# Change Log
All notable changes to this project will be documented in this file, formatted via [this recommendation](http://keepachangelog.com/).

## [1.5.0] = 2024-01-17
### Changed
- Replaced server side email with a mailto link to remove the possibility of spam, see #131
- Updated the Twitter logo to X, see #129
- Removed jQuery dependency, see #113
- Use `esc_url()` for sharing links, see #130

## [1.4.1] = 2022-12-19
### Fixed
- Fix PHP 8 warning, see #123

## [1.4.0] = 2022-05-20
### Added
- AMP support, see #75
- Only update share count if larger than currently saved service count (prevents zero counts when API is down), see #51
- Accessibility improvements around email share modal, see #112

### Changed
- Allow theme file editor to work if Count Source is set to 'none', see #120
- Updated native Facebook counts to work with new Facebook API, see #98
- Dashboard widget only appears if there are posts with share counts, see #83
- Ensure input fields are full width in email modal, see #77
- Fixed edge case where buttons show 0 count when source set to "None", see #81
- Improved fancy style in small areas, see #58

## [1.3.0] = 2019-05-13
### Added
- Filter for changing services used by location: `shared_counts_display_services`, see #69.
- Admin bar stats, see #32.
- Support for Twitter counts using TwitCount.com, props @robert-gillmer, see #62.
- Automatic social share tracking with Google Analytics, see #50.
- Add support for `fastcgi_finish_request` when updating counts, see #12.
- Specific services can be defined in shortcode via `services` attribute (comma separated), see #69.


### Changed
- Pass post_id to `needs_updating` method, see #74.
- Removed support for Google+ and StumbleUpon (RIP).
- Default `letter-spacing` to normal on button labels, see #56.
- Hide Total Counts button if empty and "Hide Empty Counts" setting is enabled, see #44.

### Fixed
- Pinterest JS API conflict, see #34.
- Multiple spaces between some CSS classes inside markup, see #64.
- Twitter URL encoding issue with special characters in text, see #54.
- Email counts not tracking, props @thartl, see #67.
- Showing "Preserve HTTP Counts" setting when Count Source is None.
- Enabling various settings by default on initial save.
- When sorting posts by share count in the admin, posts with zero shares are now included, see #76.
- Data attributes are filterable, see #73

## [1.2.0] = 2018-05-23
### Added
- Support for [Pinterest Image](https://github.com/billerickson/Shared-Counts-Pinterest-Image) add-on plugin.

### Fixed
- "Hide empty counts" checkbox now works correctly.
- Pinterest "Pin it" JS no longer modifies our pinterest button, see #34.
- Metabox is now always visible, allowing you to disable share buttons even if not collecting counts, see #33.

## [1.1.1] = 2018-04-04
### Fixed
- Internal "prime the pump" method now includes all supported post types. Can be used with [this plugin](https://github.com/billerickson/Shared-Counts-Prime-Cache) to view the status of the cache and mass update posts.
- Improved compatibility with Genesis theme framework.

## [1.1.0] = 2018-03-21
### Added
- Yummly share count support/tracking.
- Proper `rel` tags for share buttons for security and SEO.
- Caching via transient for Most Shared Content admin dashboard widget.

### Changed
- Removed code for LinkedIn/Google+ share counts, as they are no longer supported.
- Available buttons setting description to indicate which buttons support share counts.

### Fixed
- reCAPTCHA not working correctly in the email sharing modal.
- Encoded characters in the "From Name" email setting.

## [1.0.1] = 2018-02-21
### Changed
- Email sharing modal can now be closed by clicking outside the modal or pressing the ESC key.
- The minified stylesheet has been rebuilt. It was missing some styles.

## [1.0.0] = 2018-02-09
- Initial Release.
