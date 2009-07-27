=== One-Time Password ===
Contributors: Marcel Bokhorst
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=AJSBB7DGNA3MJ&lc=US&item_name=One%2dTime%20Password%20WordPress%20Plugin&item_number=Marcel%20Bokhorst&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: login, password, security, admin
Requires at least: 2.8
Tested up to: 2.8.2
Stable tag: 1.4

One-time password system conform RFC 2289 to protect your weblog in less trustworthy environments, like internet cafés.

== Description ==

This simple to use plugin enables you to login to your WordPress weblog using passwords which are valid for one session only. One-time passwords prevent stealing of your main WordPress password in less trustworthy environments, like internet cafés, for example by keyloggers. For each login you can choose between using your main password or a one-time password. The one-time password system conforms to [RFC 2289](http://tools.ietf.org/html/rfc2289 "RFC 2289") of the [Internet Engineering Task Force](http://www.ietf.org/ "IETF") (IETF).

See [Other Notes](http://wordpress.org/extend/plugins/one-time-password/other_notes/ "Other Notes") for usage instructions.

**This plugin requires at least PHP 5.0.0 and WordPress 2.8.**

If you find this plugin useful, please vote for it on the
[WordPress Competition Blog](http://weblogtoolscollection.com/pluginblog/2009/07/22/wordpress-plugin-one-time-password/ "WordPress Competition Blog").

== Installation ==

*Using the dashboard*

1. Login to your weblog
1. Goto Plugins
1. Select Add New
1. Search for One-Time Password
1. Select Install
1. Select Install Now
1. Select Activate Plugin

*Manual*

1. Download and unzip the plugin
1. Upload the entire one-time-password/ directory to the /wp-content/plugins/ directory
1. Activate the plugin through the Plugins menu in WordPress

== Frequently Asked Questions ==

= Should the pass-phrase be equal to my main password? =

No, but it could be.

= Should I remember the pass-phrase? =

No, if you plan to use a printed one-time password list only.

Yes, if you plan to use a one-time password generator, 
either [on your iPhone](http://www.apptism.com/apps/otp-generator "iPhone OTP Generator") (not tried)
or on mobile phones that support [JavaME](http://en.wikipedia.org/wiki/Java_Platform,_Micro_Edition "JavaME"), 
for example by using
[j2me-otp](http://tanso.net/j2me-otp/ "j2me-otp") (tried with success) or
[OTPGen](http://www.getjar.com/products/471/OTPGen "OTPGen").

= Are pass-phrases to generate one-time password lists stored? =

No.

= What should I do when I loose my one-time password list? =

Revoke it as soon as possible. 
Generating a new one-time password list will revoke the existing list automatically.
Do not generate a new one-time password list with the same pass-phrase, seed and algorithm (at least one should be different).

= Can I generate a one-time password list again? =

Yes, if you remember the pass-phrase, seed and algorithm, but the one-time password sequence will be reset.

= Are one-time password words case sensitive? =

No.

= How do I choose between logging-in using a one-time password or my main WordPress password? =

Simply enter the password of your choice into the WordPress password box.

= Why does this plugin require at least WordPress version 2.8? =

Because the new *authenticate* filter is used. 
See [this article](http://willnorris.com/2009/03/authentication-in-wordpress-28 "Authentication in WordPress 2.8") for more details. 

= Is this plugin multi-user? =

Yes, since version 0.5.

= Will this plugin work with WordPress MU? =

Yes, since version 1.2.

= Why does this plugin require at least PHP version 5.0.0? =

Because the [PHP One-Time Passwords class](http://sourceforge.net/projects/php-otp/ "PHP One-Time Passwords class") requires it 
and because the try-catch construction is used as a fail-safe for the login screen.

= Where can I ask questions, report bugs and request features? =

You can write a comment on the [plugin homepage](http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/ "Marcel's weblog").

== Screenshots ==

1. The login screen displaying a [RFC 2289](http://tools.ietf.org/html/rfc2289 "RFC 2289") challenge
1. The one-time password list with the requested password
1. The [OTPGen](http://www.getjar.com/products/471/OTPGen "OTPGen") application on a phone that supports [JavaME](http://en.wikipedia.org/wiki/Java_Platform,_Micro_Edition "JavaME") (this is optional)

== Changelog ==

= 1.4 =
* Defined constants
* Fixed odd number of passwords
* Update translations
* Check WordPress version on activate
* Removed hard coded paths for better compatibility
* Updated the [faq](http://wordpress.org/extend/plugins/one-time-password/faq/ "Faq"): what-if one-time password list lost
* Updated the [usage instructions](http://wordpress.org/extend/plugins/one-time-password/other_notes/ "Other Notes"): do not print one-time password list with url

= 1.3 =
* Renamed query arg 'action' to 'otp_action' for better compatibility

= 1.2 =
* Update for WordPress MU

= 1.1 =
* Check for PHP version 5
* Admin notice uses standard style now

= 1.0 =
* Only load styles and scripts when necessary
* Remove leading/trailing spaces user name
* Show sequence number within challenge bold and somewhat larger
* Added input field to choose number of passwords
* Focus on pass-phrase field
* Minor code improvements, mostly comments

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
* Improved formatting of the administration menu
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

**For security reasons do not print the one-time password list with the url of your weblog in the header.**

FireFox and Internet Explorer: you can change this using File / Page Setup.

*Login*

1. Enter your user name as usual
1. Go to the password box
1. Wait until the challenge is displayed below the password field
1. Use the sequence number in the challenge to look up a one-time password on your printed list
1. Enter either the hex or words representation of the looked-up one-time password

The [screens shots](http://wordpress.org/extend/plugins/one-time-password/screenshots/ "One-Time Password screenshots") show how to look-up a one-time password.

Note that:

* You can always login with your main WordPress password too
* You can use a one-time password generator on your mobile phone, see the [Faq](http://wordpress.org/extend/plugins/one-time-password/faq/ "Faq") for some links

== Acknowledgments ==

This plugin uses:

* [PHP One-Time Passwords class](http://sourceforge.net/projects/php-otp/ "PHP One-Time Passwords class")
written by *Tomas Mrozek* and published under the GNU Lesser General Public License version 3. 
The *readme.txt* file of this class contains useful information, for example a list of applications to compute one-time passwords.

* [jqPrint](http://plugins.jquery.com/project/jqPrint "jqPrint") plugin
written by *tanathos* and published under both the GNU General Public License and MIT License.

