=== WP Simple Booking Calendar ===
Contributors: Bryght, BestWebSoft
Tags: booking calendar, bookings, booking, bookable, calendar, availability calendar, availability, reservation calendar, reservations, scheduling, schedule, rooms, hotel, holiday home, accommodations, dateblocker, date blocker, bed and breakfast, belegungsplan, beschikbaarheidskalender
Requires at least: 3.0
Tested up to: 4.2.2
Stable tag: 1.4.1

This booking calendar shows when something is booked or available. Use it to show when your holiday home is available for rent, for example.

== Description ==

Create a booking calendar for your website! Do you want to show people when your holiday home (or something else) is available for rent? You can create, edit and publish a booking calendar with just a few clicks with the WP Simple Booking Calendar. This booking calendar is very easy to use! You can manage the bookings (availability) on a daily basis and embedding the booking calendar on a page takes only one mouse click. You can also use the WP Simple Booking Calendar Widget to show a booking calendar on your WordPress website. Check out http://www.wpsimplebookingcalendar.com for more information about this booking calendar.

You can use this booking calendar as:

* Booking calendar / availability calendar for a holiday home, bed & breakfast, condo or hotel
* Booking calendar / availability calendar for a room or office
* Booking calendar / availability calendar for  a car or boat
* A booking calendar for equipment
* A shift calendar
* Whatever you like!

Features of the Free version:

* Create a booking calendar and set a status per date
* Generate a token to insert the booking calendar in a page or post
* WP Simple Booking Calendar Widget (booking calendar as widget)
* Can be translated into other languages using PO files

Features of the Premium version:

* Create an unlimited number of booking calendars
* Display multiple months
* Edit multiple dates with one click
* Display a legend near the calendar
* Create your own legend (apply your own colours and translations)
* Change the first day of the week
* Change the start month / year
* Add and save booking information for each day on each of your booking calendars
* User management: assign users to calendars
* Display a tooltip with info (you can enter info for each day)
* Show the week's number
* Generate a token to insert the booking calendar in a page or post
* WP Simple Booking Calendar Widget (booking calendar as widget)
* Easy to translate into your own language
* Download the Premium version at: http://www.wpsimplebookingcalendar.com


== Installation ==

1. Upload `wp-simple-booking-calendar` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the settings page of the plugin to setup a calendar
4. Embed the calendar on any page or post using the shortcode **[sbc]**

A sidebar widget is also available.


== Frequently Asked Questions ==

= How can I embed the booking calendar on a page or post? =

Use the 'Generate token' field below your editor. An example of a token: [sbc title="yes"]. If you paste this token in a page it will show your booking calendar with the title.

= How can I remove the booking calendar title from displaying? =

Edit the shortcode: title="no".

= I have another question =

Please see http://www.wpsimplebookingcalendar.com for more information and ask your questions there!


== Screenshots ==

1. Two month view of the booking calendar (free version supports one month view only)
2. The booking calendar in a sidebar as widget
3. Editing the booking calendar

== Changelog ==

= 1.4.1 =
* Small hash tweak

= 1.4 =
* Security hardening (added a unique identifier to all urls in a form of a hash)

= 1.3 =
* Small CSS tweaks for WordPress 3.8

= 1.2 =
* Changed .live() to .on() for better compatibility with jQuery

= 1.1 =
* Fixed Warning: array_key_exists() error some users experienced
* Fixed enqueue_scripts

= 1.0 =
* First release

== Upgrade Notice ==

= 1.4.1 =
* Small hash tweak

= 1.4 =
* Security hardening (added a unique identifier to all urls in a form of a hash)

= 1.3 =
* Small CSS tweaks

= 1.2 =
* Small improvement for the free version

= 1.1 =
* Two small fixes

= 1.0 =
* Stable release