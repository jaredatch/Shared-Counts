=== Shared Counts - Social Media Share Buttons ===
Contributors: jaredatch, billerickson
Tags: sharing, share buttons, social buttons, share counts, social, facebook, linkedin, pinterest, twitter
Requires at least: 4.6
Tested up to: 5.2
Stable tag: 1.3.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shared Counts adds social media sharing buttons that look great and keep your site loading fast.

== Description ==

Shared Counts is a WordPress social media share buttons plugin. Sharing buttons increase traffic and engagement by helping readers share your posts and pages to their friends on social media.

**Included Buttons**
- Facebook Share Button
- Pinterest Pin Button
- Yummly Button
- Twitter Tweet Button (using the third-party Twitcount.com API)
- Email Sharing (with reCAPTCHA support to prevent abuse)
- Share Count Total
- Print Button
- LinkedIn Share Button

Facebook, Pinterest, Yummly, and Twitter buttons support social count display and tracking.

### Styling and Display
We include many share button styling options, so you can pick the perfect look for you site. Additionally, you can automatically insert the share buttons before and/or after the post content. Want granular or manual control? No problem! You can also use the `[shared_counts]` shortcode to insert them inside your content as you see fit.

### HTTP Recovery / Upgrading to HTTPS
We provide a one-click option to retrieve both HTTP and HTTPS share counts, ensuring you don't lose your share counts when upgrading your website to HTTPS.

### GDPR
Unlike other social sharing tools, this plugin does not use cookies, tracking scripts, or store any user data.

### Performance
Shared Counts was created with site performance in mind, even at large scale. It is used on several large websites that get tens of millions of page views each month. Our unique and creative caching methods have a minimal affect on site overhead. Leveraging the SharedCount.com API, we can retrieve (almost) all share counts in a single request.

### Developers
Additionally, Shared Counts was built to be developer friendly! We provide very liberal usage of hooks and filters. Everything is customizable and the possibilities are near limitless. Unlike other plugins all data (counts) are stored and cached in post_meta which makes it easy to access for extending (e.g. fetch top 10 most shared posts on your site). We have extensive documentation on [our website](https://sharedcountsplugin.com/) and we're also on [GitHub](https://github.com/jaredatch/Shared-Counts/).

### Add On Plugins
- [Shared Counts - Pinterest Image](https://github.com/billerickson/Shared-Counts-Pinterest-Image) - Upload a separate image for Pinterest sharing
- [Shared Counts - Cache Status](https://github.com/billerickson/Shared-Counts-Cache-Status) - Build and check the status of the Shared Counts cache

### Customization
For details on this please see the [Shared Counts website](https://sharedcountsplugin.com/) and our [GitHub wiki](https://github.com/jaredatch/Shared-Counts/wiki/).

### Bugs
If you find an bug or problem, please let us know by [creating an issue](https://github.com/jaredatch/Shared-Counts/issues?state=open).

### Contributions
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

If you would like to include Twitter share counts, you can sign up for a free account at [twitcount.com](https://twitcount.com).

If you use the Email share button, we recommend you enable Google's reCAPTCHA to prevent spam. [Sign up here](https://www.google.com/recaptcha/intro/android.html) (free) to get your Site Key and Secret Key.

== Screenshots ==

1. Available styles

== Changelog ==

**1.3.0**
- Added: Filter for changing services used by location: `shared_counts_display_services`.
- Added: Admin bar stats.
- Added: Support for Twitter counts using TwitCount.com, props @robert-gillmer.
- Added: Automatic social share tracking with Google Analytics.
- Added: Add support for `fastcgi_finish_request` when updating counts.
- Added: Specific services can be defined in shortcode via `services` attribute (comma separated).
- Changed: Pass post_id to `needs_updating` method.
- Changed: Removed support for Google+ and StumbleUpon (RIP).
- Changed: Default `letter-spacing` to normal on button labels.
- Changed: Hide Total Counts button if empty and "Hide Empty Counts" setting is enabled.
- Fixed: Pinterest JS API conflict.
- Fixed: Multiple spaces between some CSS classes inside markup.
- Fixed: Twitter URL encoding issue with special characters in text.
- Fixed: Email counts not tracking, props @thartl.
- Fixed: Showing "Preserve HTTP Counts" setting when Count Source is None.
- Fixed: Enabling various settings by default on initial save.
- Fixed: When sorting posts by share count in the admin, posts with zero shares are now included.
- Fixed: Data attributes are filterable.


**1.2.0**
- Added support for [Pinterest Image](https://github.com/billerickson/Shared-Counts-Pinterest-Image) add-on plugin
- "Hide empty counts" checkbox now works correctly
- Pinterest "Pin it" JS no longer modifies our pinterest button
- Metabox is now always visible, allowing you to disable share buttons even if not collecting counts

**1.1.1**
- Internal "prime the pump" method now includes all supported post types. Can be used with [this plugin](https://github.com/billerickson/Shared-Counts-Prime-Cache) to view the status of the cache and mass update posts.
- Improved compatibility with Genesis theme framework.

**1.1.0**
- Added Yummly share count support/tracking.
- Added Proper `rel` tags for share buttons for security and SEO.
- Added caching via transient for Most Shared Content admin dashboard widget.
- Removed code for LinkedIn/Google+ share counts, as they are no longer supported.
- Added available buttons setting description to indicate which buttons support share counts.
- Fixed reCAPTCHA issue in the email sharing modal.
- Fixed encoded characters in the "From Name" email setting.

**1.0.1**
- Email sharing modal can now be closed by clicking outside the modal or pressing the ESC key.
- The minified stylesheet has been rebuilt. It was missing some styles.

**1.0.0**
- Initial release.
