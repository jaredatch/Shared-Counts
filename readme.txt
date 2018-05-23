=== Shared Counts ===
Contributors: jaredatch, billerickson
Tags: sharing, share buttons, social buttons, share counts, social, facebook, linkedin, pinterest, stumbleupon, twitter
Requires at least: 4.6
Tested up to: 4.9
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shared Counts adds social sharing buttons that look great and keep your site loading fast.

== Description ==

We include many styling options, and you can automatically insert the buttons before and/or after the post content. You can also use the `[shared_counts]` shortcode to insert them inside the content.

We provide a one-click option to retrieve both HTTP and HTTPS share counts, ensuring you don't lose your share counts when upgrading your website to HTTPS.

**GDPR Compliant:** Unlike other social sharing tools, this plugin does not use cookies, tracking scripts, or store any user data.

Shared Counts was created with site performance in mind, even at large scale. It is used on several large websites that get tens of millions of page views each month. Our unique and creative caching methods have a minimal affect on site overhead. Leveraging the SharedCount.com API, we can retrieve (almost) all share counts in a single request.

Additionally, Shared Counts was built to be developer friendly! We provide very liberal usage of hooks and filters. Everything is customizable and the possibilities are near limitless. Unlike other plugins all data (counts) are stored and cached in post_meta which makes it easy to access for extending (e.g. fetch top 10 most shared posts on your site).

**Included Buttons**
- Facebook
- Pinterest
- Yummly
- Twitter (using the third-party NewShareCounts.com API)
- StumbleUpon
- Email sharing (with reCAPTCHA support to prevent abuse)
- Share count totals
- Print*
- LinkedIn*
- Google+*

* denotes button/service does not support share count tracking.

**Add On Plugins**
- [Shared Counts - Pinterest Image](https://github.com/billerickson/Shared-Counts-Pinterest-Image) - Upload a separate image for Pinterest sharing
- [Shared Counts - Cache Status](https://github.com/billerickson/Shared-Counts-Cache-Status) - Build and check the status of the Shared Counts cache


**Customization**
For details on this please see [the wiki](https://github.com/jaredatch/Shared-Counts/wiki/).

**Bugs**
If you find an bug or problem, please let us know by [creating an issue](https://github.com/jaredatch/Shared-Counts/issues?state=open).

**Contributions**
Contributions are welcome!

1. Open an [Issue](https://github.com/jaredatch/Shared-Counts/issues) on GitHub.
2. Fork Shared Counts on GitHub.
3. Create a new branch off of `develop`; branch name should be `issue/###` to reference the issue.
4. When committing, reference your issue and provide notes/feedback.
5. Send us a Pull Request with your bug fixes and/or new features.

== Installation ==
1. Download the plugin [from GitHub.](https://github.com/jaredatch/Shared-Counts/archive/master.zip) or from [WordPress.org](https://wordpress.org/plugins/shared-counts/).
2. Activate plugin.
3. Go to Settings > Shared Counts to configure.

We recommend you sign up for a free account at [SharedCount.com](https://sharedcount.com), which lets you receive share counts from all services (except Twitter) with a single API query. Alternatively, you can select "Native" as the count source and select which services you'd like to query. If you select all 5 native service queries, then you will have 5 separate API queries every time share counts are updated.

If you would like to include Twitter share counts, you can sign up for a free account at [NewShareCounts.com](https://newsharecounts.com).

If you use the Email share button, we recommend you enable Google's reCAPTCHA to prevent spam. [Sign up here](https://www.google.com/recaptcha/intro/android.html) (free) to get your Site Key and Secret Key.

== Screenshots ==

1. Available styles

== Changelog ==

**Version 1.2.0**
- Added support for [Pinterest Image](https://github.com/billerickson/Shared-Counts-Pinterest-Image) add-on plugin
- "Hide empty counts" checkbox now works correctly
- Pinterest "Pin it" JS no longer modifies our pinterest button
- Metabox is now always visible, allowing you to disable share buttons even if not collecting counts

**Version 1.1.1**
- Internal "prime the pump" method now includes all supported post types. Can be used with [this plugin](https://github.com/billerickson/Shared-Counts-Prime-Cache) to view the status of the cache and mass update posts.
- Improved compatibility with Genesis theme framework.

**Version 1.1.0**
- Added Yummly share count support/tracking.
- Added Proper `rel` tags for share buttons for security and SEO.
- Added caching via transient for Most Shared Content admin dashboard widget.
- Removed code for LinkedIn/Google+ share counts, as they are no longer supported.
- Added available buttons setting description to indicate which buttons support share counts.
- Fixed reCAPTCHA issue in the email sharing modal.
- Fixed encoded characters in the "From Name" email setting.

**Version 1.0.1**
- Email sharing modal can now be closed by clicking outside the modal or pressing the ESC key.
- The minified stylesheet has been rebuilt. It was missing some styles.

**Version 1.0.0**
- Initial release.
