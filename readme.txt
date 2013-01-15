=== One-Time Password ===
Contributors: Marcel Bokhorst
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=AJSBB7DGNA3MJ&lc=US&item_name=One%2dTime%20Password%20WordPress%20Plugin&item_number=Marcel%20Bokhorst&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: login, password, security, admin, authentication, wpmu, access
Requires at least: 2.8
Tested up to: 3.5
Stable tag: 2.31

One-time password system conform RFC 2289 to protect your weblog in less trustworthy environments, like internet cafés.

== Description ==

This simple to use plugin enables you to login to your WordPress weblog using passwords which are valid for one session only. One-time passwords prevent stealing of your main WordPress password in less trustworthy environments, like internet cafés, for example by keyloggers. The one-time password system conforms to [RFC 2289](http://tools.ietf.org/html/rfc2289 "RFC 2289") of the [Internet Engineering Task Force](http://www.ietf.org/ "IETF") (IETF).

*Version 2 of this plugin has a new option to protect administrative actions by one-time passwords. This option is disabled by default and only available when you logged-in with a one-time password. It is possible to define exceptions. The default exceptions are viewing the dashboard, adding a post (but not saving it) and logging out.*

See [Other Notes](http://wordpress.org/extend/plugins/one-time-password/other_notes/ "Other Notes") for usage instructions.

**This plugin requires at least PHP 5**

Please report any issue you have with this plugin on the [support page](http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/ "Marcel's weblog"), so I can at least try to fix it. If you rate this plugin low, please [let me know why](http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/#respond "Marcel's weblog").

See my [other plugins](http://wordpress.org/extend/plugins/profile/m66b "Marcel Bokhorst")

== Installation ==

*Using the WordPress dashboard*

1. Login to your weblog
1. Go to Plugins
1. Select Add New
1. Search for One-Time Password
1. Select Install
1. Select Install Now
1. Select Activate Plugin

*Manual*

1. Download and unzip the plugin
1. Upload the entire one-time-password/ directory to the /wp-content/plugins/ directory
1. Activate the plugin through the Plugins menu in WordPress

Amit Banerjee wrote an [excellent guide](http://www.ampercent.com/one-time-passwords-wordpress-blog-prevent-keylogging/7720/) to setup the plugin.

== Frequently Asked Questions ==

= Should the pass-phrase be equal to my main password? =

No, but it could be.

= Should I remember the pass-phrase? =

No, if you plan to use a printed one-time password list only.

Yes, if you plan to use a one-time password generator,
[on your iPhone](http://www.apptism.com/apps/otp-generator "iPhone OTP Generator") (not tried)
or [on Android](http://www.androlib.com/android.application.ub0r-android-otpdroid-pzmn.aspx "OTPdroid") (tried with success)
or on mobile phones that support [JavaME](http://en.wikipedia.org/wiki/Java_Platform,_Micro_Edition "JavaME"), for example using
[j2me-otp](http://tanso.net/j2me-otp/ "j2me-otp") (not tried) or
[OTPGen](http://www.getjar.com/products/471/OTPGen "OTPGen") (tried with success).

If you are using a one-time password generator, you can safely generate a new password list using a one-time password
by entering this password in the pass-phrase field and by checking *Pass-phrase is a One-Time Password*.
The sequence number should be entered into the *Count/sequence* field. In this case no password list will be displayed.

= Are pass-phrases to generate one-time password lists stored? =

No.

= What should I do when I have lost my one-time password list? =

Revoke it as soon as possible. Generating a new one-time password list will revoke the existing list automatically.
Do not generate a new one-time password list with the same pass-phrase, seed and algorithm (at least one should be different).

= Can I generate a one-time password list again? =

Yes, if you remember the pass-phrase, seed and algorithm, but the one-time password sequence will be reset.

= Are one-time passwords case sensitive? =

No.

= How do I choose between logging-in using a one-time password or my main WordPress password? =

Simply enter the password of your choice into the WordPress password box.

= How can I change the styling? =

1. Copy *wp-otp.css* to your theme directory to prevent it from being overwritten by an update
2. Change the style sheet to your wishes; the style sheet contains documentation

= Why does this plugin require at least WordPress version 2.8? =

Because the new *authenticate* filter is used.
See [this article](http://willnorris.com/2009/03/authentication-in-wordpress-28 "Authentication in WordPress 2.8") for more details.

= Is this plugin multi-user? =

Yes, since version 0.5.

= Will this plugin work with WordPress MU? =

Yes, since version 1.2.

= Why does this plugin require at least PHP version 5.0.0? =

Because this is a requirement of the [PHP One-Time Passwords class](http://sourceforge.net/projects/php-otp/ "PHP One-Time Passwords class") and
because the *try-catch* construction is used as a fail-safe for the login screen.

= Who can modify the one-time password options? =

Users with *manage\_options* capability, normally only administrators.

= What is the scope of the one-time password options? =

Site wide.

= How does the integration with the http:BL plugin work? =

First of all the integration with the [http:BL plugin](http://wordpress.org/extend/plugins/httpbl/ "http:BL")
has to be enabled using the settings menu.
If enabled, you can navigate to the login url of your blog, even if http:BL would normally block it.
A warning indication the age, level and threat type is displayed above the login window.
You can login only using a one-time password, not with your user name and password.
After logging in, you can navigate to any part of your weblog, until you sign out.
Note that before logging in only *wp-login.php* is available and no other addresses like */wp-admin/*.

I recommend installing [Invalidate Logged Out Cookies](http://wordpress.org/extend/plugins/invalidate-logged-out-cookies/) for more security.

= How does the integration with Bad Behavior work? =

If you enable the option to disable [Bad Behavior](http://wordpress.org/extend/plugins/bad-behavior/ "Bad Behavior") on the login page using the settings menu the Bad Behavior plugin will be disabled.
To re-enabled the Bad Behavior plugin you have to disable this option first.
When this option is enabled the one-time password plugin will load the Bad Behavior plugin instead of WordPress, except for the login page and for every other page when you are logged in using a one-time password.
Unfortunately it is not possible (yet) to display a warning on the login page that Bad Behavior would block access.

= Will RFC 4226 be supported? =

No, RFC 4226 requires a symmetric key, which should be stored. WordPress does not provide a safe way to store keys.

= Where can I ask questions, report bugs and request features? =

You can write a comment on the [support page](http://blog.bokhorst.biz/2200/computers-en-internet/wordpress-plugin-one-time-password/ "Marcel's weblog").

== Screenshots ==

1. The login screen displaying a challenge
1. The one-time password list with the requested password
1. The [OTPGen](http://www.getjar.com/products/471/OTPGen "OTPGen") application on a phone that supports [JavaME](http://en.wikipedia.org/wiki/Java_Platform,_Micro_Edition "JavaME") (optional)
1. The authorize window for an administrative action (optional)

== Changelog ==

= Development version =
* Bugfix: PHP warning
* You can download the development version [here](http://downloads.wordpress.org/plugin/one-time-password.zip)

= 2.31 =
* Added Lithuanian (lt\_LT) by [Host1Free](http://www.host1free.com/ "Host1Free")

= 2.29 =
* Added Romanian translation (ro\_RO) by *Alexander Ovsov*

= 2.28 =
* Removed [Sustainable Plugins Sponsorship Network](http://pluginsponsors.com/)

= 2.27 =
* Added Rusian translation (ru\_RU) by *Yurij*

= 2.26 =
* Removed tools page
* Fixed notice
* Tested with WordPress 3.3

= 2.25 =
* Only printing needed scripts/styles on login page

= 2.24 =
* Style fix tools page
* Added *Sustainable Plugins Sponsorship Network* again
* Updated Dutch/Flemish translations

= 2.23 =
* Removed *Sustainable Plugins Sponsorship Network*

= 2.22 =
* Tested with WordPress 3.2
* Updated sponsorship ID

= 2.21 =
* Added Polish translation (pl\_PL) by [Positionmaker](http://positionmaker.pl/ "Positionmaker")

= 2.20 =
* Re-release because of a bug in the WordPress repository

= 2.19 =
* Re-release because of a bug in the WordPress repository

= 2.18 =
* Re-release because of a bug in the WordPress repository

= 2.17 =
* Fixed all PHP notices
* Compatibility with [Google Analyticator](http://wordpress.org/extend/plugins/google-analyticator/ "Google Analyticator")

= 2.16 =
* Added Italian translation (it\_IT) by [Aldo](http://profiles.wordpress.org/users/aldolat/ "Aldo")

= 2.15 =
* Added French translation (fr\_FR) by [Emmanuelle](http://www.translatonline.com/ "Emmanuelle")
* Updated Dutch/Flemish translations
* Updated SimpleModal to version 1.4.1
* Tested with WordPress 3.1 beta 1

= 2.14 =
* Using https transport when needed

= 2.13 =
* 'I have donated' removes donate button

= 2.12 =
* Added option to store css in upload folder

= 2.11 =
* Constructor compatibility with PHP 5.3.3+

= 2.10 =
* Added tool to bulk generate OTP lists

= 2.9 =
* Option to disable normal password login
* Improved [http:BL](http://wordpress.org/extend/plugins/httpbl/ "http:BL") integration
* Improved [Bad Behavior](http://wordpress.org/extend/plugins/bad-behavior/ "Bad Behavior") integration
* Updated jqPrint to version 0.3.1
* Updated SimpleModal to version 1.3.5
* Updated Dutch/Flemish translations

= 2.8.6 =
* Better default for 'Do not protect'

= 2.8.5 =
* Starting session if not started already for better compatibility with other plugins

= 2.8.4 =
* Belorussian (be\_BY) translation by [Marcis G.](http://pc.de/ "Marcis G.")
* Updated jQuery URL Utils to version 1.11
* Updated jQuery SimpleModal to version 1.3.4

= 2.8.3 =
* Fixed incompatibility with [GD Star Rating plugin](http://wordpress.org/extend/plugins/gd-star-rating/ "GD Star Rating plugin")

= 2.8.2 =
* Added link to Privacy Policy of Sustainable Plugins Sponsorship Network
* Added option 'I have donated to this plugin'
* Moved Sustainable Plugins Sponsorship Network banner to top

= 2.8.1 =
* Participating in the [Sustainable Plugins Sponsorship Network](http://pluginsponsors.com/ "PluginSponsors.com")

= 2.8 =
* Option to disable Bad Behavior for the login page
* Updated Dutch/Flemish translations

= 2.7.1 =
* Option to enable integration with http:BL
* Updated Dutch/Flemish translations

= 2.7 =
* Integration with [http:BL plugin](http://wordpress.org/extend/plugins/httpbl/ "http:BL WordPress Plugin"); allow otp login even if threat

= 2.6.3 =
* Checking PHP version before loading classes
* Made request method case insensitive

= 2.6.2 =
* Updated German translation

= 2.6.1 =
* Added German translation (de\_DE) by *Heiko Bartsch \[mai 'kju:t̬i\]*

= 2.6 =
* Using class pointer in stead of static references
* Replaced database table by user meta data

= 2.5.2 =
* Added Chinese and Taiwanese translations (zh\_CN/TW) by *Vikingzheng*
* Updated documentation

= 2.5.1 =
* Restored hard-coded style of unauthorized message
* Moved generate error messages back to correct place
* Included minified versions of the URL Utils and SimpleModal jQuery plugins
* Upgraded SimpleModal jQuery plugin to version 1.3
* Upgraded PHP One-Time Passwords class to version 1.0.3
* Using *$wpdb->escape* in SQL statements only to prevent mistakes
* Calling *sanitize\_user* for user names

= 2.5 =
* Better options for custom styling, see [faq](http://wordpress.org/extend/plugins/one-time-password/faq/ "Faq") for details
* Moved password list to the top for clarity

= 2.4 =
* Ending protected session with logout
* Splitted the large *otp\_administration* function
* Made location of .css file relative

= 2.3 =
* Added *session\_start* to class constructor

= 2.2 =
* Updated Czech translation by *Tomas Mrozek*
* Checking of password validity less strict
* Displaying *wait* when getting new seed / default protect exceptions
* Improved formatting of admin panel
* Modified class constructor to get callers file name

= 2.1 =
* **Added an option to initialize a one-time password list with a one-time password**
* Continuing session protecting after exhausting one-time passwords
* Added Czech translation by *Tomas Mrozek*

= 2.0 =
* Added resources info panel
* **Added protection for admin actions with one-time passwords**
* Added checks for required WordPress functions
* Updated Dutch/Flemish translations
* Updated documentation
* Created class for better compatibility
* Added helper methods
* Moved to old-style JavaScript comments
* Made JavaScript compliant with [XHTML](http://en.wikipedia.org/wiki/XHTML "XHTML")
* Displaying notices on the admin menu too
* Using new-style *option\_page=options*
* Moved rendering of admin notices to *admin\_footer*
* Removing *otp\_authorization* query arg from url

= 1.4 =
* Defined constants
* Fixed odd number of passwords
* Updated translations
* Checking WordPress version on activate
* Removed hard coded paths for better compatibility
* Updated the [faq](http://wordpress.org/extend/plugins/one-time-password/faq/ "Faq"): what-if one-time password list lost
* Updated the [usage instructions](http://wordpress.org/extend/plugins/one-time-password/other_notes/ "Other Notes"): do not print one-time password list with url

= 1.3 =
* Renamed query arg *action* to *otp\_action* for better compatibility

= 1.2 =
* Update for WordPress MU

= 1.1 =
* Checking for PHP version 5
* Using standard WordPress style for admin notices

= 1.0 =
* Loading styles and scripts when necessary only
* Removing leading/trailing spaces of user name
* Showing sequence number within challenge bold and somewhat larger
* Added input field to choose number of passwords
* Setting default focus on pass-phrase field
* Minor code improvements, mostly comments

= 0.5 =
* Added text domain to *Save* text
* Changed default algorithm to md5
* Added user name and generated time to printable one-time password table
* Settings only accessible to users with role *manage\_options* (administrators)
* Modified user level of administration menu to zero
* Ajax responses with explicit character set UTF-8
* Updated documentation

= 0.4 =
* Registering last login time
* Renamed database column *Time* to *Generated*
* Catching exceptions in *wp\_authenticate* filter
* Added ajax *New* seed link
* Added query arg *action=challenge*
* Added *Algorithm*, *Sequence*, *Registered* and *Last login* to revoke form
* Added Flemish translation (nl\_BE)
* Changed background admin notice to orange-red
* Improved formatting of the administration menu
* Added setting to delete data (database and options) on deactivation

= 0.3 =
* Added [I18n](http://codex.wordpress.org/I18n_for_WordPress_Developers "I18n")
* Added Dutch translation

= 0.2 =
* Showing admin notice if one-time password list should be generated
* Improved documentation

= 0.1 =
* Initial version

== Upgrade Notice ==

= 2.29 =
Romanian translation

= 2.28 =
Compliance

= 2.27 =
Rusian translation

= 2.26 =
Compatibility

= 2.25 =
Compatibility

= 2.24 =
Compatibility

= 2.23 =
Compatibility

= 2.22 =
Compatibility

= 2.21 =
Polish translation

= 2.17 =
Compatibility

= 2.14 =
Compatibility

= 2.15 =
French translation, compatibility

= 2.13 =
New feature: remove donate button

= 2.12 =
Compatibility

= 2.11 =
Compatibility

= 2.8.6 =
Compatibility

= 2.8.5 =
Compatibility

= 2.8.3 =
Fixed incompatibility with GD Star Rating plugin

== Usage ==

*Preparation*

1. Go to One-Time Password Settings
1. Enter and confirm a pass-phrase
1. Click the Generate button
1. Print the generated one-time password list (the button is below the list)

**For security reasons generate a one-time password list in a trustworthy environment only.**

**For security reasons do not print the one-time password list with the url of your weblog in the header.**

FireFox and Internet Explorer: you can change this using the menu *File / Page Setup*.

*Login*

1. Enter your user name as usual
1. Go to the password box
1. Wait until the challenge is displayed below the password field
1. Use the sequence number in the challenge to look up a one-time password on your printed list
1. Enter either the hex or words representation of the looked-up one-time password

The [screens shots](http://wordpress.org/extend/plugins/one-time-password/screenshots/ "One-Time Password screenshots") show how to look-up a one-time password.

Note that:

* You can always login with your main WordPress password too
* You could use a one-time password generator on your mobile phone, see the [faq](http://wordpress.org/extend/plugins/one-time-password/faq/ "Faq") for some links
* You could enable protection of all WordPress administrative actions by one-time passwords

== Known Problems ==

* Updating your WordPress user profile requires another click on the Update Profile button (cause unknown)
* Page refreshes of protected administrative actions will be disapproved (more a feature)
* No authorization is asked if an administrative page has not finished loading. However, the server will still check and disapprove the authorization.

== Acknowledgments ==

This plugin uses:

* [PHP One-Time Passwords class](http://sourceforge.net/projects/php-otp/ "PHP One-Time Passwords class")
by *Tomas Mrozek* and published under the GNU Lesser General Public License version 3.
The *readme.txt* file of this class contains useful information, for example a list of applications to compute one-time passwords.

* [jQuery JavaScript Library](http://jquery.com/ "jQuery") published under both the GNU General Public License and MIT License

* [jqPrint](http://plugins.jquery.com/project/jqPrint "jQuery jqPrint") jQuery plugin
by *tanathos* and published under both the GNU General Public License and MIT License

* [URL Utils](http://benalman.com/projects/jquery-url-utils-plugin/ "jQuery URL Utils") jQuery plugin
by *Ben Alman* and published under the MIT License

* [SimpleModal](http://www.ericmmartin.com/projects/simplemodal/ "jQuery SimpleModal") jQuery plugin
by *Eric Martin* and published both under the GNU General Public License and the MIT License

All licenses are [GPL-Compatible Free Software Licenses](http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses "GPL compatible").

