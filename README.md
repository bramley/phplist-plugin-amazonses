# Amazon SES Plugin #

## Description ##

This plugin sends emails through Amazon SES using its API.

## Installation ##

### Dependencies ###

This plugin is for phplist 3.3.0 or later and requires php version 5.4 or later.

It also requires Common Plugin and the php curl extension to be installed.

### Install through phplist ###
The recommended way to install is through the Plugins page (menu Config > Manage Plugins) using the package
URL `https://github.com/bramley/phplist-plugin-amazonses/archive/master.zip`.

Then enable the plugin.

### Install manually ###
If the automatic installation does not work then you can install manually.
Download the plugin zip file from <https://github.com/bramley/phplist-plugin-amazonses/archive/master.zip>

Expand the zip file, then copy the contents of the plugins directory to your phplist plugins directory.
This should contain

* the file AmazonSes.php
* the directory AmazonSes

## Usage ##

For guidance on using the plugin see the plugin's page within the phplist documentation site <https://resources.phplist.com/plugin/amazonses>

## Support ##

Please raise any questions or problems in the user forum <https://discuss.phplist.org/>.

## Donation ##

This plugin is free but if you install and find it useful then a donation to support further development is greatly appreciated.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W5GLX53WDM7T4)

## Version history ##

    version     Description
    1.4.0+20231104  Choose region from a select list
    1.3.3+20220912  Minor bug fix
    1.3.2+20210131  Revise dependency message
    1.3.1+20200415  Minor rework of AWS signature
    1.3.0+20200412  Use AWS Signature Version 4
    1.2.3+20191102  Remove dependency on phpmailer line-ending
    1.2.2+20190213  Minor internal code change
    1.2.1+20171206  Change order of access key id and secret access key on the Settings page
    1.2.0+20170929  Use generic class to send emails
    1.1.0+20170315  Integrate with phplist by implementing the EmailSender interface
                    Release on GitHub
    1.0.1+20161214  Improve handling of outstanding transfers when process exits
    1.0.0+20160824  Send emails concurrently using multi curl
