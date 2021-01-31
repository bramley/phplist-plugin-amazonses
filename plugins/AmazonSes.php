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

/**
 * Registers the plugin with phplist.
 */
if (!interface_exists('EmailSender')) {
    echo 'AmazonSes plugin requires phplist 3.3.0+';

    return;
}

class AmazonSes extends phplistPlugin implements EmailSender
{
    const VERSION_FILE = 'version.txt';

    private $mailSender;

    /*
     *  Inherited variables
     */
    public $name = 'Amazon SES Plugin';
    public $authors = 'Duncan Cameron';
    public $description = 'Use Amazon SES to send emails';
    public $documentationUrl = 'https://resources.phplist.com/plugin/amazonses';
    public $settings = [
        'amazonses_access_key' => [
            'value' => '',
            'description' => 'AWS access key ID',
            'type' => 'text',
            'allowempty' => false,
            'category' => 'Amazon SES',
        ],
        'amazonses_secret_key' => [
            'value' => '',
            'description' => 'AWS secret access key',
            'type' => 'text',
            'allowempty' => false,
            'category' => 'Amazon SES',
        ],
        'amazonses_region' => [
            'value' => 'us-east-1',
            'description' => 'SES region',
            'type' => 'text',
            'allowempty' => false,
            'category' => 'Amazon SES',
        ],
        'amazonses_endpoint' => [
            'value' => 'https://email.us-east-1.amazonaws.com/',
            'description' => 'SES endpoint',
            'type' => 'text',
            'allowempty' => false,
            'category' => 'Amazon SES',
        ],
        'amazonses_multi' => [
            'value' => false,
            'description' => 'Whether to use curl multi to send emails concurrently',
            'type' => 'boolean',
            'allowempty' => true,
            'category' => 'Amazon SES',
        ],
        'amazonses_multi_limit' => [
            'value' => 4,
            'min' => 2,
            'max' => 32,
            'description' => 'The maximum number of emails to send concurrently when using curl multi, (between 2 and 32)',
            'type' => 'integer',
            'allowempty' => false,
            'category' => 'Amazon SES',
        ],
        'amazonses_multi_log' => [
            'value' => false,
            'description' => 'Whether to create a log file showing all curl multi transfers',
            'type' => 'boolean',
            'allowempty' => true,
            'category' => 'Amazon SES',
        ],
        'amazonses_curl_verbose' => [
            'value' => false,
            'description' => 'Whether to generate verbose curl output (use only for debugging)',
            'type' => 'boolean',
            'allowempty' => true,
            'category' => 'Amazon SES',
        ],
    ];

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
        global $emailsenderplugin, $plugins;

        return [
            'PHP version 5.4.0 or greater' => version_compare(PHP_VERSION, '5.4') > 0,
            'No other plugin to send emails can be enabled' => empty($emailsenderplugin) || get_class($emailsenderplugin) == __CLASS__,
            'curl extension installed' => extension_loaded('curl'),
            'Common Plugin version 3.12.0 or later must be enabled' => (
                phpListPlugin::isEnabled('CommonPlugin') && version_compare($plugins['CommonPlugin']->version, '3.12.0') >= 0
            ),
            'phpList 3.3.0 or greater' => version_compare(VERSION, '3.3') > 0,
        ];
    }

    /**
     * Send an email using the Amazon SES API.
     *
     * @param PHPlistMailer $phplistmailer mailer instance
     * @param string        $messageheader the message http headers
     * @param string        $messagebody   the message body
     *
     * @return bool success/failure
     */
    public function send(PHPlistMailer $phplistmailer, $messageheader, $messagebody)
    {
        if ($this->mailSender === null) {
            $client = new phpList\plugin\AmazonSes\MailClient(
                parse_url(getConfig('amazonses_endpoint'), PHP_URL_HOST),
                getConfig('amazonses_region'),
                getConfig('amazonses_access_key'),
                getConfig('amazonses_secret_key')
            );

            $this->mailSender = new phpList\plugin\Common\MailSender(
                $client,
                (bool) getConfig('amazonses_multi'),
                (int) getConfig('amazonses_multi_limit'),
                (bool) getConfig('amazonses_multi_log'),
                (bool) getConfig('amazonses_curl_verbose'),
                true
            );
        }

        return $this->mailSender->send($phplistmailer, $messageheader, $messagebody);
    }

    /**
     * This hook is called within the processqueue shutdown() function.
     *
     * For command line processqueue phplist exits in its shutdown function
     * therefore need to explicitly call the mailsender shutdown method.
     */
    public function processSendStats($sent = 0, $invalid = 0, $failed_sent = 0, $unconfirmed = 0, $counters = array())
    {
        if ($this->mailSender !== null) {
            $this->mailSender->shutdown();
        }
    }
}
