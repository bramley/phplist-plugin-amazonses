<?php
/**
 * AmazonSes plugin for phplist.
 *
 * This file is a part of AmazonSes Plugin.
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category  phplist
 *
 * @author    Duncan Cameron
 * @copyright 2016-2017 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

namespace phpList\plugin\AmazonSes;

use phpList\plugin\Common\Logger;

/**
 * This class is a client of the generic MailSender class and provides the request
 * data specific to the Amazon SES API.
 *
 * {@inheritdoc}
 *
 * @see http://docs.aws.amazon.com/ses/latest/APIReference/API_SendRawEmail.html
 */
class MailClient implements \phpList\plugin\Common\IMailClient
{
    private $host;
    private $accessKey;
    private $secretKey;

    public function __construct($host, $region, $accessKey, $secretKey)
    {
        $this->host = $host;
        $this->region = $region;
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->logger = Logger::instance();
    }

    public function requestBody(\PHPlistMailer $phplistmailer, $messageheader, $messagebody)
    {
        global $message_envelope;

        $messageheader = rtrim($messageheader, "\r\n ");
        $rawMessage = base64_encode($messageheader . "\r\n\r\n" . $messagebody);

        $request = [
            'Action' => 'SendRawEmail',
            'Source' => $message_envelope,
            'Destinations.member.1' => $phplistmailer->destinationemail,
            'RawMessage.Data' => $rawMessage,
        ];

        return http_build_query($request, null, '&');
    }

    public function httpHeaders()
    {
        list($messageheader, $messagebody) = func_get_args();
        $httpHeaders = $this->sign($messagebody);
        $httpHeaders[] = 'Host: ' . $this->host;
        $httpHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
        $httpHeaders[] = 'Date: ' . date('r');

        return $httpHeaders;
    }

    public function endpoint()
    {
        return getConfig('amazonses_endpoint');
    }

    public function verifyResponse($response)
    {
        return true;
    }

    private function sign($body)
    {
        $date = new \DateTime('UTC');
        $shortDate = $date->format('Ymd');
        $longDate = $date->format('Ymd\THis\Z');
        $algorithm = 'AWS4-HMAC-SHA256';
        $hashAlgorithm = 'sha256';
        $service = 'ses';
        $scope = "$shortDate/$this->region/$service/aws4_request";

        $canonicalFields = [
            'POST',
            '/',
            '',
        ];
        $canonicalHeaders = [
            'host' => $this->host,
            'x-amz-date' => $longDate,
        ];

        foreach ($canonicalHeaders as $k => $v) {
            $canonicalFields[] = $k . ':' . $v;
        }
        $canonicalFields[] = '';
        $canonicalFields[] = implode(';', array_keys($canonicalHeaders));
        $canonicalFields[] = hash($hashAlgorithm, $body);
        $canonicalRequest = implode("\n", $canonicalFields);

        $fieldsToSign = [
            $algorithm,
            $longDate,
            $scope,
            hash($hashAlgorithm, $canonicalRequest),
        ];
        $stringToSign = implode("\n", $fieldsToSign);

        // calculate the signature
        $dateKey = hash_hmac($hashAlgorithm, $shortDate, 'AWS4' . $this->secretKey, true);
        $regionKey = hash_hmac($hashAlgorithm, $this->region, $dateKey, true);
        $serviceKey = hash_hmac($hashAlgorithm, $service, $regionKey, true);
        $signingKey = hash_hmac($hashAlgorithm, 'aws4_request', $serviceKey, true);
        $signature = hash_hmac($hashAlgorithm, $stringToSign, $signingKey);

        $authorization = sprintf(
            '%s Credential=%s/%s,SignedHeaders=%s,Signature=%s',
            $algorithm,
            $this->accessKey,
            $scope,
            implode(';', array_keys($canonicalHeaders)),
            $signature
        );

        return [
            'X-Amz-Date: ' . $longDate,
            'Authorization: ' . $authorization,
        ];
    }
}
