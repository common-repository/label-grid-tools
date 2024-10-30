=== LabelGrid Tools ===
Contributors: labelgrid
Donate link: https://labelgrid.com
Tags: record label, artist, music, musician, releases
Requires at least: 5.0.0
Tested up to: 6.7
Stable tag: /trunk/
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

LabelGrid Tools is a plugin for Record Labels, Artists, and Distributors, offering easy music release showcases with advanced promotional tools.

== Description ==

**LabelGrid Tools** is an advanced plugin for **Record Labels**, **Artists**, and **Music Distributors** that allow to showcase music releases with ease providing **advanced pre/post release tools**.

- [Live Website](https://kinphonic.com/) to see all functionalities live.
- [LabelGrid Tools Documentation](https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugin)
- [More informations on LabelGrid Tools](https://labelgrid.com/solutions/music-promotion-marketing-tools/wordpress-plugin-labelgrid-tools/)

**Main Features**

*	**Manage your catalog:** Create Releases along with Artists, Genres and much more. Handle all your Releases, Artists and Free Downloads.
*	**Release Landing Pages - SmartLinks pages:** Landing Pages with Stores Release links, Press Release, Spotify Preview and more. Stop using external websites for your release landing pages and use your own website to gather more visitors. Lite (semplified) pages to improve conversions are available.
*	**Internationalized iTunes links:** Internationalization for **iTunes** and **Apple Music** links, the plugin changes automatically the links to the user's current Country to bring the user directly to the correct national store.
*	**Apple Affiliate Program support:** LabelGrid Tools enable [Apple Affiliate program](https://affiliate.itunes.apple.com/).
*	**Spotify Pre-Save:** Pre-save tracks from any release page. Optionally setup further actions that can have your users follow Playlists, Users or Artists.
*	**Free Download Gates:** Crete new Free Downloads as "Follow to Download" gate type. LabelGrid Tools supports Spotify, Twitter and Soundcloud.

**-- LABELGRID ADVANCED FUNCTIONALITIES --**

**LabelGrid Gate Tools are available only to LabelGrid customers subscribed to any paid plan or as a standalone add-on. [Sign-up here](https://labelgrid.com/pricing/)**

**LABELGRID SYNC:**
LabelGrid Tools can sync automatically Releases, Artists, Genres and Record Labels from LabelGrid databases directly to WordPress thanks to LabelGrid APIs. 
The plugin will sync automatically all catalog twice daily, fetching new Stores Links as they get available. 
No need to worry anymore on updating your website because LabelGrid Tools will do it for you.


**FREE DOWNLOAD GATES:**
Create Free Downloads with "Action-to-download" gates, supporting Spotify, Twitter and Soundcloud.
The application will prompt customers to first "Connect" the requested services and then will automatically perform the configured actions.
Once the customer has connected all the requested services the download will unlock automatically.

Download Gates can also be setup to forward to URLS, so you could "Gate" for example your DEMO submission form page to ask users to follow your socials before being able to submit you content.

**SPOTIFY PRE-SAVES:**
Improve your pre-release sales with a Spotify Pre-Save.

Optionally setup further actions that can have your users follow Playlists, Users or Artists.
  

== Installation ==

Please refer to our Documentation for the complete installation process:
[LabelGrid Tools Documentation](https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugin)

== Frequently Asked Questions ==

Please check our Documentation: 
[LabelGrid Tools Documentation](https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugin)

== Screenshots ==

1. Example website - Release list page
2. Example website - Lite release smartlink page to improve conversions
3. Example website - Lite release smartlink page to improve conversions
4. Add new Release - Tab General
5. Add new Release - Tab Artists
6. Add new Release - Tab Store Links
7. Add new Release - Tab Press Release
8. Add new Release - Tab Spotify (Pre)Save
9. Add new Release - Tab Preferred Labels
10. Add new Artist - Tab General
11. Add new Artist - Media Links
12. Add new Artist - Biography
13. Add new Gate Download - General
14. Add new Gate Download - File
15. Add new Gate Download - Download Settings

== Changelog ==

= 1.3.60 =
* Refactors and improvements for compatibility

= 1.3.59 =
* Fix vulnerabilities in admin for Cross Site Scripting (XSS) (OWASP A3: Injection (2021))

= 1.3.58 =
* Fix issue from LabelGrid content update where the content (release/artist) wasn't correctly updated after being re-imported from LabelGrid Catalog

= 1.3.57 =
* Secure new geolocalization endpoint

= 1.3.56 =
* Minor fix

= 1.3.55 =
* Fix geolocalization for itunes links

= 1.3.54 =
* Update plugins

= 1.3.53 =
* Update plugins

= 1.3.52 =
* Improvements in labelgrid-release-banner shortcode: Added new filter options

= 1.3.51 =
* Wordpress version bump

= 1.3.50 =
* Wordpress version bump

= 1.3.49 =
* Update LabelGrid update routines

= 1.3.48 =
* Minor fixes
* WP compatibility

= 1.3.47 =
* Optimize css loading

= 1.3.46 =
* Update library PHP8 support

= 1.3.45 =
* Fixed monolog exception issue

= 1.3.44 =
* Fixed monolog exception issue

= 1.3.43 =
* Fixed minor log issues

= 1.3.42 =
* Fixed minor log issues

= 1.3.41 =
* Fixed minor log issues

= 1.3.40 =
* Fixed minor log issues

= 1.3.39 =
* Fixed minor log issues

= 1.3.38 =
* Updated PHP 8 dependencies and libraries

= 1.3.37 =
* Tests

= 1.3.36 =
* Libraries update
* Small fix on Custom Services id field

= 1.3.35 =
* Compatibility wordpress 6.1

= 1.3.34 =
* Compatibility wordpress 6.0

= 1.3.33 =
* Fix cached results for releases/artists/banners. Resolve bug of deleted/hidden posts that was showing also if deleted.

= 1.3.32 =
* Plugin update and wordpress 5.9 compatibility

= 1.3.31 =
* Library updates

= 1.3.30 =
* Fix an issue with Custom Services and names with caps

= 1.3.29 =
* Minor improvements

= 1.3.28 =
* Minor improvements

= 1.3.27 =
* Minor improvements

= 1.3.26 =
* Minor improvements

= 1.3.25 =
* Minor improvements on first install

= 1.3.24 =
* Minor improvements & library updates

= 1.3.23 =
* Minor improvements & library updates

= 1.3.22=
* Minor improvements & library updates

= 1.3.21 =
* Bugfix: Release internal caching was not working properly

= 1.3.20 =
* Minor changes in Gate API for Sync

= 1.3.19 =
* Minor improvements

= 1.3.18 =
* Bugfix: YOAST SEO integration can break certain installations.

= 1.3.17 =
* New feature: [labelgrid-release-list-filter] shortcode now also filter by record label.
* New feature: Update css/html releases grid.
* New feature: YOAST SEO integration. While YOAST SEO is installed LabelGrid Tools would optimize the Open Graph tags for Releases and Artists

= 1.3.16 =
* Bugfix: Itunes Affiliate Links was not processing the links correctly

= 1.3.15 =
* Gate functionalities: Presave tab in Releases is now visible only if a valid API key is present.
* Library updates

= 1.3.14 =
* Gate functionalities: LITE templates are now available for any gated download.
* New advanced setting: New field in General Settings -> Advanced to Disable CURLOPT_INTERFACE to avoid issues on shared servers. 

= 1.3.13 =
* Gate functionalities: Youtube Gate functionalities are now disabled due to a change in Youtube API Policies

= 1.3.12 =
* Minor change: Debug

= 1.3.11 =
* Minor change: Debug

= 1.3.10 =
* Minor change: Debug

= 1.3.9 =
* Minor change: Debug

= 1.3.8 =
* Minor change: Debug

= 1.3.7 =
* New feature: Display domain/ip informations to ease API creation

= 1.3.6 =
* Bugfix: Minor fixes: improved transient management

= 1.3.5 =
* Bugfix: Minor fixes: installation activation - default fields in import releases

= 1.3.4 =
* Bugfix: Gate downloads loop while Facebook pixel is active
* Bugfix: Release genres filter
* Compatibility: WordPress 5.7

= 1.3.3 =
* Bugfix: Gate mobile was not working properly in certain network configurations.

= 1.3.2 =
* New feature: New shortcode [labelgrid-release-list-filter] to filter releases by genre. It can be used in any page that is using "labelgrid-release-list" shortcode.
* New feature: Gate Downloads are now fully translable.
* New feature: Added help tooltip on Gate popup
* Edit: Removed Google Play from the Services lists due service closure.
* Bugfix: UI Presave and Gate improvements.
* Bugfix: Custom free download link was not showing on release details.
* Bugfix: Sync button in Toolbar was not handling the first sync error.
* Bugfix: Session management and Gate downloads
* Compatibility: WordPress 5.6
* Compatibility: PHP 8.0

= 1.3.1 =
* Bugfix: Frontend UI bugfixes.
* Bugfix: Default options for 'Default Visible Services/Releases' were not set on first install.
* Bugfix: Release/Artist transient cache was not always updated when expired
* New feature: Change value of field displayed below Artwork, in Release Page, with Release code or Record Label name.
* New feature: New privacy text on Gate wizard.
* New feature: Lite page now shows connected Record Label below the title instead website name.
* Library update

= 1.3 =
* New feature: New LITE Template for Releases: You can add "/lite/" to any release Url and you will get a simplified release download page with only artwork and links. Useful in case of Ads campaigns that require a cleaner page to improve conversions.
* New feature: CUSTOM FACEBOOK EVENTS - Store Links, Gates and Pre-saves now fires custom FB events so you could filter understand and target your audiences thru Facebook ADV. New actions are: GateActionStart, GateConfirmed, PresaveActionStart, ServiceLinkClick
* New feature: The majority of the elements of the plugin are now cached for faster performances.
* Bugfix: Google Analytics Events wasnt firing depending on the Google Analytics implementation. LabelGrid Tools supports only events fired thru Google Analytics GA and is not compatibile with Google Tag Manager.
* Bugfix: UI for Pre-save and Gate Downloads has been updated and now works with "in-app" mobile browsers.
* Release Page/Artist page image optimization: we now use a smaller preview image for featured artists and releases. You need to re-generate your wordpress thrumbnails after this update. The easier way is to install any plugin that "Regenerate thumbnails" available in the Wordpress marketplace.
* Various bugfix

= 1.2.6 =
* New feature: Added new DSP: QQ - KuGou - Anghami - AWA - Boomplay - JioSaavn - Jaxsta. You can add these for Releases in Dashboard->LabelGrid Tools->General Settings->Default Visible Services
* Bugfix: Catalog sync could have used wrong URLs for new releases. In case that happened you can update all links in all releases running a manual update: LabelGridTools -> Sync Content and checking the option "FORCE SYNC RELEASES"

= 1.2.5 =
* New feature: Gate now supports Youtube action 'like video'.
* New feature: Gate can now collect data for "Contests" with no download or forward.
* Minor fixes
* Library update

= 1.2.4 =
* New feature: Gate now supports Youtube action 'follow accounts'.
* New feature: Gate and presave now accept multiple values on each action.
* Minor improvements on Store links, custom links and Gate functionalities.
* Minor fix: Releases->Spotify player now will be not visible pre-release date.

= 1.2.3 =
* New feature: Now is possible to add custom links for Releases and Artists. Go to Admin Dashboard->LabelGrid Tools->General Settings->Custom Services

= 1.2.2 =
* Sync: Minor update on ISRC fields import
* New feature: You can now re-order all the services that are listed in Releases/Artists pages. Go to Admin Dashboard->LabelGrid Tools->General Settings->Default Visible Services to setup the order or delete services.
* Code cleaning and optimizations 

= 1.2.1 =
* Pre-save: Now save album and tracks also if user doesnt select any specific playlist.
* Gate/Pre-save: Minor fixes
* Sync Catalog: Minor fixes
* Library update


= 1.2.0 =
* New Pre-save system with Gate: Pre-save will be now handled remotely by Gate Labelgrid. An API Key is required.
* Refactoring and fixes
* Library Updates

= 1.1.16 =
* Minor improvements on Sync updates

= 1.1.15 =
* New feature: Gated Downloads now allows minimum number of accounts connected. Check the docs: https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugininstallation/advanced-features/gated-downloads#set-minimum-number-of-services-to-connect
* Minor Fixes
* Library update

= 1.1.14 =
* New feature: Now is possible to enable email collection on gated downloads, obligatory or optional.
* New feature: Gate Entries table in LabelGrid Admin Panel: View and export (CSV) collected emails for all or selected gated downloads.
* Minor fixes on gate functionalities.
* Library update

= 1.1.13 =
* Gate downloads are disabled if there is not a valid labelgrid api key configured.
* Minor fixes sync data

= 1.1.12 =
* Minor edit API check

= 1.1.11 =
* Library update
* Fix Docs Link in settings
* Changed the occurency of catalog update cron. Now every 6 hours
* Added rate limiting management for apis

= 1.1.10 =
* Fix Spotify Pre/Save data storage
* Fix Gate download layer description text
* Library update

= 1.1.9 =
* Library update

= 1.1.8 =
* Bugfix: Gate button with empty text in certain cases

= 1.1.7 =
* Spotify Pre-save wrong table name fix
* Minor fixes
* Style and Responsive fixes

= 1.1.6 =
* Minor fixes

= 1.1.5 =
* Minor fixes

= 1.1.4 =
* New feature: New outlets are available on releases: IHeartRadio, Amazon Music, Yandex and Napster.
* Minor improvements on banner shortcode

= 1.1.3 =
* Library update
* New feature: New shortcode "[labelgrid-release-banner label='labelname']" to create automated banners for home page purposes.

= 1.1.2 =
* Maintenance release

= 1.1.1 =
* Library update
* Minor fixes

= 1.1.0 =
* Library update
* Doc links updated

= 1.0.36 =
* Composer fix

= 1.0.35 =
* Minor improvements on Gate
* Minor bugs on logs cleaning
* Library update

= 1.0.34 =
* Library update
* New feature: Improved app update
* New feature: Gate download buttons are indipendent from releases and can be now fully personalized with custom texts in the link and download window. More info: https://labelgrid.atlassian.net/wiki/spaces/CSM/pages/28508210/WordPress+Plugininstallation/contents/shortcodes

= 1.0.33 =
* Bugfix: Fix in Spotify pre/save cron task
* Library update

= 1.0.32 =
* Bugfix: Using CURL to check existance of images to avoid issues with hostings with php setting "allow_url_fopen" set to off.

= 1.0.31 =
* New feature: Added logs

= 1.0.30 =
* New feature: New store fields available: Pandora.
* New feature: Gate Downloads now supports Soundcloud.
* Bugfix: Automatic Catalog sync was ignoring the skip field in general settings.

= 1.0.29 =
* Library update
* Minor changes

= 1.0.28 =
* New feature: Revamped product import controls on dates. All elements and images will be imported only when changed.
* Bugfix: Various bugfix and enchangements

= 1.0.27 =
* IMPORTANT: In this release we have changed many important parts, in case of issues please disable and re-enable the plugin.

= 1.0.26 =
* Bugfix: Logs handling
* New feature: Can filter Logs for Channel

= 1.0.25 =
* Bugfix: Database logs

= 1.0.24 =
* Bugfix: Import updated release
* New feature: Automatic log cleaning with Cron and new day retention field in General Settings

= 1.0.23 =
* Maintenance: Code refactoring and cleaning.
* Bugfix: Import updated release
* Library Update

= 1.0.22 =
 * Bugfix: Minor bugs on import/save new releases from LGAPI.

= 1.0.21 =
* New Feature: Revamped Log Handler - Now is possible to debug the majority of actions of the app.
* Bugfix: Import utility and other minor bugs.

= 1.0.20 =
* New Feature: New settings on General Settings to enable Debug informations.
* Bugfix: Spotify Pre/Save Cron Schedule fix.
* Bugfix: Cron Activation/Deactivation edit.

= 1.0.19 =
* Bugfix: Spotify Pre/Save functionalities has been optimized.

= 1.0.18 =
* New Feature: You can now pass variables (es: ?spotify/youtube/beatport etc) to the release and artist pages, the plugin will redirect the user directly to the linked service.
* Bugfix: Admin Menu Bar Update is now working on frontend pages.

= 1.0.17 =
* Bugfix: Apple affiliation code moved to inline-wordpress function.
* Wordpress 5.3 support

= 1.0.16 =
* New Feature: New LabelGrid menu in Wordpress Admin Menu Bar. Now you can easily and quickly update the catalog with a click.

= 1.0.15 =
* Bugfix: Minor bugfixes
* Library update

= 1.0.14 =
* Bugfix: Gate download fixed description 500error
* New Feature: Bandsintown feed on artist page	
* Bugfix: Releases that are set to hide from the general list, will be hide from the JSON feed as well.												
* Cleaning old files
* Bugfix: Releases List CSS

= 1.0.13 =
* General updates for catalog sync with LabelGrid V.2
* Library update
* Bugfix: iTunes Affiliation code not correctly displayed
* New Feature: Releases can be hide from the Releases lists thru the plugin Shortcodes.
* Minor fixes and new features

= 1.0.12 =
* Bugfix: iTunes pre-save link fix
* Bugfix: Gate Downloads file download failed a security check.
* Bugfix: Spotify Pre-Save wasn't saving tracks on user's playlists.
* New Feature: Beatport pre-save

= 1.0.11 =
* Library update

= 1.0.10 =
* Bugfix: Gate Download session

= 1.0.9 =
* Edit functionality: Images sizes are now using exlusive names to avoid issues with other plugins/themes.


= 1.0.8 =
* New Feature: Added support to W3TC Cache Plugin.
* Bugfix: Gate Download cache

= 1.0.7 =
* New Feature: Added json Release Feed to Sync with external services. (https://yourdomain.com/releases-feed/).
* New Feature: Gate Download now supports caching plugin: W3 Total Cache.
* New Feature: Spotify (Pre)Save: LabelGrid Tools now throw errors in the webserver logs for any issue running scheduled actions
* Bugfix: Spotify (Pre)Save does not operate properly.
* Bugfix: Fixed options to hide Save/Pre-save Options in General Settings - Releases
* Bugfix: Fixed Styles for Releases/Artists lists with custom Row items.

= 1.0.6 =
* Bugfix: Fixed iTunes links Geolocalization in Artist pages
* New Feature: Single release page: added releases by same artist. It is possible to disable this feature in General Options->Releases tab.
* Edit: LabelGrid Sync now runs hourly.

= 1.0.5 =
* New Feature: Links to external services now generate Analytics events for tracking if Google Tag Manager is active and configured to receive "mediabutton_click" events.
* New Feature: LabelGrid Sync: Genres are now imported hierarchically.
* Readme update

= 1.0.4 =
* Bug Fix: Gate Downloads: Fix template with no artists
* Bug Fix: Gate Downloads: Fix obligatory fields
* Bug Fix: LabelGrid Sync: Post status doesnt get overwitten after update.
* Bug Fix: LabelGrid Sync: Automatic Sync not running on certain cases
* Tweak: Releases: Added Artist names field
* New Feature: LabelGrid Sync: Force Sync of releases

= 1.0.3 =
* New Feature: Added 'title-below' parameter to Releases/Artists Shortcodes.
* New Feature: Download stats on Gate Downloads.
* Bugfix: LabelGrid Import, skip artists/releases with empty name.
* Bugfix: Gate Downloads, optimized dowload procedure for larger files
* Minor fixes

= 1.0.2 =
* Minor fixes

= 1.0.1 =
* Minor fixes

= 1.0.0 =
* First commit

== Upgrade Notice ==

= 1.0 =
First commit