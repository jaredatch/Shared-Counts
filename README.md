# [Shared Counts](https://wordpress.org/plugins/shared-counts/) #

![Plugin Version](https://img.shields.io/wordpress/plugin/v/shared-counts.svg?style=flat-square) ![Total Downloads](https://img.shields.io/wordpress/plugin/dt/shared-counts.svg?style=flat-square) ![Plugin Rating](https://img.shields.io/wordpress/plugin/r/shared-counts.svg?style=flat-square) ![WordPress Compatibility](https://img.shields.io/wordpress/v/shared-counts.svg?style=flat-square) ![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg?style=flat-square)

**Contributors:** jaredatch, billerickson  
**Tags:** sharing, share buttons, social buttons, share counts, social, facebook, linkedin, pinterest, stumbleupon, twitter  
**Requires at least:** 4.6  
**Tested up to:** 4.9  
**Stable tag:** 1.2.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

Shared Counts adds social sharing buttons that look great and keep your site loading fast.

We provide a one-click option to retrieve both HTTP and HTTPS share counts, ensuring you don't lose your share counts when upgrading your website to HTTPS.

**GDPR Compliant:** Unlike other social sharing tools, this plugin does not use cookies, tracking scripts, or store any user data.

We include many styling options, and you can automatically insert the buttons before and/or after the post content. You can also use the `[shared_counts]` shortcode to insert them inside the content.

Shared Counts was created with site performance in mind, even at large scale. It is used on several large websites that get tens of millions of page views each month. Our unique and creative caching methods have a minimal affect on site overhead. Leveraging the SharedCount.com API, we can retrieve (almost) all share counts in a single request.

Additionally, Shared Counts was built to be developer friendly! We provide very liberal usage of hooks and filters. Everything is customizable and the possibilities are near limitless. Unlike other plugins all data (counts) are stored and cached in post_meta which makes it easy to access for extending (e.g. fetch top 10 most shared posts on your site).

### Included Buttons
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

### Add On Plugins
- [Shared Counts - Pinterest Image](https://github.com/billerickson/Shared-Counts-Pinterest-Image) - Upload a separate image for Pinterest sharing
- [Shared Counts - Cache Status](https://github.com/billerickson/Shared-Counts-Cache-Status) - Build and check the status of the Shared Counts cache


## Style Options ##

#### Fancy

![fancy](https://d3vv6lp55qjaqc.cloudfront.net/items/001O1T2o0s0a3A2F3D0p/Screen%20Shot%202018-02-09%20at%2010.32.32%20AM.png?X-CloudApp-Visitor-Id=095a13821a9a7633d8999bdb4bf2b94a&v=a0c11008)

#### Slim

![slim](https://d3vv6lp55qjaqc.cloudfront.net/items/363x2P3Y2t0w1g1S2u2C/Screen%20Shot%202018-02-09%20at%2010.33.17%20AM.png?X-CloudApp-Visitor-Id=095a13821a9a7633d8999bdb4bf2b94a&v=473dd2d6)

#### Classic
![classic](https://d3vv6lp55qjaqc.cloudfront.net/items/302h3t3j3z0x3w2l0o0i/Screen%20Shot%202018-02-09%20at%2010.33.53%20AM.png?X-CloudApp-Visitor-Id=095a13821a9a7633d8999bdb4bf2b94a&v=7c71a21a)

#### Block
![block](https://d3vv6lp55qjaqc.cloudfront.net/items/441W3L3j3S3O2P2u3x21/Screen%20Shot%202018-02-09%20at%2010.35.19%20AM.png?X-CloudApp-Visitor-Id=095a13821a9a7633d8999bdb4bf2b94a&v=bad3fa6c)

#### Bar
![bar](https://d3vv6lp55qjaqc.cloudfront.net/items/2R2X2a3g1j0w1L171h1H/Screen%20Shot%202018-02-09%20at%2010.36.51%20AM.png?X-CloudApp-Visitor-Id=095a13821a9a7633d8999bdb4bf2b94a&v=876d7ced)

#### Rounded
![rounded](https://d3vv6lp55qjaqc.cloudfront.net/items/2n2G3j3h161I2I2O1e0L/Screen%20Shot%202018-02-09%20at%2010.44.47%20AM.png?X-CloudApp-Visitor-Id=095a13821a9a7633d8999bdb4bf2b94a&v=116f138b)

#### Buttons
![buttons](https://d3vv6lp55qjaqc.cloudfront.net/items/1u0C1s210Z1L12181J3A/Screen%20Shot%202018-02-09%20at%2010.45.29%20AM.png?X-CloudApp-Visitor-Id=095a13821a9a7633d8999bdb4bf2b94a&v=cffff3cf)

#### Icons
![icons](https://d3vv6lp55qjaqc.cloudfront.net/items/3H1M1e3K0F3K370Q1J1L/Screen%20Shot%202018-02-09%20at%2010.47.16%20AM.png?X-CloudApp-Visitor-Id=095a13821a9a7633d8999bdb4bf2b94a&v=b4d3bc7f)

## Installation ##
1. Download the plugin [from GitHub.](https://github.com/jaredatch/Shared-Counts/archive/master.zip) or from [WordPress.org](https://wordpress.org/plugins/shared-counts/).
2. Activate plugin.
3. Go to Settings > Shared Counts to configure.

We recommend you sign up for a free account at [SharedCount.com](https://sharedcount.com), which lets you receive share counts from all services (except Twitter) with a single API query. Alternatively, you can select "Native" as the count source and select which services you'd like to query. If you select all 5 native service queries, then you will have 5 separate API queries every time share counts are updated.

If you would like to include Twitter share counts, you can sign up for a free account at [NewShareCounts.com](https://newsharecounts.com).

If you use the Email share button, we recommend you enable Google's reCAPTCHA to prevent spam. [Sign up here](https://www.google.com/recaptcha/intro/android.html) (free) to get your Site Key and Secret Key.

## Customization ##
For details on this please see [the wiki](https://github.com/jaredatch/Shared-Counts/wiki/).

## Bugs ##
If you find an bug or problem, please let us know by [creating an issue](https://github.com/jaredatch/Shared-Counts/issues?state=open).

## Contributions ##
Contributions are welcome!

1. Open an [Issue](https://github.com/jaredatch/Shared-Counts/issues) on GitHub.
2. Fork Shared Counts on GitHub.
3. Create a new branch off of `develop`; branch name should be `issue/###` to reference the issue.
4. When committing, reference your issue and provide notes/feedback.
5. Send us a Pull Request with your bug fixes and/or new features.

## This Repo ##
Master branch is always stable and contains latest releases. Development occurs in the develop branch while large features/changes are contained in dedicated branches. For reporting bugs or contributing, see more additional information below.
