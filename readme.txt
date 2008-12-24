=== Get your plurk ===
Contributors: roga
Donate link: http://blog.roga.tw/
Tags: plurk
Requires at least: 2.1
Tested up to: 2.7
Stable tag: 1.1.3

"Get your Plurk" could get your plurks from www.plurk.com, and show it on your sidebar.
You may enable cache option to save the PHP page gerneration time. 

== Description ==

*  Cache the plurk to a static page, you don't need to read fetch feed every time.
*  Display each plurk's link and post time (or not).
*  Widget support.
*  Externel CSS file.

== Installation ==

* Put the "get-your-plurk" folder to plugin folders.
* Make a cache folder like /wp-content/cache/ if the cache folder is not exists.
* add a cache file named "cache.tmp", now you have a cache file like: "wp-content/cache/gyp-cache.tmp"
* chmod the "gyp-cahce.tmp" file permission to writable (chmod 666 cache.tmp).
* active the plugin.

** command: 
** cd /wp-content
** mkdir cache
** cd cahce
** touch gyp-cache.tmp
** chmod 777 gyp-cache.tmp

== Screenshots ==

1. /tags/1.1.1/screenshot-1.png

== TODO ==

*  fetch other's people plurk and integrated with yours
*  move the cache file to other folder to prevent from wordpress auto-upgrade removing file writable permission.

== Changelog ==

*  1.1.3 - display username or not, move the cache file path, some code improve.
*  1.1.1 - fix some css problem.
*  1.1.0 - plurk looks and feel, and localization config file support. remove the "show link option".
*  1.0.5 - fix some readme error and default options. 
*  1.0.0 - first release.