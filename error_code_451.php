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
    Copyright 2017 Ulrike <u@451f.org>, Tara

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
    load_plugin_textdomain( 'error_451', false, "$plugin_dir/languages" );
}
add_action('plugins_loaded', 'error_code_451_init');

/* get user IP */
function get_the_user_ip() {
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        // check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        // to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return apply_filters( 'wpb_get_ip', $ip );
}

function get_client_geocode() {
      // Get visitor geo origin
      $json_url = 'http://freegeoip.net/json/' . get_the_user_ip();
      $options = array(
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
      );
      $ch = curl_init( $json_url );
      curl_setopt_array( $ch, $options );
      $ch_result = curl_exec($ch);
      $geo_ip = json_decode($ch_result);
	  if($geo_ip->country_code)
          return $geo_ip->country_code;
}

// Serve 451 http_response_code and send additional headers
function error_451_check_blocked() {
	// Get access to the current WordPress object instance
	// Get the base URL & post ID
	global $wp;
	$current_url = home_url(add_query_arg(array(),$wp->request));
	$current_url = $current_url . $_SERVER['REDIRECT_URL'];
	$post_id = url_to_postid($current_url);

    $client_geo_origin = get_client_geocode();

    //get blocked countries from post metadata
    $blocked_countries = explode(',', get_post_meta( $post_id, 'error_451_blocking_countries', true));

    if(get_post_meta( $post_id, 'error_451_blocking', true) == "yes") {
        if( in_array($client_geo_origin, $blocked_countries) || empty($blocked_countries) ) {
            $error_code = 451;
    		$site_url = site_url();
    		$blocking_authority = get_post_meta($post_id, 'error_451_blocking_authority', true);
    		$blocking_description = get_post_meta($post_id, 'error_451_blocking_description', true);

    		// send additional headers
    		header('Link: <'.$site_url.'>; rel="blocked-by"', false, $error_code);
    		header('Link: <'.$blocking_authority.'>; rel="blocking-authority"', false, $error_code);

    		// redirect to get the correct HTTP status code for this page.
    		wp_redirect("/451", 451);
    		$user_error_message  = '<html><head><body><h1>451 Unavailable For Legal Reasons</h1>';
    		$user_error_message .= '<p>This status code indicates that the server is denying access to the resource as a consequence of a legal demand.</p>';
    		if(!empty($blocking_description)) {
        	    $user_error_message .= '<p>'.$blocking_description.'</p>';
    		}
    		if(!empty($blocking_authority)) {
        	    $user_error_message .= '<p>The blocking of this content has been requested by <a href="'.$blocking_authority.'">'.$blocking_authority.'</a>.</p>';
    		}
    		$user_error_message .= '<p>On an unrelated note, <a href="https://gettor.torproject.org/">Get Tor.</a></p></body></html>';
    		echo $user_error_message;
    		exit;
        }
    }
}

/* Check for blocked content on page load */
add_action( 'init', 'error_451_check_blocked');

/* Meta box setup function. */
function error_451_post_meta_boxes_setup() {
    /* Add meta boxes on the 'add_meta_boxes' hook. */
    add_action( 'add_meta_boxes', 'error_451_add_post_meta_boxes' );

    /* Save post meta on the 'save_post' hook. */
    add_action( 'save_post', 'error_451_save_blocking_meta', 10, 2 );
}

/* Create meta box to be displayed on the post editor screen. */
function error_451_add_post_meta_boxes() {
    add_meta_box(
        'error-451-blocking', // Unique ID
        esc_html__( 'Configure blocking / Error 451', 'error-451' ), // Title
        'error_451_meta_box',  // Callback function
        'post',         // Admin page (or post type)
        'side',         // Context
        'default'       // Priority
  );
}

