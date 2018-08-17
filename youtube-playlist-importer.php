<?php
/*
Plugin Name:  Youtube Playlist Importer
Plugin URI:   https://github.com/HelaGone/WP-PlaylistImporter
Description:  Import youtube playlist and insert videos into post
Version:      0.0.1
Author:       cubeinthebox
Author URI:   https://cubeithebox.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/
require_once( ABSPATH . "wp-includes/pluggable.php" );
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

define( 'WP_TYP_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
// INCLUDE CSS FOR THE PLUGIN
function hk_plugin_scripts() {
    wp_enqueue_style( 'wp-seo-admin', WP_TYP_URL . 'css/style.css', array(), '0.0.1' );
    wp_enqueue_style( 'yt-styles' );
}
add_action( 'admin_enqueue_scripts', 'hk_plugin_scripts' );


//ADDING NECESSARY META FOR VIDEO ID
function hk_setupMetabox(){
	add_action('add_meta_boxes', 'hk_metabox_fn');
	// METABOX CALLBACK
	function hk_metabox_fn(){
		add_meta_box('youyube_video_id', 'Youtube Video ID', 'hk_metabox_ui', 'post', 'side', 'high');
	}

	// CREATE METABOX UI 
	function hk_metabox_ui($post){
		$hk_sources_youtube = get_post_meta($post->ID, 'hk_sources_youtube', true);
		wp_nonce_field(__FILE__, 'post_video_meta_nonce');
		echo "<input type='text' class='widefat' id='hk_sources_youtube' name='hk_sources_youtube' value='$hk_sources_youtube'/>";
		echo "<br/><br />";
	}

	// SAVE METABOX
	add_action('save_post', function($post_id){
		if ( isset( $_POST['hk_sources_youtube'] ) ){
			update_post_meta($post_id, 'hk_sources_youtube', $_POST['hk_sources_youtube']);
		}
	});
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
		<form id="hk_plugin_form" action="" method="post">
			<label for="apikey">Youtube Api Key</label><br>
			<input class="hk_input" type="text" name="apikey"><br>
			<label for="playlist_id">Youtube Playlist ID</label><br>
			<input class="hk_input" type="text" name="playlist_id"><br>
			<label for="cateory_id">Assign a category for the post <em>Must be the category ID</em></label><br>
			<input class="hk_input" type="number" name="cateory_id"><br>
			<button type="submit">Import Playlist Now</button>
		</form>';
}

if(isset($_POST['apikey']) && isset($_POST['playlist_id']) && isset($_POST['cateory_id']) ){
	$the_apikey = $_POST['apikey'];
	$the_list_id = $_POST['playlist_id'];
	$the_categ = $_POST['cateory_id'];

	//YOUTUBE
	function ytVideos($apiK, $plId){
		// $playlists = array(
		// 	'leitnait'=>'PLN-rg-jk5_jXFWQyyGfROfdPbxqtEF-q5',
		// 	'mornin'=>'PLN-rg-jk5_jVOYRGT_bc0i6LtFttBZOD6'
		// );
		// $apikey = 'AIzaSyA2BYsmHfnIeoY5l2-DmeCyeo0uwAwwFZM';
		// $playlistID = $playlists[$pl];

		$apikey = $apiK;
		$playlistID = $plId;

		$apiUrl = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=25&playlistId='.$playlistID.'&key='.$apikey;
		$playlist = json_decode(file_get_contents($apiUrl));

		return $playlist;
	}//END ty Videos


	function argumentFn($apikey, $list_id, $catego){
		$vList = ytVideos($apikey, $list_id);
		$cat = $catego;

		insertYoutubeVideoAsPost($vList, $cat);

		// add_action('run_insert_post', 'insertYoutubeVideoAsPost', 10, 2);
		// do_action('run_insert_post', $argument, $argument2);

	}//END argument Fn

	function insertYoutubeVideoAsPost($videoList, $category){
		$count = 0;
		// $video = $videoList->items[1];
		foreach ($videoList->items as $video):
			# code...
			$post_ID = -1;
			$slug = sanitize_title($video->snippet->title);
			$thumb_url = $video->snippet->thumbnails->maxres->url;
			$video_ID = $video->snippet->resourceId->videoId;


			if(!postExistBySlug($slug)){
				// echo $video_ID.'<br>';

				$postArr = array(
					'post_type'=>'post',
					'post_author'=>7,
					'post_title'=>$video->snippet->title,
					'post_name'=>$slug,
					'post_content'=>$video->snippet->description,
					'post_category'=> array($category),
					'post_status'=>'publish'
				);

				if(is_user_logged_in()){
					$post_ID = wp_insert_post($postArr, true);
					//UPDATE POST META

					if($video_ID != ''){
						update_post_meta($post_ID, 'hk_sources_youtube', $video_ID);
					}

					//UPLOAD & INSERT POST THUMBNAIL

					$media = media_sideload_image($thumb_url, $post_ID);
					if(!empty($media) && !is_wp_error($media)){
					    $args = array(
					        'post_type' => 'attachment',
					        'posts_per_page' => -1,
					        'post_status' => 'any',
					        'post_parent' => $post_ID
					    );
					    $attachments = get_posts($args);
					    if(isset($attachments) && is_array($attachments)){
					        foreach($attachments as $attachment){
					            $image = wp_get_attachment_image_src($attachment->ID, 'full');
					            if(strpos($media, $image[0]) !== false){
					                set_post_thumbnail($post_ID, $attachment->ID);
					                break;
					            }
					        }
					    }
					}
				}//End is user is logged in
			}
		endforeach;
	}//END insert Youtube Video As Post

	function postExistBySlug($post_slug){
		$args = array(
			'post_type'=>'post',
			'post_status'=>'any',
			'name'=> $post_slug,
			'posts_per_page'=>1
		);
		$result = new WP_Query($args);
		if(!$result->have_posts()){
			return false;
		}else{
			$result->the_post();
			return $result->post->ID;
		}
	}//END post Exist By Slug

	add_action('argument_action', 'argumentFn', 10, 3);
	do_action('argument_action', $the_apikey, $the_list_id, $the_categ);
}



// DEREGISTERING PLUGIN
function hk_deleteMetabox(){
	// delete metabox in all post types
}

function hk_pluginDeactivation(){
	hk_deleteMetabox();
}
register_deactivation_hook(__FILE__, 'hk_pluginDeactivation');