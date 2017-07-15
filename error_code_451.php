<?php
/*
Plugin Name: Error Code 451
Plugin URI: http://github.com/451hackathon/wpplugin
Description: Wordpress plugin to interact with the Blocked-Middleware by OpenRightsGroup. API credentials can be configured via a settings page.
Version: 0.1
Author: Ulrike Uhlig
License: GPL3+
Text Domain: error_code_451
Domain Path: /languages/
*/

/*
    Copyright 2017 Ulrike Uhlig <u@curlybracket.net>

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


/* Plugin l10n */
function error_code_451_init() {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain( 'error_code_451', false, "$plugin_dir/languages" );
}
add_action('plugins_loaded', 'error_code_451_init');


/* get user IP */
function get_the_user_ip() {
if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
//check ip from share internet
$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
//to check ip is pass from proxy
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
$ip = $_SERVER['REMOTE_ADDR'];
}
return apply_filters( 'wpb_get_ip', $ip );
}

// make it possible for a site admin to block a URL
// - serve 451 http_response_code
// send HTTP response CODE
// FIXME: implement geoblocking
$json_url = 'http://freegeoip.net/json/' . get_the_user_ip();
$options = array(
CURLOPT_RETURNTRANSFER => true,
CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
);
$ch = curl_init( $json_url );
curl_setopt_array( $ch, $options );
$ch_result = curl_exec($ch);
$geo_ip = json_decode($ch_result);

//echo $geo_ip->country_code;




$http_response_code = http_response_code();
if($http_response_code == 451) {
	// get additional header: "blocked-by"
	// contact the webcrawler
}

// - based on geocodes
// - make it possible to send blocked-by header
// Admin page with URLs to block (list and checkboxes)
// or field in post editor with checkbox / we could use post_meta for this.
// make it possible to allow the admin to report the block to the webcrawler

/*
// https://tools.ietf.org/html/rfc7725
 When an entity blocks access to a resource and returns status 451, it
 SHOULD include a "Link" HTTP header field [RFC5988] whose value is a
 URI reference [RFC3986] identifying itself.  When used for this
 purpose, the "Link" header field MUST have a "rel" parameter whose
 value is "blocked-by".

 - Would be the website itself = the implementor
 - extra field blocking-authority = specify the entity that requested the blocking

 -> post_meta fields which we need to create
 1 = blocked boolean,
 2 = blocking-authority (URI)
 3 = Geocode field (comma separated list of country codes)
 additional header: blocked-by (automatically attributed URI)
*/

/*
What would the user see?
-> would get served a page telling them "Error 451 - blocked for legal reasons"
-> should also say "by $blocking_authority"
*/
?>