/* Display the post meta box. */
function error_451_meta_box( $post ) {
  wp_nonce_field( basename( __FILE__ ), 'error_451_blocking_nonce' ); ?>

  <p>
	<?php $error_451_blocking = get_post_meta($post->ID, 'error_451_blocking', true); ?>
    <label for="error_451_blocking">
    <input class="checkbox" type="checkbox" name="error_451_blocking" id="error_451_blocking" value="yes" <?php if($error_451_blocking == "yes") { echo ' checked="checked"'; } ?> />
	<?php _e( "If you have to enable blocking of this content, please check the box.", 'error_451' ); ?></label>
  </p>

  <p>
    <label for="error_451-blocking_countries"><?php _e( "Add comma-separated list of country codes where this page shall be blocked. When left empty, the content shall be blocked from all locations.", 'error_451' ); ?></label>
    <br />
    <input class="widefat" type="text" name="error_451_blocking_countries" id="error_451_blocking_countries" value="<?php echo esc_attr( get_post_meta( $post->ID, 'error_451_blocking_countries', true ) ); ?>" size="30" />
  </p>

  <p>
    <label for="error_451_blocking_authority"><?php _e( "Please specify the URL of the authority has requested the block.", 'error_451' ); ?></label>
    <br />
    <input class="widefat" type="url" name="error_451_blocking_authority" id="error_451_blocking_authority" value="<?php echo esc_attr( get_post_meta( $post->ID, 'error_451_blocking_authority', true ) ); ?>" size="256" />
  </p>

  <p>
    <label for="error_451_blocking_description"><?php _e( "You may optionally specify a description so that visitors know why the page is blocked.", 'error_451' ); ?></label>
	<br />
    <input class="widefat" type="text" name="error_451_blocking_description" id="error_451_blocking_description" value="<?php echo esc_attr( get_post_meta( $post->ID, 'error_451_blocking_description', true ) ); ?>" size="256" />
  </p>
<?php }

/* Save the meta box's post metadata. */
function error_451_save_blocking_meta( $post_id, $post ) {

    /* Verify the nonce before proceeding. */
    if ( !isset( $_POST['error_451_blocking_nonce'] ) || !wp_verify_nonce( $_POST['error_451_blocking_nonce'], basename( __FILE__ ) ) )
      return $post_id;

    /* Get the post type object. */
    $post_type = get_post_type_object( $post->post_type );

    /* Check if the current user has permission to edit the post. */
    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
        return $post_id;

    // which meta keys do we want to update?
    $meta_keys = array('error_451_blocking', 'error_451_blocking_authority', 'error_451_blocking_countries', 'error_451_blocking_description');

    /* Get the posted data and sanitize it. */
    foreach($meta_keys as $meta_key) {
        $new_meta_value[$meta_key] = ( isset( $_POST[$meta_key] ) ? sanitize_text_field( $_POST[$meta_key] ) : '' );

        /* Get the meta value of the custom field key. */
        $meta_value = get_post_meta( $post_id, $meta_key, true );

        /* If a new meta value was added and there was no previous value, add it. */
	    if ( $new_meta_value[$meta_key] && '' == $meta_value ) {
		    add_post_meta( $post_id, $meta_key, $new_meta_value[$meta_key], true );
        }

	    /* If the new meta value does not match the old value, update it. */
	    elseif ( $new_meta_value[$meta_key] && $new_meta_value[$meta_key] != $meta_value ) {
		    update_post_meta( $post_id, $meta_key, $new_meta_value[$meta_key] );
	}

	/* If there is no new meta value but an old value exists, delete it. */
	elseif ( '' == $new_meta_value[$meta_key] && $meta_value ) {
		delete_post_meta( $post_id, $meta_key, $meta_value );
	}

    }
}

/* Fire meta box setup function on the post editor screen. */
add_action( 'load-post.php', 'error_451_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'error_451_post_meta_boxes_setup' );

/* Save post meta on the 'save_post' hook. */
add_action( 'save_post', 'error_451_save_blocking_meta', 10, 2 );
?>
