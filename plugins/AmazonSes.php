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
 * @copyright 2016 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 */

/**
 * Registers the plugin with phplist.
 */
class AmazonSes extends phplistPlugin
{
    const VERSION_FILE = 'version.txt';

    /*
     *  Inherited variables
     */
    public $name = 'Amazon SES Plugin';
    public $authors = 'Duncan Cameron';
    public $description = 'Use Amazon SES to send emails';
    public $documentationUrl = 'https://resources.phplist.com/plugin/amazonses';
    public $settings = array(
        'amazonses_secret_key' => array(
            'value' => '',
            'description' => 'Secret key',
            'type' => 'text',
            'allowempty' => false,
            'category' => 'Amazon SES',
        ),
        'amazonses_access_key' => array(
            'value' => '',
            'description' => 'Access key',
            'type' => 'text',
            'allowempty' => false,
            'category' => 'Amazon SES',
        ),
        'amazonses_endpoint' => array(
            'value' => 'https://email.us-east-1.amazonaws.com/',
            'description' => 'SES endpoint',
            'type' => 'text',
            'allowempty' => false,
            'category' => 'Amazon SES',
        ),
    );

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . 'AmazonSes' . '/';
        parent::__construct();
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
    }

    /**
     * Provide the dependencies for enabling this plugin.
     *
     * @return array
     */
    public function dependencyCheck()
    {
        return array(
            'PHP version 5.4.0 or greater' => version_compare(PHP_VERSION, '5.4') > 0,
            'curl extension installed' => extension_loaded('curl'),
        );
    }
    /**
     * Send an email using the Amazon SES API.
     *
     * @see 
     * 
     * @param PHPlistMailer $phplistmailer mailer instance
     * @param string        $messageheader the message http headers
     * @param string        $messagebody   the message body
     *
     * @return bool success/failure
     */
    public function send($phplistmailer, $messageheader, $messagebody)
    {
        
        static $curl = null;

        if ($curl === null) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, getConfig('amazonses_endpoint'));
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_HEADER, 1);
            curl_setopt($curl, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
            curl_setopt($curl, CURLOPT_USERAGENT, NAME . ' (phpList version ' . VERSION . ', http://www.phplist.com/)');
            curl_setopt($curl, CURLOPT_POST, 1);
        }

        $messageheader = preg_replace('/' . $phplistmailer->LE . '$/', '', $messageheader);
        $messageheader .= $phplistmailer->LE . 'Subject: ' . $phplistmailer->EncodeHeader($phplistmailer->Subject) . $phplistmailer->LE;

        $date = date('r');
        $aws_signature = base64_encode(hash_hmac('sha256', $date, getConfig('amazonses_secret_key'), true));

        $requestheader = array(
            'Host: ' . parse_url(getConfig('amazonses_endpoint'), PHP_URL_HOST),
            'Content-Type: application/x-www-form-urlencoded',
            'Date: ' . $date,
            'X-Amzn-Authorization: AWS3-HTTPS AWSAccessKeyId=' . getConfig('amazonses_access_key') . ',Algorithm=HMACSHA256,Signature=' . $aws_signature,
            'Connection: keep-alive',
            'Keep-Alive: 300',
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestheader);

        $rawmessage = base64_encode($messageheader . $phplistmailer->LE . $phplistmailer->LE . $messagebody);
        $requestdata = array(
            'Action' => 'SendRawEmail',
            'Source' => $GLOBALS['message_envelope'],
            'Destinations.member.1' => $phplistmailer->destinationemail,
            'RawMessage.Data' => $rawmessage,
        );
        $data = http_build_query($requestdata, null, '&');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        $res = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($res === false || $status != 200) {
            $error = curl_error($curl);
            curl_close($curl);
            $curl = null;
            logEvent('Amazon SES status ' . $status . ' ' . strip_tags($res) . ' ' . $error);

            return false;
        }

        return true;
    }
}
