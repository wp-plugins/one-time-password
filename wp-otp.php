<?php
/*
Plugin Name: One-Time Password
Plugin URI: http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/
Description: One-Time Password System conforming to <a href="http://tools.ietf.org/html/rfc2289">RFC 2289</a> to protect your weblog in less trustworthy environments, like internet cafés.
Version: 2.29
Author: Marcel Bokhorst
Author URI: http://blog.bokhorst.biz/about/
*/

/*
	Copyright 2009, 2010, 2011 Marcel Bokhorst

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
	Acknowledgments

	PHP One-Time Passwords class by Tomas Mrozek
	See http://sourceforge.net/projects/php-otp/ for details
	This class is publised under the GNU Lesser General Public License version 3 or later

	jQuery JavaScript Library
	This library is published under both the GNU General Public License and MIT License

	jqPrint by tanathos
	See http://plugins.jquery.com/project/jqPrint
	This jQuery plugin is publised both under the GNU General Public License and the MIT License

	jQuery URL Utils by Ben Alman
	See http://benalman.com/projects/jquery-url-utils-plugin/
	This jQuery plugin is published under the MIT License

	SimpleModal by Eric Martin
	See http://www.ericmmartin.com/projects/simplemodal/
	This jQuery plugin is publised both under the GNU General Public License and the MIT License

	All licenses are GPL compatible (see http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses)
*/

#error_reporting(E_ALL);

if (!function_exists('version_compare') || version_compare(PHP_VERSION, '5.0.0', '<'))
	die('One-Time Password requires at least PHP 5.0.0');

// Include otp class
require_once('wp-otp-class.php');

// Check pre-requisites
WPOneTimePassword::otp_check_prerequisites();

// Check ajax requests
WPOneTimePassword::otp_check_ajax();

// Start plugin
global $wp_one_time_password;
$one_time_password = new WPOneTimePassword();

// That's it!

?>
