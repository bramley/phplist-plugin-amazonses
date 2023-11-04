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
    const AMZ_ALGORITHM = 'AWS4-HMAC-SHA256';
    const HASH_ALGORITHM = 'sha256';

    private $accessKey;
    private $host;
    private $logger;
    private $region;
    private $secretKey;

    public function __construct($region, $accessKey, $secretKey)
    {
        $this->region = $region;
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->logger = Logger::instance();
        $this->host = sprintf('email.%s.amazonaws.com', $region);
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

        return http_build_query($request, '', '&');
    }

    public function httpHeaders()
    {
        list($messageheader, $messagebody) = func_get_args();
        $httpHeaders = $this->authorisationHeaders($messagebody);
        $httpHeaders[] = 'Host: ' . $this->host;
        $httpHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
        $httpHeaders[] = 'Date: ' . date('r');

        return $httpHeaders;
    }

    public function endpoint()
    {
        return sprintf('https://%s', $this->host);
    }

    public function verifyResponse($response)
    {
        return true;
    }

    private function authorisationHeaders($body)
    {
        $longDate = gmdate('Ymd\THis\Z');
        $shortDate = substr($longDate, 0, 8);
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
        $canonicalFields[] = hash(self::HASH_ALGORITHM, $body);
        $canonicalRequest = implode("\n", $canonicalFields);
        $toSign = implode("\n", [self::AMZ_ALGORITHM, $longDate, $scope, hash(self::HASH_ALGORITHM, $canonicalRequest)]);

        // calculate the signature
        $dateKey = hash_hmac(self::HASH_ALGORITHM, $shortDate, 'AWS4' . $this->secretKey, true);
        $regionKey = hash_hmac(self::HASH_ALGORITHM, $this->region, $dateKey, true);
        $serviceKey = hash_hmac(self::HASH_ALGORITHM, $service, $regionKey, true);
        $signingKey = hash_hmac(self::HASH_ALGORITHM, 'aws4_request', $serviceKey, true);
        $signature = hash_hmac(self::HASH_ALGORITHM, $toSign, $signingKey);

        $authorization = sprintf(
            '%s Credential=%s/%s,SignedHeaders=%s,Signature=%s',
            self::AMZ_ALGORITHM,
            $this->accessKey,
            $scope,
            implode(';', array_keys($canonicalHeaders)),
            $signature
        );
        $this->logger->debug(print_r($canonicalFields, true));
        $this->logger->debug(print_r($canonicalRequest, true));
        $this->logger->debug(print_r($toSign, true));
        $this->logger->debug(print_r($signature, true));

        return [
            'X-Amz-Date: ' . $longDate,
            'Authorization: ' . $authorization,
        ];
    }
}
