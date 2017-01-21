<?php
/*
Plugin Name: PM Affiliates - Ecwid integration
Plugin URI: http://luke.gedeon.name/
Description: Track sales in Ecwid back to referrer.
Version: 1.0
Author: lgedeon
Author URI: http://luke.gedeon.name/
License: GPLv2 or later
Text Domain: pmaffiliates_ecwid
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2005-2015 Codegrade LLC
*/

namespace PMAffiliates\Ecwid;

/**
 * Records affiliate action when someone makes an ecwid purchase.
 *
 */
function init() {
  // Will probably get our info from a query string.
  \PMAffiliates\add_transaction( 'ecwid purchase', $data );
}
add_action( 'init', __NAMESPACE__ . '\init', 10, 3 );
