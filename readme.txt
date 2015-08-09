=== Block Spammers ===
Contributors: sander85
Tags: spam, comments, blocking, ip
Requires at least: 3.5.1
Tested up to: 4.3
Stable tag: trunk
License: CC0
License URI: http://creativecommons.org/publicdomain/zero/1.0/legalcode

Block spammers from submitting comments, by IPs or by bad words.

== Description ==
This plugin allows to block spammers with the following options:

* Block spammers by IPs (supports wildcards).
* Block IPs that have posted comments marked as spam.
* Block comments that contain bad words.

Additional options:

* If comment contains bad words, add the spammers IP into the blacklist.
* When deleting spam, add IPs of spam comments into the blacklist.
* Similar entries in the blacklist are merged automatically.

== Installation ==
1. Upload block-spammers to the /wp-content/plugins/ directory
2. Activate the plugin through the \'Plugins\' menu in WordPress
3. Check and configure the plugin settings through the \'Block Spammers\' menu in WordPress settings

== Changelog ==
= 0.3 =
* Add counter for blocked comments
* Add option to merge similar entries in the blacklist

= 0.2 =
* Enable translations
* Add Estonian translation

= 0.1 =
* Initial release
