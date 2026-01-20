=== Get Use APIs - JSON Content Importer ===
Contributors: berkux
Tags: api, json, xml, csv, data
Requires at least: 5.3
Requires PHP: 7.0
Tested up to: 6.9
Stable tag: 2.0.8
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Connects an API to WordPress: Get API-data (JSON, XML, CSV...), show it with a Shortcode, a JCI Block or PHP.  Generate a template with the JCI Block

== Description ==

The simplest method to link WordPress with external APIs

JCI simplifies the task of retrieving data from a third-party REST API. The returned JSON-, XML, CSV, TXT-data can be neatly arranged and showcased on your WordPress site via a shortcode, a JCI Block or PHP. The plugin can generate a template for the data display.

20 seconds: Load API and display data

https://www.youtube.com/watch?v=RBlrAUVywAk

7 minutes: Basic intro to JCI free

https://www.youtube.com/watch?v=SDUj1teNG2s

### Examples, Preview

Check out the live demonstration to explore how we're interfacing with various APIs
[LIVE PREVIEW - WordPress and a external API](https://api.json-content-importer.com/free-jci-plugin-example/wetter/)

[Video: How to use the Wikipedia API](https://www.youtube.com/watch?v=GJGBPvaKZsk)

### Main Plugin Features

* Low code usage: Save time and avoid coding using an API
* Insert an API-URL into Step 1: Data is loaded, and and displayed. Save it as API-Access-Set.
* Use the API-Access-Set in Step 2 to create a Shortcode
* Optional for maximum flexibility: Use the API-Access-Set n PHP code
* Insert an API-URL into the JCI Block: Data is loaded, and a template can be generated to display the data.
* Easy to start: Check Installation - Is your WordPress ready for JCI? Most probably!
* Basic Settings: Check SSL, Cacher, Gutenberg and Authentication
* ContactForm7 Integration: Populate CF7 forms with JSON data and forward CF7-submitted data to an API.
* Use the JCI cacher to avoid many API requests
* JCI-Block :  Use the simple JCI Block interface to get data! Query the API and check the response. Then utilize the simple template generator inside the JCI Block to use the data!
* Contact Form 7 Integration: Fill form and submit to API

### Documentation

There's a wealth of resources and support at your fingertips. Explore the articles listed below to begin your journey:

* [JCI Manual](https://doc.json-content-importer.com/)
* [Step 1: Access the Data](https://doc.json-content-importer.com/json-content-importer/step-1-data-access/)
* [Step 2: Using the Data](https://doc.json-content-importer.com/json-content-importer/step-2-data-usage/)
* [Shortcode – Basic structure](https://doc.json-content-importer.com/json-content-importer/shortcode-basic-structure/)
* [Contact Form 7 Integration](https://doc.json-content-importer.com/json-content-importer/pro-contactform7/)
* [Free JCI Plugin: Overview Videos](https://doc.json-content-importer.com/json-content-importer/free-overview-videos/)
* [Free JCI Plugin: Getting Started](https://doc.json-content-importer.com/json-content-importer/basic-start/)

### You need more?

= JSON Content Importer PRO =
Both the free and PRO JCI Plugins serve the same purpose: retrieving data, transforming it, and publishing the results.
However, while the free Plugin can only handle basic challenges, the PRO JCI Plugin offers nearly full control over WordPress, the database, and applications.
[Compare: Free vs. JCI PRO Plugin](https://json-content-importer.com/compare/)

PRO features:
* application building by creating a searchform and connect it to a JSON-API in the background: pass GET-Variables to use a dynamic JSON-Feed-URL ("talk to API / webservice")
* much better and more flexible templateengine: twig
* create and use Custom Post Types
* store Templates independent of pages
* more Shortcode-Parameters
* executing Shortcodes inside a template
* more features...

== Frequently Asked Questions ==

= Help! I need more information! =
[Check the JCI manual, please](https://doc.json-content-importer.com)

= Where to start?
Give it a try: The JCI plugin's block comes with a simple, easy-to-understand example. This lets you learn how JCI works without any risk.

= What does this plugin do? =
This plugin enables you to insert a WordPress shortcode or a Gutenberg Block within any page, post, or Custom Post Type (CPT). This facilitates the retrieval of data from an API, which you can then manipulate, such as converting it to HTML for display purposes.
Connecting an API to WordPress offers enhanced content management, extended functionality, personalization and scalability. It enables real-time data display, third-party service integration, and process automation, making your website dynamic and powerful.
The plugin parses almost any JSON-feed and allows you to display all data on your website: Import data from an API or Webservice to display it on your website.

= How can I make sure the plugin works? =
Select the 'JSON Content Importer' option from the Admin Menu. Upon doing so, you'll be presented with various tabs, one of which is labeled 'Check Installation.' Ensure all the tests conducted here are successfully passed for the optimal functioning of the plugin.
Then use the Shortcodes from Tab "Step 1: Get data" and if successful "Step 2: Use data". If that does not work, check Tab "Support".

= Where is this plugin from? =
This plugin is made in munich, bavaria, germany!
Famous for Oktoberfest, FC Bayern Munich, AllianzArena, TUM, BMW, Siemens, seas, mountains and much more...


### Basic structure of a JCI Shortcode (use the JCI Block to generate such a shortcode and a template):
= Shortcode =
'[jsoncontentimporter
* url="http://...json"
* numberofdisplayeditems="number: how many items of level 1 should be displayed? display all: leave empty or set -1"
* urlgettimeout="number: who many seconds for loading url till timeout?"
* basenode="starting point of datasets, the base-node in the JSON-Feed where the data is"
* oneofthesewordsmustbein="default empty, if not empty keywords spearated by ','. At least one of these keywords must be in the created text (here: text=code without html-tags)"
* oneofthesewordsmustbeindepth="default: 1, number: where in the JSON-tree oneofthesewordsmustbein must be?"
]
This is the template:
Any HTML-Code plus "basenode"-datafields wrapped in "{}"
{subloop:"basenode_subloop":"number of subloop-datasets to be displayed"}
Any HTML-Code plus "basenode_subloop"-datafields wrapped in "{}". If JSON-data is HTML add "html" flag like "{fieldname:html}"
{/subloop:"basenode_subloop"}
[/jsoncontentimporter]'

* templates like "{subloop-array:AAAA:10}{text}{subloop:AAAA.image:10}{id}{/subloop:AAAA.image}{/subloop-array:AAAA}" are possible:
one is the recursive usage of "subloop-array" and "subloop".
the other is "{subloop:AAAA.image:10}" where "AAAA.image" is the path to an object. This is fine for some JSON-data.

= Some special add-ons for datafields =
* "{street:purejsondata}": Default-display of a datafield is NOT HTML, but HTML-Tags are converted : use this to use really the pure data from the JSON-Feed
* "{street:html}": Default-display of a datafield is NOT HTML: "&lt;" etc. are converted to "&amp,lt;". Add "html" to display the HTML-Code as Code.
* "{street:htmlAndLinefeed2htmlLinefeed}": Same as "{street:html}" plus "\n"-Linefeeds are converted to HTML-Linebreak
* "{street:ifNotEmptyAddRight:,}": If datafield "street" is not empty, add "," right of datafield-value. allowed chars are: "a-zA-Z0-9,;_-:&lt;&gt;/ "
* "{street:html,ifNotEmptyAddRight:extratext}": you can combine "html" and "ifNotEmptyAdd..." like this
* "{street:purejsondata,ifNotEmptyAddLeftRight:LEFT##RIGHT##}": If datafield "street" is not empty, add text on the left and right
* "{street:ifNotEmptyAdd:,}": same as "ifNotEmptyAddRight"
* "{street:ifNotEmptyAddLeft:,}": If datafield "street" is not empty, add "," left of datafield-value. allowed chars are: "a-zA-Z0-9,;_-:&lt;&gt;/ "
* "{locationname:urlencode}": Insert the php-urlencoded value of the datafield "locationname". Needed when building URLs

= Update 2.0.8 =
Please check the following:
In the "Check Installation" section of this plugin’s options, it shows whether the PHP extension **mbstring** is active on your WordPress server’s PHP installation.
* If mbstring is active, this update is not important for you.
* If mbstring is NOT active:
  After updating, please verify that the display of API data is still correct.
  If you display HTML or JavaScript code from the API data **without** using `:html` or `:purejsondata`, this code should now be displayed as source code.
  This means that HTML code will not be interpreted and JavaScript code will not be executed.
  If this is the intended behavior, please add `:purejsondata` to your JCI template, for example: `{apinodevalue:purejsondata}`
* Background:
If the PHP extension **mbstring** is not active on your WordPress server (it usually is, but not on all servers), security measures that would display API data containing HTML or JavaScript code as source code were not active.
Only by adding `:html` or `:purejsondata` could you explicitly allow the code to be executed, which should only be done with trusted APIs.
Otherwise, malicious code could be injected via the API.


== Screenshots ==  
1. Welcome to JCI! Thank you!
2. Check your JCI installation and its requirements.
3. Configure your JCI settings: SSL? Cache? Gutenberg?
4. Step 1: Retrieve data. Highly recommended: Use the JCI Block.
5. Step 2: Use data. Generate a template with the JCI Block and try it out.
6. Locate the JCI Block.
7. JCI Block: Welcome to the JCI Block. Familiarize yourself with the JSON example.
8. JCI Block: Turn debug mode on to see what is happening.
9. JCI Block: Generate a template from JSON.
	
== Changelog ==
= 2.0.8 =
* Security Issue Fixed, importaint when mbstring is not active or Contributors use the JCI shortcode
* Plugin Options, Tab "Check Installation": Added check on "mbstring and mb_check_encoding"
* OK: Plugin ok with "Plugin Check (PCP) 1.8.0"
* OK: Plugin ok with WordPress 6.9

= 2.0.7 =
* ADDED: New settings for the WordPress “Contributor” role – starting with this version, contributors can no longer request URLs or APIs. If a WordPress contributor should still be allowed to do so, an option in the “Basic Settings” can be enabled to grant this permission.

= 2.0.6 =
* BUGFIX: Handling of Form-Data at Step 1 
* REMOVED: When generating a template from the JSON data, “:datetime” was previously inserted, where applicable, to show how date and time can be formatted. Unfortunately, automatic detection of date and time values isn’t very reliable, so this feature has been removed.
* REMOVED: In step 1, the loaded JSON is shown as a tree. An unnecessary JS library was being loaded; this has been removed.

= 2.0.5 =
* BUGFIX: Improved sanitizing Step 1, Part 2

= 2.0.4 =
* BUGFIX: Improved sanitizing Step1
* REMOVED: The plugin’s bundled translations have been removed because translations are now provided via WordPress.org.
* OK: Plugin ok with "Plugin Check Plugin" Version 1.6.0

= 2.0.3 =
* BUGFIX: If JSON contains numbers as values, the plugin attempts to convert them to strings. This can cause issues with certain JSON structures. Now, this conversion is only performed if the result remains valid JSON.

= 2.0.2 =
* BUGFIX: SSL-Verification 
* Plugin ok with WordPress 6.7.2
* Plugin ok with "Plugin Check (PCP) 1.4.0"

= 2.0.1 =
* BUGFIX: CSS in the WP-Backend 

= 2.0.0 =
* NEW: Create an API-Access-Set - fill form with the API-URL. select data-tye (JSON, XML, CSV) etc. Test API response and you're ready to use the data
* NEW: Use the API-Access-Set in Step 2: Generate a Shortcode for the data. The generatated Shortcode has all API-data, reduce it to your needs.
* NEW: Use the API-Access-Set with the PHP function jcifree_getjson("NAME_OF_API_ACCESS_SET");
* Plugin ok with WordPress 6.7.1
* Plugin ok with "Plugin Check (PCP) 1.3.1"


== Upgrade Notice ==
= 2.0.8 =
* Security Issue Fixed, importaint when mbstring is not active
* Plugin Options, Tab "Check Installation": Added check on "mbstring and mb_check_encoding"
* OK: Plugin ok with "Plugin Check (PCP) 1.8.0"
* OK: Plugin ok with WordPress 6.9