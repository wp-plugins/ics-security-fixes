=== ICS Security Fixes ===
Contributors: vladimir_kolesnikov
Donate link: http://blog.sjinks.pro/feedback/
Tags: security, vulnerability
Requires at least: 2.3
Tested up to: 3.1
Stable tag: 0.4

The plugin tries to fix known WordPress vulnerabilities for older WordPress versions.
Warning: For PHP 5 only.

== Description ==

Like any other software WordPress does have bugs and security vulnerabilities. They get fixed when the developers become aware of them.
However, sometimes several WordPress releases are vulnerable. The problem is that the developers do not backport security updates to older WordPress releases.
As a result, those who do not want to upgrade their WordPress installations (for example, because newer WP releases tend to consume more system resources and
upgrade may result in having to use more expensive gosting) remain vulnerable.

ICS Security Fixes is the plugin for those who do not upgrade their WordPress: the plugin tries to fix known WordPress vulnerabilities
at that remaining compatible with as many old WordPress releases as possible.
And though it cannot fix all known vulnerabilities, it makes older WordPress installations more secure.

== Installation ==

1. Upload `ics-security-fixes` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Have fun :-)

== Frequently Asked Questions ==

None yet. Be the first to ask.

== Warning ==

This plugins requires that PHP 5 be installed. PHP 4 is not supported.

== Screenshots ==

No screenshots, as the plugin does not require user intervention :-) It just works.

== Upgrade Notice ==

It is strongly recommended that you upgrade to the latest version of the plugin. New versions bring new bug fixes and hopefully do not put in any new bugs.

== Changelog ==
= 0.4 =
* Backport of r17393, r17387, r17400, r17406 from 3.0.5.

= 0.3 =
* First stable version (thanks to [Sergey Biryukov](http://profiles.wordpress.org/sergeybiryukov/)) for the patches
* [SA23621](http://secunia.com/advisories/23621/) is partially fixed (it remains not fixed even in the current WP)
* Hides versions of the used scripts and stylesheets
* Due to numerous requests, the plugin hides All in One SEO Pack's version

= 0.2 =
* Bug fixes
* Forcefully sets the default CSS/JS version to 0.0 (by default it matches the WordPress version)

= 0.1 =
* disables trackback/pingback whitelisting (fixed in 3.0.2, exists since 1.x)
* tries to protect against SQL truncation attack during signup
* stops SQL injection attack when processing trackbacks
* CVE-2008-4769
* closes old slug redirect vulnerability
* tries to fix redirection bug to file:// and scp:// (you must have really old cURL if you are hit with this bug)
* stops SQL injection attack in wp_insert_attachment()
* stupid trick to fight the feed replacement vulnerability
* PRNG attack protection;
* tries to fix 2.7.x/2.8.x admin remote code execution
* fixes 2.5 Cookie Integrity Protection Vulnerability
* fixes 2.5.1 reset password bug
