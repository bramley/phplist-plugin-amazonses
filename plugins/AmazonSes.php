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

    public function activate()
    {
        $regions = [
            'us-east-1' => 'US East (N. Virginia) us-east-1',
            'us-east-2' => 'US East (Ohio) us-east-2',
            'us-west-1' => 'US West (N. California) us-west-1',
            'us-west-2' => 'US West (Oregon) us-west-2',
            'af-south-1' => 'Africa (Cape Town) af-south-1',
            'ap-east-1' => 'Asia Pacific (Hong Kong) ap-east-1',
            'ap-south-2' => 'Asia Pacific (Hyderabad) ap-south-2',
            'ap-southeast-3' => 'Asia Pacific (Jakarta) ap-southeast-3',
            'ap-southeast-4' => 'Asia Pacific (Melbourne) ap-southeast-4',
            'ap-south-1' => 'Asia Pacific (Mumbai) ap-south-1',
            'ap-northeast-3' => 'Asia Pacific (Osaka) ap-northeast-3',
            'ap-northeast-2' => 'Asia Pacific (Seoul) ap-northeast-2',
            'ap-southeast-1' => 'Asia Pacific (Singapore) ap-southeast-1',
            'ap-southeast-2' => 'Asia Pacific (Sydney) ap-southeast-2',
            'ap-northeast-1' => 'Asia Pacific (Tokyo) ap-northeast-1',
            'ca-central-1' => 'Canada (Central) ca-central-1',
            'eu-central-1' => 'Europe (Frankfurt) eu-central-1',
            'eu-west-1' => 'Europe (Ireland) eu-west-1',
            'eu-west-2' => 'Europe (London) eu-west-2',
            'eu-south-1' => 'Europe (Milan) eu-south-1',
            'eu-west-3' => 'Europe (Paris) eu-west-3',
            'eu-south-2' => 'Europe (Spain) eu-south-2',
            'eu-north-1' => 'Europe (Stockholm) eu-north-1',
            'eu-central-2' => 'Europe (Zurich) eu-central-2',
            'il-central-1' => 'Israel (Tel Aviv) il-central-1',
            'me-south-1' => 'Middle East (Bahrain) me-south-1',
            'me-central-1' => 'Middle East (UAE) me-central-1',
            'sa-east-1' => 'South America (São Paulo) sa-east-1',
            'us-gov-east-1' => 'AWS GovCloud (US-East) us-gov-east-1',
            'us-gov-west-1' => 'AWS GovCloud (US-West) us-gov-west-1',
        ];
        $this->settings = [
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
                'description' => 'SES region',
                'type' => 'select',
                'values' => $regions,
                'value' => 'us-east-1',
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

        parent::activate();
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
