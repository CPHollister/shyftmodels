<?php
/**
 * Plugin Name: WP Simple Booking Calendar
 * Plugin URI:  http://www.wpsimplebookingcalendar.com
 * Description: WP Simple Booking Calendar - Free Version.
 * Version:     1.4.1
 * Author:      WP Simple Booking Calendar
 * Author URI:  http://www.wpsimplebookingcalendar.com
 * License:     GPL2
 *
 * Copyright (c) 2011 WP Simple Booking Calendar
 */
 
 /*  Copyright 2011  WP Simple Booking Calendar

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Translation
load_plugin_textdomain('sbc', false, dirname(plugin_basename(__FILE__)) . '/languages/');

// Initialization
define('SBC_DIR_URL', plugin_dir_url(__FILE__));

require_once 'library/WpSimpleBookingCalendar/Exception.php';
require_once 'library/WpSimpleBookingCalendar/Model.php';
require_once 'library/WpSimpleBookingCalendar/View.php';
require_once 'library/WpSimpleBookingCalendar/Controller.php';
require_once 'library/WpSimpleBookingCalendar/Shortcode.php';
require_once 'library/WpSimpleBookingCalendar/Widget.php';
require_once 'library/WpSimpleBookingCalendar/Ajax.php';
require_once 'library/WpSimpleBookingCalendar.php';

WpSimpleBookingCalendar::init();