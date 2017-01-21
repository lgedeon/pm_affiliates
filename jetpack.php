<?php
/*
Plugin Name: PM Affiliates - JetPack integration
Plugin URI: http://luke.gedeon.name/
Description: Track actions in JetPack contact forms (formerly known as grunion)
Version: 1.0
Author: lgedeon
Author URI: http://luke.gedeon.name/
License: GPLv2 or later
Text Domain: pmaffiliates_jetpack
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

namespace PMAffiliates\Jetpack;

/**
 * Records affiliate action when someone uses a JetPack contact form.
 *
 * @param integer $post_id Post contact form lives on
 * @param array $all_values Contact form fields
 * @param array $extra_values Contact form fields not included in $all_values
 */
function grunion_pre_message_sent( $post_id, $all_values, $extra_values ) {
  \PMAffiliates\add_transaction( 'jetpack grunion form', array( $post_id, $all_values, $extra_values ) );
}
add_action( 'grunion_pre_message_sent', __NAMESPACE__ . '\grunion_pre_message_sent', 10, 3 );
