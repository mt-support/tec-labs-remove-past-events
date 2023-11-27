=== The Events Calendar Extension: Remove Past Events ===
Contributors: theeventscalendar
Donate link: https://evnt.is/29
Tags: events, calendar
Requires at least: 5.8.6
Tested up to: 6.2.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL version 3 or any later version
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This extension adds additional frequencies to the 'Move to trash events older than' and 'Permanently delete events older
than' settings, which can be found under `Events > Settings > General tab`.

== Installation ==

Install and activate like any other plugin!

* You can upload the plugin zip file via the *Plugins ‣ Add New* screen
* You can unzip the plugin and then upload to your plugin directory (typically _wp-content/plugins_) via FTP
* Once it has been installed or uploaded, simply visit the main plugin list and activate it

== Frequently Asked Questions ==

= Where can I find more extensions? =

Please visit our [extension library](https://theeventscalendar.com/extensions/) to learn about our complete range of extensions for The Events Calendar and its associated plugins.

= What if I experience problems? =

We're always interested in your feedback and our [Help Desk](https://support.theeventscalendar.com/) are the best place to flag any issues. Do note, however, that the degree of support we provide for extensions like this one tends to be very limited.

== Changelog ==

= [1.2.1] 2023-12-11 =

* Fix - Added compatibility to The Events Calendar 6.0 data structure. [ECP-1604]

= [1.2.0] 2023-06-23 =

* Fix - Update to use the new Service_Provider contract in common.

= [1.1.1] 2023-01-22 =

* Fix - Added a filter call to make the extension work with TEC 6.0.5 and above.

= [1.1.0] 2022-06-20 =

* Fix - Added a missing variable declaration, which prevents the accidental deletion of recurring events.

= [1.0.0] 2022-01-20 =

* Initial release
