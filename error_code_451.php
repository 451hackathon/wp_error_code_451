<?php
/*
Plugin Name: Error Code 451
Plugin URI: http://github.com/451hackathon/wpplugin
Description: Wordpress plugin to block pages using Error Code 451.
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

// make it possible for a site admin to block a URL
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

// FIXME: implement geoblocking
if($client_geo_origin == "SOMETHING") {
	// - serve 451 http_response_code
	$http_response_code = http_response_code(451);
	// send additional header: "blocked-by"
}

// This will get the HTTP response code of the current page.
$http_response_code = http_response_code();
if($http_response_code == 451) {
	// get additional header: "blocked-by"
	// contact the webcrawler
}
?>
