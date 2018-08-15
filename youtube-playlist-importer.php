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
			<label for="the_post_type">The post type to insert videos</label><br>
			<input class="hk_input" type="text" name="the_post_type"><br>
			<label for="cateory_id">Assign a category for the post <em>Must be the category ID</em></label><br>
			<input class="hk_input" type="number" name="cateory_id"><br>
			<button type="submit">Import Playlist Now</button>
		</form>';
}

if(isset($_POST['apikey']) && isset($_POST['playlist_id']) && isset($_POST['the_post_type']) && isset($_POST['cateory_id']) ){
	$apikey = $_POST['apikey'];
	$playlist_ID = $_POST['playlist_id'];
	$post_type = $_POST['the_post_type'];
	$category_ID = $_POST['cateory_id'];

	echo $apikey.' - '.$playlist_ID.' - '.$post_type.' - '.$category_ID;
	//MAIN FUNCTION - GET AND INSERT PLAYLIST VIDEOS FROM YOUTUBE
	function ytVideos($_playlist, $_apikey){
		$playlists = array(
			'leitnait'=>'PLN-rg-jk5_jXFWQyyGfROfdPbxqtEF-q5',
			'mornin'=>'PLN-rg-jk5_jVOYRGT_bc0i6LtFttBZOD6'
		);
		// $apikey = 'AIzaSyA2BYsmHfnIeoY5l2-DmeCyeo0uwAwwFZM';
		$this_apikey = $_apikey;
		$this_playlistID = $_playlist;
		$this_apiUrl = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=25&playlistId='.$this_playlistID.'&key='.$this_apikey;
		$this_playlist = json_decode(file_get_contents($this_apiUrl));

		return $this_playlist;
	}//END yt Videos

	// print_r();
	$thevideoList = ytVideos($playlist_ID, $apikey);
	// print_r($thevideoList);

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

	function insertYoutubeVideoAsPost($videoList, $categ, $pt){
		$count = 0;
		$video = $videoList->items[0];
		// foreach ($videoList->items as $video):
			$post_ID = -1;
			$slug = sanitize_title($video->snippet->title);
			$thumb_url = $video->snippet->thumbnails->maxres->url;

			if(!postExistBySlug($slug)){
				$postArr = array(
					'post_type'=>$pt,
					'post_author'=>7,
					'post_title'=>$video->snippet->title,
					'post_name'=>$slug,
					'post_content'=>$video->snippet->description,
					'post_category'=> array($categ)
				);
				$post_ID = wp_insert_post($postArr, true);

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

			}
		// endforeach;
	}//END insert Youtube Video As Post

	insertYoutubeVideoAsPost($thevideoList, $category_ID, $post_type); 




}//END ISSET POST





function argumentFn(){
	$argument = ytVideos('mornin');
	do_action('run_insert_post', $argument);
}//END argument Fn

// add_action('publish_post', 'argumentFn');
// add_action('run_insert_post', 'insertYoutubeVideoAsPost');






// DEREGISTERING PLUGIN
function hk_deleteMetabox(){
	// delete metabox in all post types
}

function hk_pluginDeactivation(){
	hk_deleteMetabox();
}
register_deactivation_hook(__FILE__, 'hk_pluginDeactivation');