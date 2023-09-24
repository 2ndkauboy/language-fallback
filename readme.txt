=== Language Fallback ===
Contributors: Kau-Boy
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7914504
Tags: localization, language, locale, english, german, l10n, i18n, translation, fallback
Requires PHP: 5.6
Requires at least: 4.0
Tested up to: 6.3

Set a language as a fallback for the chosen language (e.g. "Deutsch" as a fallback for "Deutsch (Sie)")

== Description ==

Starting with WordPress 4.3, you can use languages such as "Deutsch (Sie)" (formal German) in your WordPress installation. But if the themes or plugins you are using do not have a
translation file for this language, WordPress would use the default language, usually English, instead. With the help of this plugin, you can set a fallback for your chosen language.
Every time a translation file is loaded, the plugin will then load the fallback, if a translation for the originally chosen language was not found.

A list of all of my plugins can be found on the [WordPress Plugin page](http://kau-boys.com/wordpress-plugins "WordPress Plugins") on my blog [kau-boys.com](http://kau-boys.com). 

== Screenshots ==

1. Screenshot of the settings page

== Installation ==

Install the plugin as usual from the plugins page on your dashboard (by search or uploaded ZIP file) or upload it with FTP. You should than choose your fallback language in "Settings > General".



== Frequently Asked Questions ==

= Does this plugin only work with formal German? =

No. You can choose any fallback language that fits your original language. Think about fallbacks from "Spanish (Mexico)" to "Spanish".

= Do I need to manually install the fallback language? =

No. The plugin will automatically download the fallback language if it is not already installed.

   
== Change Log ==

= 2.0.0 =
* Implement a solution for "_load_textdomain_just_in_time" function not loading the fallback ".mo" files
* Time invested for this release: 150min

= 1.0.5 =
* Increment Tested up to
* Time invested for this release: 10min

= 1.0.4 =
* Remove the languages folder and prepare the plugin for automated deployments
* Time invested for this release: 10min

= 1.0.3 =
* Implementing the fallback locales as an array
* Time invested for this release: 10min

= 1.0.2 =
* Bumping version number
* Time invested for this release: 2min

= 1.0.1 =
* Small bugfix for the filter
* Time invested for this release: 15min

= 1.0.0 =
* Intitial release of the plugin
* Time invested for this release: 200min
