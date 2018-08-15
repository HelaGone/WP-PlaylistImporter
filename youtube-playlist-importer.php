<?php
/*
Plugin Name:  Youtube Playlist Importer
Plugin URI:   
Description:  Import youtube playlist and insert videos into post
Version:      0.0.1
Author:       Holkan Luna
Author URI:   https://cubeithebox.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

define( 'WP_TYP_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
// INCLUDE CSS FOR THE PLUGIN
function hk_plugin_scripts() {
    wp_enqueue_style( 'wp-seo-admin', WP_TYP_URL . 'css/style.css', array(), '0.0.1' );
    wp_enqueue_style( 'yt-styles' );
}
add_action( 'admin_enqueue_scripts', 'hk_plugin_scripts' );


//ADDING NECESARY META FOR VIDEO ID
function hk_setupMetabox(){
	// Set metabox for post types
}
add_action('init', 'hk_setupMetabox');

// REGISTERING PLUGIN
function hk_pluginInstall(){
	hk_setupMetabox();
}
register_activation_hook(__FILE__, 'hk_pluginInstall');




// SETTING UP MENU FOR PLUGIN
add_action('admin_menu', 'hk_setupMenu');
function hk_setupMenu(){
	add_menu_page('Importer Plugin', 'Playlist Importer', 'publish_posts', 'yt-playlist-importer', 'hk_inputFormData', 'dashicons-video-alt3', null);
}

//PLUGIN GUI 
function hk_inputFormData(){
	echo '<h1>Import Youtube Videos</h1>';

	echo '
		<form id="hk_plugin_form" action="'.plugin_dir_url( __FILE__ ).'importer.php" method="post">
			<label for="apikey">Youtube Api Key</label><br>
			<input class="hk_input" type="text" name="apikey"><br>
			<label for="playlist_id">Youtube Playlist ID</label><br>
			<input class="hk_input" type="text" name="playlist_id"><br>
			<label for="the_post_type">The post type to insert videos</label><br>
			<input class="hk_input" type="text" name="the_post_type"><br>
			<label for="cateory_id">Assign a category for the post <em>Must be the category ID</em></label><br>
			<input class="hk_input" type="number" name="cateory_id"><br>
			<button type="submit">Import Playlist Now</button>
		</form>';
}

// DEREGISTERING PLUGIN
function hk_deleteMetabox(){
	// delete metabox in all post types
}

function hk_pluginDeactivation(){
	hk_deleteMetabox();
}
register_deactivation_hook(__FILE__, 'hk_pluginDeactivation');