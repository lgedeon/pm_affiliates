<?php
/*
Plugin Name: PM Affiliates
Plugin URI: http://luke.gedeon.name/
Description: Affiliate tracking doesn't have to be hard (or expensive). Let's keep it simple.
Version: 1.0
Author: lgedeon
Author URI: http://luke.gedeon.name/
License: GPLv2 or later
Text Domain: pmaffiliates
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

/**
 * Roadmap:
 * Capture new user creation.                   * Started. Needs testing.
 * Capture JetPack form (grunion) submissions.  * Done.
 * Capture Ecwid purchase.                      * Starting....
 * Create a report page.                        *
 * Add custom summaries for transaction types.  *
 */


namespace PMAffiliates;
const PMA_ID = 'pm_affiliates_id';

// Set up CPT, cookie and (if applicable) affiliate id for current user.
function init () {
  // Register headless cpt to store data.
  register_post_type( PMA_ID, array( 'public' => false ) );

  // Get current affiliate id if available.
  $affiliate = get_current_affiliate();

  // Store affiliate code into cookie. Affiliates do not have to log in to share.
  if ( ! get_affiliate_cookie() && $affiliate ) {
    setcookie( PMA_ID, $affiliate, 30 * DAYS_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
  }

  // If user is signed in but doesn't have an affiliate id, give them one.
  if ( is_user_logged_in() && ! get_affiliate_by_user( get_current_user_id() ) ) {
    user_register( get_current_user_id() );
  }
}
add_action( 'init', __NAMESPACE__ . "\init" );

// Get current affiliate based on user, cookie or query string.
function get_current_affiliate() {
  if ( $affiliate = get_affiliate_by_user( get_current_user_id() ) ) {
    return $affiliate;
  }

  return get_affiliate_cookie() ?: get_affiliate_query_string();
}

// Return affiliate id when given a user id.
function get_affiliate_by_user( $user_id ) {
  if ( ! is_numeric( $user_id ) || 0 == $user_id ) {
    return 0;
  }

  return get_user_meta( $user_id, PMA_ID, true );
}

// Add pm_affiliates_id to the query_string of a link.
function add_affiliate_id ( $link ) {

  if ( ! is_string( $link ) ) { echo "<!-- Non-string passed to PMAffiliates\add_affiliate_id(): $link - " . current_filter() . " -->"; }
  $link = add_query_arg( PMA_ID, get_current_affiliate(), $link );
  if ( ! is_string( $link ) ) { echo "<!-- Non-string encountered in PMAffiliates\add_affiliate_id(): $link - " . current_filter() . " -->"; }

  return $link;
}

add_filter( 'post_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'page_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'post_type_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'year_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'month_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'day_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'feed_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'search_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'post_type_archive_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'next_post_rel_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'previous_post_rel_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'pre_post_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'next_post_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'get_pagenum_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'home_url', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'admin_url', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'edit_profile_url', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'get_shortlink', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'attachment_link', __NAMESPACE__ . '\add_affiliate_id' );
add_filter( 'preview_post_link', __NAMESPACE__ . '\add_affiliate_id' );

//site_url doesn't need to be modified for core but may be used by a plugin
//add_filter( 'site_url', __NAMESPACE__ . '\add_affiliate_id' );


// Get affiliate id from cookie if available.
function get_affiliate_cookie() {
  if ( isset( $_COOKIE[PMA_ID] ) ) {
    return $_COOKIE[PMA_ID];
  }
}

// Get affiliate id from query string if available.
function get_affiliate_query_string () {
  if ( isset( $_REQUEST[PMA_ID] ) ) {
    return $_REQUEST[PMA_ID];
  }
}

// Link affiliate to new WP user. Remove if your plugin does this differently.
function user_register( $user_id ) {
  $referrer = get_current_affiliate();
  $affiliate_id = new_affiliate();

  // Record sign-up transaction. @todo Refactor as sample of api use.
  if ( $referrer ) {
    add_transaction( 'new affiliate', $affiliate_id, $referrer );
  }

  // Link user record to affiliate record.
  if ( $affiliate_id ) {
    update_user_meta( $user_id, PMA_ID, $affiliate_id );
    add_post_meta( $affiliate_id, PMA_ID . 'user_id', $user_id );
  }

  add_parent_list( $affiliate_id, $referrer );
}
add_action( 'user_register', __NAMESPACE__ . '\user_register', 10, 1 );


// Create new affiliate and return id.
function new_affiliate() {
  return wp_insert_post( array( 'post_type' => PMA_ID ), false );
}

// Record parents.
function add_parent_list( $affiliate_id, $parent_id ) {
  $parents = get_parent_list( $parent_id );

  if ( is_numeric( $parent_id ) && $parent_id > 0 ) {
    $parents[] = $parent_id;
  }

  if ( $affiliate_id ) {
    add_post_meta( $affiliate_id, PMA_ID . 'parent', $parent );
  }
}

// Get list of all parents of a given affiliate.
function get_parent_list( $affiliate_id ) {
    $parents = get_post_meta( $affiliate_id, PMA_ID . 'parent', true );
    return $parents ? (array) $parents : array();
}

// Record transaction.
function add_transaction( $type, $value, $affiliate_id = 0 ) {
  if ( 0 == $affiliate_id ) {
    $affiliate_id = get_current_affiliate();
  }

  // Variable $type must be string or everything go kablooey.
  if ( is_string( $type ) ) {
    add_post_meta( $affiliate_id, PMA_ID . '_' . $type, $value, false );
  }
}

// Report transactions.
function get_transactions ( $type = '', $affiliate_id = 0 ) {
  if ( 0 == $affiliate_id ) {
    $affiliate_id = get_current_affiliate();
  }

  // If we have any value for the transaction type, add the prefix.
  if ( ! empty( $type ) ) {
    $type = PMA_ID . '_' . $type;
  }

  // An empty $type returns all values.
  return get_post_meta( $affiliate_id, $type );
}


// Setup a spot on the wp-admin profile page to report all we know.
function personal_options() {
  $affiliate = get_current_affiliate();
  $parents = array_to_html_list( get_parent_list( $affiliate ) );
  $report = array_to_html_list( get_transactions() );
  ?>
    </tbody>
  </table>
<h2>PM Affiliates</h2>
<table class="form-table">
  <tbody>
    <tr>
      <th scope="row">
        Affiliate ID:
      </th>
      <td>
        <?php echo $affiliate; ?>
      </td>
    </tr>
    <tr>
      <th scope="row">
        Parent Affiliates:
      </th>
      <td>
        <?php echo $parents; ?>
      </td>
    </tr>
    <tr>
      <th scope="row">
        Transaction Report:
      </th>
      <td class="<?php echo PMA_ID; ?>-report">
        <?php echo $report; ?>
      </td>
    </tr>
<style>
.<?php echo PMA_ID; ?>-report ul ul { margin-left: 2em; }
</style>
  <?php
}
add_action( 'personal_options', __NAMESPACE__ . '\personal_options' );


// Helper: Convert array to html list. So ugly.... If I can make it -pretty- well less ugly, I might share.
function array_to_html_list( $array, $classes = '', $outer_key = '' ) {
  $html = '';

  // accept array of classes for each level or a single class for all. @todo maybe do striping classes
  $class = is_array( $classes ) ? array_shift( $classes ) : $classes;

  if ( is_object( $array ) ) {
    $array = get_object_vars( $array );
  }

  if ( is_array( $array ) ) {
    foreach ( $array as $key => $node ) {
      $html .= array_to_html_list( $node, $classes, $key );
    }
    $html = "<ul class=\"$class\">$html</ul>";

    if ( '' !== $outer_key ) {
      $html = "<li class\"$class\">[$outer_key] $html</li>";
    }

    return $html;
  }

  return "<li class=\"$class\">[$outer_key] $array</li>";
}
