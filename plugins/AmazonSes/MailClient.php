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
    private $accesskey;
    private $secretKey;

    public function __construct($host, $accessKey, $secretKey)
    {
        $this->host = $host;
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
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
        $date = date('r');
        $aws_signature = base64_encode(hash_hmac('sha256', $date, $this->secretKey, true));

        return [
            'Host: ' . $this->host,
            'Content-Type: application/x-www-form-urlencoded',
            'Date: ' . $date,
            sprintf(
                'X-Amzn-Authorization: AWS3-HTTPS AWSAccessKeyId=%s,Algorithm=HMACSHA256,Signature=%s',
                $this->accessKey,
                $aws_signature
            ),
        ];
    }

    public function endpoint()
    {
        return getConfig('amazonses_endpoint');
    }

    public function verifyResponse($response)
    {
        return true;
    }
}
