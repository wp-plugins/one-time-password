=== One-Time Password ===
Contributors: Marcel Bokhorst
Donate link: http://blog.bokhorst.biz/
Tags: login, password, security, admin
Requires at least: 2.8
Tested up to: 2.8.2
Stable tag: 0.5

One-time password system to protect your weblog in less trustworthy environments, like internet cafés.

== Description ==

This simple to use plugin enables you to login to your WordPress weblog using passwords which are valid for one session only. One-time passwords prevent stealing of your main password in less trustworthy environments, like internet cafés, for example by keyloggers. For each login you can choose between using your main password or a one-time password. The one-time password system conforms to [RFC 2289](http://tools.ietf.org/html/rfc2289 "RFC 2289").

See [Other Notes](http://wordpress.org/extend/plugins/one-time-password/other_notes/ "Other Notes") for usage instructions.

If you find this plugin useful, please vote for it on the
[WordPress Competition Blog](http://weblogtoolscollection.com/pluginblog/2009/07/22/wordpress-plugin-one-time-password/ "WordPress Competition Blog").

== Installation ==

1. Download and unzip the plugin
1. Upload the entire one-time-password/ directory to the /wp-content/plugins/ directory
1. Activate the plugin through the Plugins menu in WordPress

Or use the WordPress Plugin Add New menu.

== Frequently Asked Questions ==

= Should the pass-phrase be equal to my main password? =

No.

= Should I remember the pass-phrase? =

No, if you plan to use a printed one-time password list only.

Yes, if you plan to use a one-time password generator, 
for example [on your iPhone](http://www.apptism.com/apps/otp-generator "iPhone OTP Generator") 
or [on phones that supports Java](http://tanso.net/j2me-otp/ "j2me-otp").

= Are pass-phrases to generate one-time password lists stored? =

No.

= Can I generate a one-time password list again? =

Yes, if you remember the pass-phrase, seed and algorithm, but the one-time password sequence will be reset.

= Are one-time password words case sensitive? =

No.

= How do I choose between logging-in using a one-time password or my main password? =

Simply enter the password of your choice into the WordPress password box.

= Why is this plugin not compatible with WordPress version 2.7 or lower? =

Because the new *authenticate* filter is used. 
See [this article](http://willnorris.com/2009/03/authentication-in-wordpress-28 "Authentication in WordPress 2.8") for more details. 

= Is this plugin multi-user? =

Yes, since version 0.5.

= Where can I ask questions, report bugs and request features? =

[Here](http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/#comments "Marcel's weblog")

== Screenshots ==

1. One-time password list
1. One-time password login

== Changelog ==

= 0.5 =
* Added text domain to 'Save' text
* Changed default algorithm to md5
* Added user name and generated time to printable one-time password table
* Settings only accessible to users with role 'manage_options' (administrators)
* Modified user level of administration menu to zero
* Ajax responses with explicit character set UTF-8
* Updated documentation

= 0.4 =
* Register last login time
* Renamed time column to generated
* Catch exceptions in 'wp_authenticate' filter
* Added ajax 'New' seed link
* Added query variable 'action=challenge'
* Added algorithm, sequence, registered and last login to revoke form
* Added Flemish translation (be_NL)
* Changed background admin notice to orange-red
* Improved formatting of admin menu
* Added setting to delete data (database and options) on deactivation

= 0.3 =
* Added [I18n](http://codex.wordpress.org/I18n_for_WordPress_Developers "I18n")
* Added Dutch translation

= 0.2 =
* Show admin notice if one-time password list should be generated
* Improved documentation

= 0.1 =
* Initial version

== Usage ==

*Preparation*

1. Go to One-Time Password Settings
1. Enter and confirm a pass-phrase
1. Click the Generate button
1. Print the generated one-time password list

*Login*

1. Enter your user name as usual
1. Go to the password box
1. Wait until the challenge is displayed below the password field
1. Use the sequence number in the challenge to look up a one-time password on your printed list
1. Enter either the hex or words representation of the one-time password

== Acknowledgments ==

This plugin uses:

* [PHP One-Time Passwords class](http://sourceforge.net/projects/php-otp/ "PHP One-Time Passwords class")
written by *Tomas Mrozek* and published under the GNU Lesser General Public License version 3. 
The *readme.txt* file of this class contains useful information, for example a list of applications to compute one-time passwords.

* [jqPrint](http://plugins.jquery.com/project/jqPrint "jqPrint") plugin
written by *tanathos* and published under both the GNU General Public License and MIT License.

