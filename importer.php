<?php

if(isset($_POST['apikey']) ){
	echo $_POST['apikey'];
}

/*
function ytVideos($pl){
	$playlists = array(
		'leitnait'=>'PLN-rg-jk5_jXFWQyyGfROfdPbxqtEF-q5',
		'mornin'=>'PLN-rg-jk5_jVOYRGT_bc0i6LtFttBZOD6'
	);
	$apikey = 'AIzaSyA2BYsmHfnIeoY5l2-DmeCyeo0uwAwwFZM';
	$playlistID = $playlists[$pl];
	$apiUrl = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=25&playlistId='.$playlistID.'&key='.$apikey;
	$playlist = json_decode(file_get_contents($apiUrl));

	return $playlist;
}//END ty Videos

function argumentFn(){
	$argument = ytVideos('mornin');
	do_action('run_insert_post', $argument);
}//END argument Fn

function insertYoutubeVideoAsPost($videoList){
	$count = 0;
	$video = $videoList->items[0];
	// foreach ($videoList->items as $video):
		$post_ID = -1;
		$slug = sanitize_title($video->snippet->title);
		$thumb_url = $video->snippet->thumbnails->maxres->url;

		if(!postExistBySlug($slug)){
			$postArr = array(
				'post_type'=>'post',
				'post_author'=>7,
				'post_title'=>$video->snippet->title,
				'post_name'=>$slug,
				'post_content'=>$video->snippet->description,
				'post_category'=> array(21205)
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

// insertYoutubeVideoAsPost(ytVideos('leitnait'));

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

// add_action('publish_post', 'argumentFn');
// add_action('run_insert_post', 'insertYoutubeVideoAsPost');
*/
