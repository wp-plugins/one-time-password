<?php
/*
Plugin Name: One-Time Password
Plugin URI: http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/
Description: One-Time Password System conforming to <a href="http://tools.ietf.org/html/rfc2289">RFC 2289</a> to protect your weblog in less trustworthy environments, like internet cafés.
Version: 2.0
Author: Marcel Bokhorst
Author URI: http://blog.bokhorst.biz/
*/

/*
	Copyright 2009  Marcel Bokhorst

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

	jqPrint by tanathos
	See http://plugins.jquery.com/project/jqPrint
	This jQuery plugin is publised both under the GNU General Public License and the MIT License

	jQuery URL Utils by Ben Alman
	See http://benalman.com/projects/jquery-url-utils-plugin/
	This jQuery plugin is published under the MIT License

	SimpleModal by Eric Martin
	See http://www.ericmmartin.com/projects/simplemodal/
	This jQuery plugin is publised both under the GNU General Public License and the MIT License

	All licenses are GPL compatible (see http://www.gnu.org/philosophy/license-list.html#GPLCompatibleLicenses)
*/

/*
	ToDo:
	- modular (more helpers methods)
	- update translations

	- test IE -> ajax (seed, default)
	- check errors $wpdb->get_var
	- compatibility Semisecure Login Reimagined
	- check post values as query string?
	- query var with certain value (instead of generic wildcard)
	- alternative for wp_die?
	- https & otp?
	- test openid compatibility (admin otp)
	- JavaScript file(s)
	- parameterize/localize JavaScript (document.getElementById("myscript").src)

	Docs:
	- What-if admin lockout
	- F5 in strict mode
	- page loading in strict mode
*/

#error_reporting(E_ALL);

// Include otp class
require_once('wp-otp-class.php');

// Check pre-requisites
WPOneTimePassword::otp_check_prerequisites();

// Check ajax requests
WPOneTimePassword::otp_check_ajax();

// Start plugin
global $wp_one_time_password;
$one_time_password = new WPOneTimePassword(__FILE__);

// That's it!

?>
