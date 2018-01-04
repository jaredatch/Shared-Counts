# [Shared Counts](https://wordpress.org/plugins/shared-counts/) #

![Plugin Version](https://img.shields.io/wordpress/plugin/v/shared-counts.svg?style=flat-square) ![Total Downloads](https://img.shields.io/wordpress/plugin/dt/shared-counts.svg?style=flat-square) ![Plugin Rating](https://img.shields.io/wordpress/plugin/r/shared-counts.svg?style=flat-square) ![WordPress Compatibility](https://img.shields.io/wordpress/v/shared-counts.svg?style=flat-square) ![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg?style=flat-square)

**Contributors:** jaredatch, billerickson  
**Tags:** sharing, share buttons, social buttons, share counts, social, facebook, linkedin, pinterest, stumbleupon, twitter  
**Requires at least:** 4.6  
**Tested up to:** 4.7.0  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

Shared Counts is a WordPress plugin that leverages SharedCount.com API to quickly retrieve, cache, and display various social sharing counts.

## Description

We provide enough out-of-the-box options for basic/most use cases, but don't include the kitchen sink to keep things lean and bloat-free.

Share Counts that can be tracked and displayed are:

- Facebook
- Pinterest
- LinkedIn
- Twitter (using the third-party NewShareCounts.com API)
- StumbleUpon
- Email sharing (with reCAPTCHA support to prevent abuse)
- Share count totals

Shared Counts was created with site performance in mind, even at large scale. It is used on several large that get tens of millions of page views each month. Our unique and creative caching methods have a minimal affect on site overhead. Leveraging the SharedCount.com API, we can retrieve (almost) all share counts in a single request.

Additionally, Shared Counts was built to be developer friendly! We provide _very liberal_ usage of hooks and filters. Everything is customizable and the possibilities are near limitless. Unlike other plugins all data (counts) are stored and cached in `post_meta` which makes it easy to access for extending (e.g. fetch top 10 most shared posts on your site).

We also provide a one-click method to restore and preserve non-HTTPS share counts which is extremely helpful for existing sites that have switched to HTTPS. Additional arbitrary URLs can also be tracked in cases where the post slug has changed or redirects are used.

## This Repo ##
Master branch is always stable and contains latest releases. Development occurs in the develop branch while large features/changes are contained in dedicated branches. For reporting bugs or contributing, see more additional information below.

## Installation ##
1. Download the plugin [from GitHub.](https://github.com/jaredatch/Shared-Counts/archive/master.zip) or from [WordPress.org](https://wordpress.org/plugins/shared-counts/).
2. Activate plugin.
3. Go to Settings > Shared Counts to configure.

## Customization ##
For details on this please see [the wiki](https://github.com/jaredatch/Shared-Counts/wiki/Customizations).

## Bugs ##
If you find an bug or problem, please let us know by [creating an issue](https://github.com/jaredatch/Shared-Counts/issues?state=open).

## Contributions ##
Contribututions are welcome!

1. Open an [Issue](https://github.com/jaredatch/Shared-Counts/issues) on GitHub.
2. Fork Shared Counts on GitHub.
3. Create a new branch off of `develop`; branch name should be `issue/###` to reference the issue.
4. When committing, reference your issue and provide notes/feedback.
5. Send us a Pull Request with your bug fixes and/or new features.
