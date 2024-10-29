=== Post to Social Media - WordPress to SocialPilot ===
Contributors: n7studios,wpzinc
Donate link: https://www.wpzinc.com/plugins/wordpress-to-socialpilot-pro
Tags: auto publish, auto post, social media automation, social media scheduling, socialpilot
Requires at least: 3.6
Tested up to: 5.3.1
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically share WordPress Pages, Posts or Custom Post Types to Facebook, Twitter and LinkedIn using your SocialPilot (socialpilot.co) account.

== Description ==

WordPress to SocialPilot is a plugin for WordPress that auto posts your Posts, Pages and/or Custom Post Types to your SocialPilot (socialpilot.co) account for scheduled publishing to Facebook, Twitter and LinkedIn.

Supported networks include Facebook, Twitter, LinkedIn and others.

Don't have a SocialPilot account?  [Sign up for free](https://socialpilot.co)

Our [API](https://www.wpzinc.com/documentation/wordpress-to-socialpilot-pro/data/) connects your website to [SocialPilot](https://socialpilot.co). An account with SocialPilot is required.

> #### WordPress to SocialPilot Pro
> <a href="https://www.wpzinc.com/plugins/wordpress-to-socialpilot-pro/" rel="friend" title="WordPress to SocialPilot Pro - Publish to Facebook, Twitter, LinkedIn and Pinterest">WordPress to SocialPilot Pro</a> provides additional functionality:<br />
>
> - **Instagram, Pinterest, Google My Business, Tumblr and VK Support**<br />Post to more social networks<br />
> - **Multiple, Customisable Status Messages**<br />Each Post Type and Social Network can have multiple, unique status message and settings<br />
> - **Separate Options per Social Network**<br />Define different statuses for each Post Type and Social Network<br />
> - **Dynamic Status Tags**<br />Dynamically build status updates with data from the Post, Author, Custom Fields, The Events Calendar, WooCommerce, Yoast and All-In-One SEO Pack<br />
> - **Shortcode Support**<br />Use shortcodes in status updates<br />
> - **Schedule Statuses**<br />Each status update can be posted immediately or scheduled at a specific time<br />
> - **Full Image Control**<br />Choose to display WordPress Featured Images with your status updates<br />
> - **Conditional Publishing**<br />Only send status(es) to SocialPilot based on Post Author(s), Taxonomy Term(s) and/or Custom Field Values<br />
> - **Override Settings on Individual Posts**<br />Each Post can have its own SocialPilot settings<br />
> - **Repost Old Posts**<br />Automatically Revive Old Posts that haven't been updated in a while, choosing the number of days, weeks or years to re-share content on social media.<br />
> - **Bulk Publish Old Posts**<br />Publish evergreen WordPress content and revive old posts with the Bulk Publish option<br />
> - **The Events Calendar Plugin Support**<br />Schedule Posts to SocialPilot based on your Event's Start or End date, as well as display Event-specific details in your status updates<br />
> - **WooCommerce Support**<br />Display Product-specific details in your status updates<br />
> - **Per-Post Settings**<br />Override Settings on Individual Posts: Each Post can have its own SocialPilot settings<br />
> - **Full Image Control**<br />Choose to display the WordPress Featured Image with your status updates, or define up to 4 custom images for each Post.<br />
> - **WP-Cron and WP-CLI Compatible**<br />Optionally enable WP-Cron to send status updates via Cron, speeding up UI performance and/or choose to use WP-CLI for reposting old posts<br />
> - **Support**<br />Access to one on one email support<br />
> - **Documentation**<br />Detailed documentation on how to install and configure the plugin<br />
> - **Updates**<br />Receive one click update notifications, right within your WordPress Adminstration panel<br />
> - **Seamless Upgrade**<br />Retain all current settings when upgrading to Pro<br />
>
> [Upgrade to WordPress to SocialPilot Pro](https://www.wpzinc.com/plugins/wordpress-to-socialpilot-pro/)

= Support =

We will do our best to provide support through the WordPress forums. However, please understand that this is a free plugin, 
so support will be limited. Please read this article on <a href="http://www.wpbeginner.com/beginners-guide/how-to-properly-ask-for-wordpress-support-and-get-it/">how to properly ask for WordPress support and get it</a>.

If you require one to one email support, please consider <a href="http://www.wpzinc.com/plugins/wordpress-to-socialpilot-pro" rel="friend">upgrading to the Pro version</a>.

= Data =

We connect directly to your SocialPilot (socialpilot.co) account, via their API, to:
- Fetch your social media profile names and IDs, 
- Send your WordPress Posts to one or more of your social media profiles.  The profiles and content sent will depend on the plugin settings you have configured.

We connect to our own [API](https://www.wpzinc.com/documentation/wordpress-to-socialpilot-pro/data/) to pass the following requests through to SocialPilot:
- Connect our Plugin to SocialPilot, when you click the Authorize button (this obtains an access token from SocialPilot, once you have approved authorization)

Both of these are done via our own API, to ensure that no secret data (such as oAuth client secret keys) are included in this Plugin's code or made public.

We **never** store any information on our web site or API during this process.

= WP Zinc =
We produce free and premium WordPress Plugins that supercharge your site, by increasing user engagement, boost site visitor numbers
and keep your WordPress web sites secure.

Find out more about us at <a href="https://www.wpzinc.com" title="Premium WordPress Plugins">wpzinc.com</a>

== Installation ==

1. Upload the `wp-to-socialpilot` folder to the `/wp-content/plugins/` directory
2. Active the WordPress to SocialPilot plugin through the 'Plugins' menu in WordPress
3. Configure the plugin by going to the `WordPress to SocialPilot` menu that appears in your admin menu

== Frequently Asked Questions ==

== Screenshots ==

1. Settings Screen when Plugin is first installed.
2. Settings Screen when SocialPilot is authorized.
3. Settings Screen showing available options for Posts.

== Changelog ==

= 1.0.3 =
* Added: Log: Option to filter Logs by Request Sent Date. See Docs: https://www.wpzinc.com/documentation/wordpress-to-socialpilot-pro/log-settings/#filtering-logs
* Added: Log: Provide solutions to common issues
* Added: Log: New Log screen with filters and searching to view Status Logs across all Posts for all actions (Publish, Update, Repost, Bulk Publish).  See Docs: https://www.wpzinc.com/documentation/wordpress-to-hootsuite-pro/logs/
* Added: Log: Improved messages explaining why a Post is not sent to Hootsuite
* Added: Log: Use separate database table for storing Plugin Status Logs instead of Post Meta, for performance

= 1.0.2 =
* Added: Status: Tags: Content and Excerpt Tag options with Word or Character Limits
* Added: Gutenberg: Better detection to check if Gutenberg is enabled
* Added: Gutenberg: Better detection to check if Post Content contains Gutenberg Block Markup
* Fix: Status: Removed loading of unused tags.js dependency for performance
* Fix: Status: {content} would return blank on WordPress 5.1.x or older

= 1.0.1 =
* Added: Settings: Display notice if the SocialPilot account does not have any social media profiles attached to it
* Added: Status: Textarea will automatically expand based on the length of the status text. Fixes issues for some iOS devices where textarea scrolling would not work
* Fix: Status: {content} and {excerpt} tags always return the full content / excerpt, which can then be limited using word / character limits
* Fix: Publish: Display errors and log if authentication fails, or profiles cannot be fetched
* Fix: Publish: Add checks to prevent duplicate statuses being sent when a Page Builder (Elementor) fires wp_update_post multiple times when publishing
* Fix: Status: Strip additional unwanted newlines produced by Gutenberg when using {content}
* Fix: Status: Convert <br> and <br /> in Post Content to newlines when using {content}
* Fix: Status: Trim Post Content when using {content}

= 1.0.0 =
* First release.

== Upgrade Notice ==

