<?php
/**
 * Accessible Video Library
 *
 * @package     Accessible Video Library
 * @author      Joe Dolson
 * @copyright   2013-2018 Joe Dolson
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Accessible Video Library
 * Plugin URI: http://www.joedolson.com/accessible-video-library/
 * Description: Accessible video library manager. Write transcripts and upload captions.
 * Author: Joseph C Dolson
 * Author URI: http://www.joedolson.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/license/gpl-2.0.txt
 * Text Domain: accessible-video-library
 * Domain Path: /lang
 * Version: 1.2.1
 */

/*
	Copyright 2013-2018  Joe Dolson (email : joe@joedolson.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$avl_version = '1.2.1';
// Filters.
add_filter( 'post_updated_messages', 'avl_posttypes_messages' );

add_action( 'plugins_loaded', 'avl_load_textdomain' );
/**
 * Set up internationalisation.
 */
function avl_load_textdomain() {
	load_plugin_textdomain( 'accessible-video-library', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

}

// Actions.
add_action( 'init', 'avl_taxonomies', 0 );
add_action( 'init', 'avl_posttypes' );
add_action( 'admin_menu', 'avl_add_outer_box' );

register_activation_hook( __FILE__, 'avl_plugin_activated' );
/**
 * Define fields on activation.
 */
function avl_plugin_activated() {
	flush_rewrite_rules();
}

/**
 * Default fields for AVL videos.
 *
 * @return array
 */
function avl_fields() {
	$avl_fields = array(
		'captions' => array(
			'label'  => __( 'Captions (SRT/DFXP)', 'accessible-video-library' ),
			'input'  => 'upload',
			'format' => 'srt',
			'type'   => 'caption',
		),
		'mp4'      => array(
			'label'  => __( 'Video (mp4)', 'accessible-video-library' ),
			'input'  => 'upload',
			'format' => 'mp4',
			'type'   => 'video',
		),
		'ogv'      => array(
			'label'  => __( 'Video (ogv)', 'accessible-video-library' ),
			'input'  => 'upload',
			'format' => 'ogv',
			'type'   => 'video',
		),
		'external' => array(
			'label'  => __( 'YouTube Video URL', 'accessible-video-library' ),
			'input'  => 'text',
			'format' => 'youtube',
			'type'   => 'video',
		),
		'vimeo'    => array(
			'label'  => __( 'Vimeo Video URL', 'accessible-video-library' ),
			'input'  => 'text',
			'format' => 'vimeo',
			'type'   => 'video',
		),
	);

	return apply_filters( 'avl_add_custom_fields', $avl_fields );
}

register_deactivation_hook( __FILE__, 'avl_plugin_activated' );
/**
 * Handle deactivation.
 */
function avl_plugin_deactivated() {
	flush_rewrite_rules();
}

add_action( 'plugins_loaded', 'avl_update_check' );
/**
 * Check for update needs.
 */
function avl_update_check() {
	global $avl_version;
	if ( version_compare( $avl_version, '1.0.4', '<' ) ) {
		$posts = get_posts( array( 'post_type' => 'avl-video' ) );
		foreach ( $posts as $post ) {
			if ( '' == get_post_field( 'post_content', $post->ID, 'raw' ) ) {
				add_post_meta( $post->ID, '_notranscript', 'true' );
			}
		}
	}
	update_option( 'avl_version', $avl_version );
}

/**
 * Add the administrative settings to the "Settings" menu.
 */
function avl_add_support_page() {
	if ( function_exists( 'add_submenu_page' ) ) {
		$submenu_page = add_submenu_page( 'edit.php?post_type=avl-video', __( 'Accessible Video Library > Help & Settings', 'accessible-video-library' ), __( 'Video Help/Settings', 'accessible-video-library' ), 'edit_posts', 'avl-help', 'avl_support_page' );
		add_action( 'admin_head-' . $submenu_page, 'avl_styles' );
	}
}

/**
 * Add plugin styles to admin.
 */
function avl_styles() {
	$screen = get_current_screen();
	if ( 'avl-video_page_avl-help' == $screen->id ) {
		wp_enqueue_style( 'avl.styles', plugins_url( 'css/avl-styles.css', __FILE__ ) );
	}
}

add_action( 'admin_menu', 'avl_add_support_page' );
/**
 * Build support & settings page.
 */
function avl_support_page() {
	if ( isset( $_POST['avl_settings'] ) ) {
		$responsive = ( isset( $_POST['avl_responsive'] ) ) ? 'true' : 'false';
		update_option( 'avl_responsive', $responsive );

		$avl_default_caption = ( isset( $_POST['avl_default_caption'] ) ) ? $_POST['avl_default_caption'] : '';
		update_option( 'avl_default_caption', $avl_default_caption );

		echo "<div class='notice updated'><p>" . __( 'Accessible Video Library Settings Updated', 'accessible-video-library' ) . '</p></div>';
	}
	?>
<div class="wrap avl-settings" id="accessible-video-library">
<h1><?php _e( 'Accessible Video Library', 'accessible-video-library' ); ?></h1>
	<div id="avl_settings_page" class="postbox-container avl-wide">
		<div class='metabox-holder'>
			<div class="settings meta-box-sortables">
				<div class="postbox" id="settings">
				<h2 class='hndle'><?php _e( 'Settings', 'accessible-video-library' ); ?></h2>
					<div class="inside">
					<form action='<?php echo admin_url( 'edit.php?post_type=avl-video&page=avl-help' ); ?>' method='post'>
						<p>
						<label for="avl_default_caption"><?php _e( 'Enable Subtitles by Default', 'accessible-video-library' ); ?></label>
						<select id="avl_default_caption" name="avl_default_caption">
						<?php
						$output = '';
						$fields = avl_fields();
						foreach ( $fields as $key => $field ) {
							if ( 'subtitle' == $field['type'] || 'caption' == $field['type'] ) {
								$label    = esc_html( $field['label'] );
								$value    = esc_attr( $key );
								$selected = selected( $value, get_option( 'avl_default_caption' ), false );
								if ( $value ) {
									$output .= "<option value='$value'$selected>$label</option>";
								}
							}
						}
						echo $output;
						?>
						</select>
						</p>
						<p>
							<input type='checkbox' name='avl_responsive' id='avl_responsive' value='true'<?php checked( get_option( 'avl_responsive' ), 'true' ); ?> /> <label for='avl_responsive'><?php _e( 'Responsive Videos', 'accessible-video-library' ); ?></label>
						</p>
						<p>
							<input class='button-primary' type='submit' name='avl_settings' value='<?php _e( 'Update Settings', 'accessible-video-library' ); ?>' />
						</p>
					</form>
				</div>
			</div>
		</div>
		<div class="settings meta-box-sortables">
			<div class="postbox" id="settings">
				<h2 class='hndle'><?php _e( 'Help', 'accessible-video-library' ); ?></h2>
				<div class="inside">
					<p>
					<?php
					_e( 'You can customize some aspects of your videos using filters.', 'accessible-video-library' );
					_e( 'The use of videos from your video library is largely through shortcodes, documented below.', 'accessible-video-library' );
					?>
					</p>
					<h3><?php _e( 'Shortcodes', 'accessible-video-library' ); ?></h3>
					<p>
						<textarea class='large-text readonly' type='text' size="60" readonly>[avl_video id="$video_id" width="$width" height="$height"]</textarea></p>
					<p>
					<?php
						_e( 'The only required field is the ID of the video you want to display. You can also enter a width and a height, and the video will be displayed with those dimensions.' );
					?>
					</p>
					<h3><?php _e( 'Custom Filters', 'accessible-video-library' ); ?></h3>
					<p>
					<?php
						_e( 'Out of the box, Accessible Video Library supports captions, ogv and mp4 video formats, the addition of Spanish subtitles, and a YouTube video reference.' );
						echo ' ';
						_e( 'Using a custom WordPress filter, you can add support for more formats and languages.' );
					?>
					</p>
					<p>
					<?php
						// Translators: WordPress Codex link.
						printf( __( 'Read more about <a href="%s">WordPress filters</a>', 'accessible-video-player' ), 'http://codex.wordpress.org/Function_Reference/add_filter' );
					?>
					</p>
					<h3><?php _e( 'Add Video Formats', 'accessible-video-library' ); ?></h3>
<pre>
add_filter( 'avl_add_custom_fields', 'your_function_add_formats' );
/**
* Filter to insert or remove video formats.
* @return array Array of all post meta fields shown with video library post type.
*
**/
function your_function_add_formats( $fields ) {
	$fields['mov'] = array( 'label'=>'Video (.mov)', 'input'=>'upload', 'format'=>'mov','type'=>'video' );
	return $fields;
}
</pre>

					<h3><?php _e( 'Add Additional Languages', 'accessible-video-library' ); ?></h3>
<pre>
add_filter( 'avl_add_custom_fields', 'your_function_add_languages' );
function your_function_add_formats( $fields ) {
	$fields['de_DE'] = array( 'label'=>'German Subtitles (SRT/DFXP)', 'input'=>'upload', 'format'=>'srt','type'=>'subtitle' );
	return $fields;
}
</pre>
					</div>
				</div>
			</div>
			<div class="avl-support meta-box-sortables">
				<div class="postbox" id="get-support">
				<h2 class='hndle'><?php _e( 'Get Plug-in Support', 'accessible-video-library' ); ?></h2>
					<div class="inside">
					<?php avl_get_support_form(); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php avl_show_support_box(); ?>
</div>
	<?php
}

/**
 * Display support request form.
 */
function avl_get_support_form() {
	global $avl_version;
	$current_user = wp_get_current_user();
	// send fields for Accessible Video Library.
	$version = $avl_version;
	// send fields for all plugins.
	$wp_version = get_bloginfo( 'version' );
	$home_url   = home_url();
	$wp_url     = site_url();
	$language   = get_bloginfo( 'language' );
	$charset    = get_bloginfo( 'charset' );
	// server.
	$php_version = phpversion();

	// theme data.
	$theme         = wp_get_theme();
	$theme_name    = $theme->get( 'Name' );
	$theme_uri     = $theme->get( 'ThemeURI' );
	$theme_parent  = $theme->get( 'Template' );
	$theme_version = $theme->get( 'Version' );

	// plugin data.
	$plugins        = get_plugins();
	$plugins_string = '';
	foreach ( array_keys( $plugins ) as $key ) {
		if ( is_plugin_active( $key ) ) {
			$plugin          =& $plugins[ $key ];
			$plugin_name     = $plugin['Name'];
			$plugin_uri      = $plugin['PluginURI'];
			$plugin_version  = $plugin['Version'];
			$plugins_string .= "$plugin_name: $plugin_version; $plugin_uri\n";
		}
	}
	$data = "
================ Installation Data ====================
==Accessible Video Library:==
Version: $version

==WordPress:==
Version: $wp_version
URL: $home_url
Install: $wp_url
Language: $language
Charset: $charset

==Extra info:==
PHP Version: $php_version
Server Software: $_SERVER[SERVER_SOFTWARE]
User Agent: $_SERVER[HTTP_USER_AGENT]

==Theme:==
Name: $theme_name
URI: $theme_uri
Parent: $theme_parent
Version: $theme_version

==Active Plugins:==
$plugins_string
";
	if ( isset( $_POST['avl_support'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'accessible-video-library-nonce' ) ) {
			die( 'Security check failed' );
		}
		$request     = stripslashes( $_POST['support_request'] );
		$has_donated = ( isset( $_POST['has_donated'] ) && 'on' == $_POST['has_donated'] ) ? 'Donor' : 'No donation';
		$subject     = "Accessible Video Library support request. $has_donated";
		$message     = $request . "\n\n" . $data;
		// Get the site domain and get rid of www. from pluggable.php.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( 'www.' == substr( $sitename, 0, 4 ) ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;
		$from       = "From: \"$current_user->display_name\" <$from_email>\r\nReply-to: \"$current_user->display_name\" <$current_user->user_email>\r\n";

		wp_mail( 'plugins@joedolson.com', $subject, $message, $from );

		if ( 'Donor' == $has_donated ) {
			echo "<div class='message updated'><p>" . __( 'Thank you for supporting the continuing development of this plug-in! I\'ll get back to you as soon as I can.', 'accessible-video-library' ) . '</p></div>';
		} else {
			echo "<div class='message updated'><p>" . __( 'I\'ll get back to you as soon as I can, after dealing with any support requests from plug-in supporters.', 'accessible-video-library' ) . '</p></div>';
		}
	} else {
		$request = '';
	}
	echo "
	<form method='post' action='" . admin_url( 'edit.php?post_type=avl-video&page=avl-help' ) . "'>
		<div><input type='hidden' name='_wpnonce' value='" . wp_create_nonce( 'accessible-video-library-nonce' ) . "' /></div>
		<div>
		<p>" . __( 'Please note: I do keep records of those who have donated, but if your donation came from somebody other than your account at this web site, please note this in your message.', 'accessible-video-library' ) . "
		<p>
		<input type='checkbox' name='has_donated' id='has_donated' value='on' /> <label for='has_donated'>" . __( 'I have <a href="https://www.joedolson.com/donate/">made a donation to help support this plug-in</a>.', 'accessible-video-library' ) . "</label>
		</p>
		<p>
		<label for='support_request'>Support Request:</label><br /><textarea name='support_request' required aria-required='true' id='support_request' cols='80' rows='10' class='widefat'>" . stripslashes( $request ) . "</textarea>
		</p>
		<p>
		<input type='submit' value='" . __( 'Send Support Request', 'accessible-video-library' ) . "' name='avl_support' class='button-primary' />
		</p>
		<p>" . __( 'The following additional information will be sent with your support request:', 'accessible-video-library' ) . "</p>
		<div class='avl_support'>
		" . wpautop( $data ) . '
		</div>
		</div>
	</form>';
}

/**
 * Display request to donate & info box.
 */
function avl_show_support_box() {
	?>
<div class="postbox-container avl-narrow">
<div class="metabox-holder">
	<div class="meta-box-sortables">
		<div class="postbox">
		<h2 class='hndle'><?php _e( 'Support this Plug-in', 'accessible-video-library' ); ?></h2>
		<div id="support" class="inside resources">
		<ul>
			<li>
			<p>
				<a href="https://twitter.com/intent/follow?screen_name=joedolson" class="twitter-follow-button" data-size="small" data-related="joedolson">Follow @joedolson</a>
				<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if (!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
			</p>
			</li>
			<li><p><?php _e( '<a href="https://www.joedolson.com/donate/">Make a donation today!</a> Every donation counts - donate $5, $10, or $100 and help me keep this plug-in running!', 'accessible-video-library' ); ?></p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
					<div>
					<input type="hidden" name="cmd" value="_s-xclick" />
					<input type="hidden" name="hosted_button_id" value="WVDV542WW56KG" />
					<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="Donate" />
					<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
					</div>
				</form>
			</li>
			<li><a href="http://profiles.wordpress.org/joedolson/"><?php _e( 'Check out my other plug-ins', 'accessible-video-library' ); ?></a></li>
			<li><a href="http://wordpress.org/plugins/accessible-video-library/"><?php _e( 'Rate this plug-in', 'accessible-video-library' ); ?></a></li>
		</ul>
		</div>
		</div>
	</div>
</div>
</div>
	<?php
}

/**
 * Return post types defined by AVL
 *
 * @return array
 */
function avl_types() {
	$args = array(
		'public'              => true,
		'publicly_queryable'  => true,
		'exclude_from_search' => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_ui'             => true,
		'menu_icon'           => null,
		'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' ),
	);

	$avl_types = array(
		'avl-video' => array(
			__( 'video', 'accessible-video-library' ),
			__( 'videos', 'accessible-video-library' ),
			__( 'Video', 'accessible-video-library' ),
			__( 'Videos', 'accessible-video-library' ),
			$args,
		),
	);

	return $avl_types;
}

add_filter( 'avl_add_custom_fields', 'avl_add_basic_languages' );
/**
 * Set up the default language set for AVL
 *
 * @param array $fields Array of language fields.
 *
 * @return array
 */
function avl_add_basic_languages( $fields ) {
	if ( 'en-us' != get_bloginfo( 'language' ) ) {
		$fields['en-us'] = array(
			'label'  => __( 'US English Subtitles (SRT/DFXP)', 'accessible-video-library' ),
			'input'  => 'upload',
			'format' => 'srt',
			'type'   => 'subtitle',
		);
	}
	if ( 'es-ES' != get_bloginfo( 'language' ) ) {
		$fields['es_ES'] = array(
			'label'  => __( 'Spanish Subtitles (SRT/DFXP)', 'accessible-video-library' ),
			'input'  => 'upload',
			'format' => 'srt',
			'type'   => 'subtitle',
		);
	}

	return $fields;
}

/**
 * Add meta boxes.
 */
function avl_add_outer_box() {
	add_meta_box( 'avl_custom_div', __( 'Video Data', 'accessible-video-library' ), 'avl_add_inner_box', 'avl-video', 'side', 'high' );
	add_meta_box( 'avl_video', __( 'Video', 'accessible-video-library' ), 'avl_show_video', 'avl-video', 'normal', 'high' );
}

/**
 * Show video in editor.
 */
function avl_show_video() {
	global $post_id;
	if ( $post_id ) {
		echo avl_video( $post_id, 450, 640 );
	}

	return;
}

/**
 * Produce meta box.
 */
function avl_add_inner_box() {
	global $post_id;
	$fields = avl_fields();
	$format = sprintf( '<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />', 'mcm_nonce_name', wp_create_nonce( plugin_basename( __FILE__ ) ) );
	foreach ( $fields as $key => $value ) {
		$label   = $value['label'];
		$input   = $value['input'];
		$choices = ( isset( $value['choices'] ) ) ? $value['choices'] : false;
		$format .= avl_create_field( $key, $label, $input, $post_id, $choices );
	}
	$shortcode = "<div class='avl-shortcode'><label for='shortcode'>" . __( 'Shortcode', 'accessible-video-library' ) . ":</label> <input type='text' id='shortcode' readonly value='[avl_video id=\"$post_id\"]' /></div>";
	echo '<div class="avl_post_fields">' . $shortcode . $format . '</div>';
}

/**
 * Generate options given array of choices.
 *
 * @param array  $choices Set of items to choose from.
 * @param string $selected Value currently selected.
 *
 * @return string
 */
function avl_create_options( $choices, $selected ) {
	$return = '';
	if ( is_array( $choices ) ) {
		foreach ( $choices as $value ) {
			$v       = esc_attr( $value );
			$chosen  = ( $v == $selected ) ? ' selected="selected"' : '';
			$return .= "<option value='$value'$chosen>$value</option>";
		}
	}

	return $return;
}

add_action( 'wp_enqueue_scripts', 'avl_enqueue_scripts' );
/**
 * Enqueue scripting and styles for AVL.
 */
function avl_enqueue_scripts() {
	wp_register_style( 'avl-mediaelement', plugins_url( 'css/avl-mediaelement.css', __FILE__ ) );
	wp_enqueue_style( 'avl-mediaelement' );
	wp_deregister_script( 'wp-mediaelement' );
	wp_register_script( 'wp-mediaelement', plugins_url( 'js/avl-mediaelement.js', __FILE__ ), array( 'jquery', 'mediaelement' ) );
	$args = apply_filters( 'avl_mediaelement_args', array(
		'pluginPath'         => includes_url( 'js/mediaelement/', 'relative' ),
		'alwaysShowControls' => 'true',
	) );
	wp_localize_script( 'wp-mediaelement', '_avlmejsSettings', $args );
}

add_filter( 'avl_mediaelement_args', 'avl_options' );
/**
 * Filter default startlanguage used in MediaElement.
 *
 * @param array $args Default arguments.
 *
 * @return array
 */
function avl_options( $args ) {
	if ( '' != get_option( 'avl_default_caption' ) ) {
		$args['startLanguage'] = strtolower( get_option( 'avl_default_caption' ) );
	}

	return $args;
}

add_action( 'admin_enqueue_scripts', 'avl_enqueue_admin_scripts' );
/**
 * Enqueue scripts required by AVL in the admin.
 */
function avl_enqueue_admin_scripts() {
	$screen = get_current_screen();
	if ( 'post' == $screen->base ) {
		if ( function_exists( 'wp_enqueue_media' ) && ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
		wp_enqueue_script( 'avl-admin-script', plugins_url( 'js/uploader.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'avl-admin-script', 'baseUrl', home_url() );
	}
}

/**
 * Create a custom field used in meta boxes.
 *
 * @param string $key Post meta field name.
 * @param string $label Field label.
 * @param string $type Type of field.
 * @param int    $post_id Post ID where displayed.
 * @param array  $choices Available choices for select or checkbox groups.
 *
 * @return string
 */
function avl_create_field( $key, $label, $type, $post_id, $choices = false ) {
	$value  = false;
	$custom = esc_attr( get_post_meta( $post_id, '_' . $key, true ) );

	switch ( $type ) {
		case 'text':
			$value = '
			<div>
				<label for="_' . $key . '">' . $label . '</label><br />' . '<input style="width: 80%;" type="text" name="_' . $key . '" value="' . $custom . '" />
			</div>';
			break;
		case 'upload':
			$value = '
			<div class="field-holder"><label for="_' . $key . '">' . $label . '</label><br />' . '<input style="width: 70%;" type="text" class="textfield" name="_' . $key . '" value="' . $custom . '" id="_' . $key . '" /> <a href="#" class="button textfield-field">' . __( 'Upload', 'accessible-video-library' ) . '</a>
				<div class="selected"></div>
			</div>' . "\n";
			break;
		case 'select':
			$value = '
			<div>
				<label for="_' . $key . '">' . $label . '</label><br />' . '<select name="_' . $key . '">' .
					avl_create_options( $choices, $custom ) .
				'</select>
			</div>';
			break;
	}

	return $value;
}
add_action( 'admin_menu', 'avl_add_outer_box' );

add_action( 'save_post', 'avl_post_meta', 10 );
/**
 * Handle saving of post meta data.
 *
 * @param int $id Post ID.
 */
function avl_post_meta( $id ) {
	$fields = avl_fields();
	if ( isset( $_POST['_inline_edit'] ) ) {
		return;
	}
	foreach ( $fields as $key => $value ) {
		if ( isset( $_POST[ '_' . $key ] ) ) {
			$value = $_POST[ '_' . $key ];
			update_post_meta( $id, '_' . $key, $value );
		}
	}
	// for post screen filters.
	if ( '' == get_post_field( 'post_content', $id, 'raw' ) ) {
		add_post_meta( $id, '_notranscript', 'true' );
	} else {
		delete_post_meta( $id, '_notranscript' );
	}
}

/**
 * Register custom post types for AVL.
 */
function avl_posttypes() {
	$types   = avl_types();
	$enabled = array( 'avl-video' );
	if ( is_array( $enabled ) ) {
		foreach ( $enabled as $key ) {
			$value  =& $types[ $key ];
			$labels = array(
				'name'               => $value[3],
				'singular_name'      => $value[2],
				'add_new'            => __( 'Add New', 'accessible-video-library' ),
				'add_new_item'       => __( 'Create New Video', 'accessible-video-library' ),
				'edit_item'          => __( 'Modify Video', 'accessible-video-library' ),
				'new_item'           => __( 'New Video', 'accessible-video-library' ),
				'view_item'          => __( 'View Video', 'accessible-video-library' ),
				'search_items'       => __( 'Search Videos', 'accessible-video-library' ),
				'not_found'          => __( 'No videos found', 'accessible-video-library' ),
				'not_found_in_trash' => __( 'No videos found in Trash', 'accessible-video-library' ),
				'parent_item_colon'  => '',
			);
			$raw    = $value[4];
			$args   = array(
				'labels'              => $labels,
				'public'              => $raw['public'],
				'publicly_queryable'  => $raw['publicly_queryable'],
				'exclude_from_search' => $raw['exclude_from_search'],
				'show_ui'             => $raw['show_ui'],
				'show_in_menu'        => $raw['show_in_menu'],
				'show_ui'             => $raw['show_ui'],
				'menu_icon'           => plugins_url( 'images', __FILE__ ) . '/avl-video.png',
				'query_var'           => true,
				'rewrite'             => array(
					'with_front' => false,
					'slug'       => 'avl-video',
				),
				'hierarchical'        => false,
				'supports'            => $raw['supports'],
			);
			register_post_type( $key, $args );
		}
	}
}

/**
 * Field messages for post types.
 *
 * @param array $messages Existing array of messages.
 *
 * @return array
 */
function avl_posttypes_messages( $messages ) {
	global $post, $post_ID;
	$types   = avl_types();
	$enabled = array( 'avl-video' );
	if ( is_array( $enabled ) ) {
		foreach ( $enabled as $key ) {
			$value            = $types[ $key ];
			$messages[ $key ] = array(
				0  => '', // Unused. Messages start at index 1.
				// Translators: Video URL.
				1  => sprintf( __( 'Video updated. <a href="%s">View video</a>' ), esc_url( get_permalink( $post_ID ) ) ),
				2  => __( 'Custom field updated.' ),
				3  => __( 'Custom field deleted.' ),
				4  => __( 'Video updated.' ),
				// translators: %s: date and time of the revision.
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Video restored to revision from %2$s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				// Translators: Video URL.
				6  => sprintf( __( 'Video published. <a href="%s">View video</a>' ), esc_url( get_permalink( $post_ID ) ) ),
				7  => __( 'Video saved.' ),
				// Translators: Preview URL.
				8  => sprintf( __( 'Video submitted. <a target="_blank" href="%s">Preview video</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
				// Translators: Date, preview URL.
				9  => sprintf( __( 'Video scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview video</a>' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
				// Translators: Preview URL.
				10 => sprintf( __( 'Video draft updated. <a target="_blank" href="%s">Preview video</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			);
		}
	}

	return $messages;
}

/**
 * Define AVL taxonomies.
 */
function avl_taxonomies() {
	register_taxonomy(
		'avl_category_avl-video',
		array( 'avl-video' ),
		array(
			'hierarchical' => true,
			'label'        => __( 'Video Categories', 'accessible-video-library' ),
			'query_var'    => true,
			'rewrite'      => array(
				'slug' => 'avl-video-group',
			),
		)
	);
}

add_filter( 'the_content', 'avl_replace_content', 10, 2 );
/**
 * Automatically replace content with template for videos.
 *
 * @param string $content Default post content.
 * @param int    $id Post ID.
 *
 * @return new content.
 */
function avl_replace_content( $content, $id = false ) {
	if ( ! is_main_query() && ! $id ) {
		return $content;
	}
	if ( is_singular( 'avl-video' ) && ! isset( $_GET['transcript'] ) ) {
		$id = get_the_ID();

		return avl_video( $id );
	} else {

		return $content;
	}
}


/**
 * Get a single custom field from a video object.
 *
 * @param string  $field Field name.
 * @param integer $id Post ID.
 *
 * @return mixed value
 */
function avl_get_custom_field( $field, $id = '' ) {
	global $post;
	$id           = ( '' != $id ) ? $id : $post->ID;
	$custom_field = get_post_meta( $id, $field, true );

	return $custom_field;
}

/**
 * Get a single video.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function avl_get_video( $atts ) {
	$args = shortcode_atts( array(
		'id'     => '',
		'height' => false,
		'width'  => false,
	), $atts, 'avl_video' );

	return avl_video( $args['id'], $args['height'], $args['width'] );
}

/**
 * Shortcode handler for avl media list.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function avl_get_media( $atts, $content = null ) {
	$args = shortcode_atts( array(
		'category' => '',
		'header'   => 'h4',
		'orderby'  => 'menu_order',
		'order'    => 'asc',
		'height'   => false,
		'width'    => false,
	), $atts, 'avl_media' );

	return avl_media( $args['category'], $args['header'], $args['orderby'], $args['order'], $args['height'], $args['width'] );
}

// add shortcode interpreter.
add_shortcode( 'avl_video', 'avl_get_video' );
add_shortcode( 'avl_media', 'avl_get_media' );
/**
 * Execute avl media list shortcode.
 *
 * @param string  $category Category slug.
 * @param string  $header Header level.
 * @param string  $orderby Ordering field.
 * @param string  $order Asc/desc.
 * @param integer $height Height in px.
 * @param integer $width width in px.
 *
 * @return string
 */
function avl_media( $category, $header = 'h4', $orderby = 'menu_order', $order = 'asc', $height = false, $width = false ) {
	$args                = array(
		'post_type' => 'avl-video',
		'orderby'   => $orderby,
		'order'     => $order,
	);
	$args['numberposts'] = -1;
	$media               = '';
	if ( $category ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'avl_category_avl-video',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);
	}
	$posts = get_posts( $args );
	foreach ( $posts as $p ) {
		$permalink = get_permalink( $p->ID );
		$media    .= "\n
		<div class='avl-video avl-video-$p->ID'>
			<$header><a href='$permalink'>$p->post_title</a></$header>
			<div class='avl-video-description'>
				<div class='avl-video-thumbnail'>" . avl_video( $p->ID, 135, 240 ) . '</div>
				' . wpautop( $p->post_excerpt ) . '
			</div>
		</div>';
	}

	return $media;
}

/**
 * Test whether this is a validly formatted URL.
 *
 * @param string $url Potential URL.
 *
 * @return mixed string/boolean URL or false.
 */
function avl_is_url( $url ) {
	return preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url );
}

/**
 * Executes custom video shortcode and parses videos.
 *
 * @param integer $id Post (video) ID.
 * @param integer $height Height in px.
 * @param integer $width Width in px.
 *
 * @return string
 */
function avl_video( $id, $height = false, $width = false ) {
	global $content_width;
	$fields    = avl_fields();
	$yt_url    = false;
	$image     = false;
	$has_video = false;
	if ( ! is_numeric( $id ) ) {
		$video = get_page_by_title( $id, OBJECT, 'avl-video' );
		$id    = $video->ID;
	}
	$youtube = avl_get_custom_field( '_external', $id );
	$vimeo   = avl_get_custom_field( '_vimeo', $id );

	if ( $youtube && avl_is_url( $youtube ) ) {
		$yt_url = $youtube;
	} elseif ( $youtube && ! avl_is_url( $youtube ) ) {
		$yt_url = "http://youtu.be/$youtube";
	}

	if ( $vimeo && avl_is_url( $vimeo ) ) {
		$vm_url = $vimeo;
	} elseif ( $vimeo && ! avl_is_url( $vimeo ) ) {
		$vm_url = 'http://vimeo.com/' . $vimeo;
	}

	$params = '';
	$first  = true;
	foreach ( $fields as $k => $field ) { // need to id videos.
		if ( 'video' == $field['type'] && 'external' != $k ) {
			$format             = ( $first ) ? 'src' : $field['format'];
			${$field['format']} = avl_get_custom_field( '_' . $field['format'], $id );
			if ( ${$field['format']} ) {
				$params   .= $format . '="' . ${$field['format']} . '" ';
				$has_video = true;
			}
			$first = false;
		}
	}
	if ( has_post_thumbnail( $id ) ) {
		$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'thumbnail_name' );
		$image = $thumb[0]; // thumbnail url.
	}
	if ( ! $image && $youtube ) {
		$replace = array( 'http://youtu.be/', 'http://www.youtube.com/watch?v=', 'https://youtu.be/', 'https://www.youtube.com/watch?v=' );
		$youtube = str_replace( $replace, '', $youtube );
		$image   = "//img.youtube.com/vi/$youtube/0.jpg";
	}

	if ( ! $image && $vimeo ) {
		if ( get_post_meta( $id, '_vimeo_poster', true ) ) {
			$image = get_post_meta( $id, '_vimeo_poster', true );
		} else {
			$replace = array( 'http://vimeo.com/', 'https://vimeo.com/' );
			$vimeo   = str_replace( $replace, '', $vimeo );
			$data    = wp_remote_get( 'http://vimeo.com/api/v2/video/' . $vimeo . '.json' );
			$data    = json_decode( $data['body'] );
			$image   = str_replace( 'http://', '//', $data[0]->thumbnail_large );
			add_post_meta( $id, '_vimeo_poster', $image );
		}
	}
	// $audio_desc = avl_get_custom_field( '_audio_desc', $id ); MediaElements.js does not support audio description.
	$captions = avl_get_custom_field( '_captions', $id );
	$content  = get_post_field( 'post_content', $id );

	if ( $content ) {
		// Translators: Post title.
		$transcript = "<a href='" . add_query_arg( 'transcript', 'true', get_permalink( $id ) ) . "' class='video-transcript-link'>" . sprintf( __( 'Transcript<span class="screen-reader-text"> to %s</span>', 'accessible-video-library' ), get_post_field( 'post_title', $id ) ) . '</a>';
	} else {
		$transcript = '';
	}

	$transcript = apply_filters( 'avl_transcript_link', $transcript, $id, get_post_field( 'post_title', $id ) );
	// player selector in settings.
	// to test YouTube, need to not have any video attached (WP auto uses first attached vid].
	if ( 'true' == get_option( 'avl_responsive' ) && ! is_admin() ) {
		$height = '100%';
		$width  = '100%';
	}

	if ( $height && $width ) {
		$params .= " height='$height' width='$width'";
	} else {
		$params .= " height='360' width='640'";
	}

	if ( $youtube ) {
		$params .= " src='$yt_url'";
	}

	if ( $vimeo ) {
		$params .= " src='$vm_url'";
	}

	if ( 'true' == get_option( 'avl_responsive' ) && ! is_admin() ) {
		$vid  = do_shortcode( "[video $params poster='$image']" );
		$html = str_replace( array( 'px;', 'width="100"', 'height="100"' ), array( '%;', 'width="100%"', 'height="100%"' ), $vid );
	} else {
		$html = do_shortcode( "[video $params poster='$image']" );
	}

	$html = apply_filters( 'avl_implementation', $html, $id, $captions, $yt_url ) . $transcript;

	return $html;
}

add_filter( 'avl_implementation', 'avl_add_a11y', 10, 4 );
/**
 * Insert accessibility related tracks into video element
 *
 * @param string  $html Source HTML.
 * @param integer $id Video ID.
 * @param string  $captions Captions source URL.
 * @param string  $youtube Youtube ID.
 *
 * @return string
 */
function avl_add_a11y( $html, $id = false, $captions = '', $youtube = '' ) {
	$fields = avl_fields();
	if ( $captions ) {
		if ( is_ssl() ) {
			$captions = str_replace( 'http:', 'https:', $captions );
		}
		if ( ! is_ssl() ) {
			$captions = str_replace( 'https:', 'http:', $captions );
		}
		$html = str_replace( '</video>', '<track kind="subtitles" src="' . $captions . '" label="' . __( 'Captions', 'accessible-video-library' ) . '" srclang="' . get_bloginfo( 'language' ) . '" /></video>', $html );
	}

	foreach ( $fields as $key => $field ) {
		if ( 'subtitle' == $field['type'] ) {
			$label = esc_attr( $field['label'] );
			$value = get_post_meta( $id, '_' . $key, true );
			if ( is_ssl() ) {
				$value = str_replace( 'http:', 'https:', $value );
			}
			if ( ! is_ssl() ) {
				$value = str_replace( 'https:', 'http:', $value );
			}
			if ( $value ) {
				$html = str_replace( '</video>', '<track kind="subtitles" src="' . $value . '" label="' . $label . '" srclang="' . $key . '" /></video>', $html );
			}
		}
	}

	return $html;
}

add_filter( 'get_media_item_args', 'avl_custom' );
/**
 * Add custom media item argument.
 *
 * @param array $args Existing arguments.
 *
 * @return array
 */
function avl_custom( $args ) {
	$args['send'] = true;

	return $args;
}

add_filter( 'upload_mimes', 'avl_custom_mimes' );
/**
 * Add custom mime types to allow srt and dfxp caption files.
 *
 * @param array $mimes Allowed mime types.
 *
 * @return array
 */
function avl_custom_mimes( $mimes = array() ) {
	$mimes['srt']  = 'text/plain';
	$mimes['dfxp'] = 'application/ttaf+xml';

	return $mimes;
}

/**
 * Add custom columns.
 *
 * @param array $cols All columns.
 *
 * @return array
 */
function avl_column( $cols ) {
	$cols['avl_captions']   = __( 'Captions', 'accessible-video-library' );
	$cols['avl_transcript'] = __( 'Transcript', 'accessible-video-library' );
	$cols['avl_id']         = __( 'ID', 'accessible-video-library' );

	return $cols;
}

/**
 * Display custom column information.
 *
 * @param string  $column_name Column name.
 * @param integer $id Post ID.
 */
function avl_custom_column( $column_name, $id ) {
	$no  = __( 'No', 'accessible-video-library' );
	$yes = __( 'Yes', 'accessible-video-library' );
	switch ( $column_name ) {
		case 'avl_captions':
			$srt   = get_post_meta( $id, '_captions', true );
			$notes = "<span class='avl no-captions'>$no</span>";
			if ( $srt ) {
				$notes = "<span class='avl has-captions'>$yes</span>";
			}
			echo $notes;
			break;
		case 'avl_transcript':
			$transcript = get_post_field( 'post_content', $id );
			$notes      = "<span class='avl no-transcript'>$no</span>";
			if ( $transcript ) {
				$notes = "<span class='avl has-transcript'>$yes</span>";
			}
			echo $notes;
			break;
		case 'avl_id':
			echo $id;
			break;
	}
}

/**
 * Display value in custom video columns.
 *
 * @param string  $value Value to show.
 * @param string  $column_name Column name.
 * @param integer $id Post ID.
 *
 * @return value.
 */
function avl_return_value( $value, $column_name, $id ) {
	if ( 'avl_captions' == $column_name || 'avl_transcript' == $column_name || 'avl_id' == $column_name ) {
		$value = $id;
	}

	return $value;
}

/**
 * Output CSS for width of new column
 */
function avl_css() {
	?>
<style type="text/css">
#avl_captions, #avl_transcript { width: 70px; }
#avl_id { width: 50px; }
.avl_captions, .avl_transcript { text-align: center; vertical-align: middle; }
.avl { color: #fff; padding: 2px 4px; border-radius: 3px; width: 3em; display: inline-block; box-shadow: 1px 1px #333; }
.no-transcript, .no-captions { background: #c00; }
.has-transcript, .has-captions { background: #070;}
.avl-shortcode { padding: 4px; background: #fff; margin-bottom: 4px; }
.avl-shortcode label { font-weight: 700; }
.avl-shortcode input { border: none; font-size: 1.2em; }
</style>
	<?php
}

add_action( 'admin_init', 'avl_add' );
/**
 * Add custom columns to video posts list.
 */
function avl_add() {
	add_action( 'admin_head', 'avl_css' );
	add_filter( 'manage_avl-video_posts_columns', 'avl_column' );
	add_action( 'manage_avl-video_posts_custom_column', 'avl_custom_column', 10, 2 );
}

add_filter( 'pre_get_posts', 'avl_filter_videos' );
/**
 * Filter video listing by transcript & captions.
 *
 * @param object $query WP Query.
 *
 * @return object
 */
function avl_filter_videos( $query ) {
	global $pagenow;
	if ( ! is_admin() ) {
		return;
	}

	$qv = &$query->query_vars;

	if ( 'edit.php' == $pagenow && ! empty( $qv['post_type'] ) && 'avl-video' == $qv['post_type'] ) {
		if ( empty( $_GET['avl_filter'] ) ) {
			return;
		}

		if ( 'transcripts' == $_GET['avl_filter'] ) {
			$query->set( 'meta_query', array(
				array(
					'key'     => '_notranscript',
					'value'   => 'true',
					'compare' => '=',
				),
			) );
		} elseif ( 'captions' == $_GET['avl_filter'] ) {
			$query->set( 'meta_query', array(
				array(
					'key'     => '_captions',
					'value'   => '',
					'compare' => '=',
				),
			) );
		}
	}
}

add_action( 'restrict_manage_posts', 'avl_filter_dropdown' );
/**
 * Add a filter to posts screen to identify files with captions or transcripts.
 */
function avl_filter_dropdown() {
	global $wp_query, $typenow;
	if ( 'avl-video' == $typenow ) {
		$post_type = get_post_type_object( $typenow );
		if ( isset( $_GET['avl_filter'] ) ) {
			$captions    = ( 'captions' == $_GET['avl_filter'] ) ? ' selected="selected"' : '';
			$transcripts = ( 'transcripts' == $_GET['avl_filter'] ) ? ' selected="selected"' : '';
		} else {
			$captions    = '';
			$transcripts = '';
		}
		?>
		<select class="postform" id="avl_filter" name="avl_filter">
			<option value="all"><?php _e( 'All videos', 'accessible-video-library' ); ?></option>
			<option value="captions"<?php echo $captions; ?>><?php _e( 'Videos missing Captions', 'accessible-video-library' ); ?></option>
			<option value="transcripts"<?php echo $transcripts; ?>><?php _e( 'Videos missing Transcripts', 'accessible-video-library' ); ?></option>
		</select>
		<?php
	}
}
