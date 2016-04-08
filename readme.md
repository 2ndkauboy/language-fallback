# Backend Localization #
**Contributors:** Kau-Boy  
**Donate link:** https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7914504  
**Tags:** localization, language, locale, english, german, l10n, i18n, translation, fallback  
**Requires at least:** 4.0  
**Stable tag:** 1.0  
**Tested up to:** 4.5

Set a language as a fallback for the chosen language (e.g. "Deutsch" as a fallback for "Deutsch (Sie)")

## Description ##

Starting with WordPress 4.3, you can use languages such as "Deutsch (Sie)" (formal German) in your WordPress installation. But if the themes or plugins you are using to not have a
translation file for this language, WordPress would use the default language, usually English, instead. With the help of this plugin, you can set a fallback for your chosen langauge.
Every time a translation file is loded, the plugin will than load the fallback, if a translation for the originally chosen language was not found.

A list of all of my plugins can be found on the [WordPress Plugin page](http://kau-boys.com/wordpress-plugins "WordPress Plugins") on my blog [kau-boys.com](http://kau-boys.com). 

## Screenshots ##

### 1. Screenshot of the settings page ###
![Screenshot of the settings page](https://raw.githubusercontent.com/2ndkauboy/backend-localization/master/assets/screenshot-1.png)


## Installation ##

Install the plugin as usual from the plugins page on your dashboard (by search or uploaded ZIP file) or upload it with FTP. You should than choose your fallback lanugage in "Settings > General".



## Frequently Asked Questions ##

### Does this plugin only work with formal German? ###

No. You can choose any fallback language that fits your orignial language. Think about fallbacks from "Spanish (Mexico)" to "Spanish".

### Do I need to manually install the fallback language? ###

No. The plugin will automatically download the fallback language if it is not already installed.

   
## Change Log ##

### 1.0.0 ###
* Intitial release of the plugin
* Time invested for this release: 180min