<?php

/*
Plugin Name: Popularity Posts Widget
Plugin URI: http://wordpress.org/extend/plugins/popularity-posts-widget/
Description: Allows you to show your most viewed posts as a widget on your blog.
Version: 1.12
Author: Illidan
Author URI: 
*/

/*  Copyright 2013 

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

require ('class_popularity-posts-widget.php');

add_action('wp_head', 'ppw_start_PostViews');

add_action('widgets_init', create_function('', 
'return register_widget("PopularityPostsWidget");'));

add_action('init', 'popularity_posts_widget_init');
/*
add_action('init', 'ppw_count_only_unique');

// Function to set cookies if unique views filter is ON
function ppw_count_only_unique() {
	$show_unique = get_option('show_unique');
	if ($show_unique === 'true') {
		$show_id = get_option('id');
		settype($show_id, "integer");
	//	$value = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	//	$name_cookie = crc32($value);
		if (!$_COOKIE[$show_id] ) {
			setcookie($show_id, $show_id, time()+86400);
			delete_option('id');
		} 
	}
}
*/


/*
add_action('admin_menu', 'add_ppw_admin');

function add_ppw_admin () {
	add_options_page('Popularity Posts Widget', 'Popularity Posts Widget', 8, 'ppw_admin', 'ppw_admin');
}

function ppw_admin() {
	require (dirname(__FILE__) . '/admin.php');
}	
*/
register_activation_hook(__FILE__,'PopularityPostsWidget_install');

add_action('ppw_cache_event', 'ppw_cache_maintenance');
	if (!wp_next_scheduled('ppw_cache_event')) {
		$tomorrow = time() + 86400;
		$midnight  = mktime(0, 0, 0, 
			date("m", $tomorrow), 
			date("d", $tomorrow), 
			date("Y", $tomorrow));
		wp_schedule_event( $midnight, 'daily', 'ppw_cache_event' );
	}



//Start program, checking is current page is single post and return ID of current post
function ppw_start_PostViews  () {
	if(function_exists('ppw_bac_PostViews') && is_single()) {
    return ppw_bac_PostViews(get_the_ID()); 
	}
}

//Search tables and if not exists create them
function PopularityPostsWidget_install () {
   global $wpdb;
   $table_name = $wpdb->prefix . "PopularityPostsWidget";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name . " (
	  id mediumint(30) NOT NULL,
	  hits mediumint(55) NOT NULL,
	  UNIQUE KEY id (id)
	);";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
   }  
	  
   $table_name_cache = $wpdb->prefix . "PopularityPostsWidgetCache";
   if($wpdb->get_var("show tables like '$table_name_cache'") != $table_name_cache) {
      
      $sql_cache = "CREATE TABLE " . $table_name_cache . " (
	  id mediumint(30) NOT NULL,
	  date date NOT NULL,
	  hits mediumint(55) NOT NULL
	);";

      dbDelta($sql_cache);  	
	    
   }
}
//if (!isset($_COOKIE['MyWpBlog'])) setcookie('MyWpBlog', 'cookie', time()+60);
	
//Create a row in db witch ID and number of views of the post
function ppw_bac_PostViews ($post_ID) {
/*	$show_unique = get_option('show_unique');
	$show_id = get_option('id');
	
	if ($show_unique === 'true' && !$_COOKIE[$post_ID]  ) update_option('id', $post_ID);
	settype($show_id, "integer");
	
	if ($show_id === 0 || !isset($show_id)) {
		$idius = true;
	}
	*/
//	if ( (!$_COOKIE[$post_ID] && $show_unique === 'true' && $idius) || $show_unique === 'false') {
	
	global $wpdb;
	$table_name = $wpdb->prefix . "PopularityPostsWidget";
	$rows = $wpdb->get_var("SELECT hits FROM " . $table_name . " WHERE id=".$post_ID);
	if ($rows==NULL) {
		$query = $wpdb->query("INSERT INTO " . $table_name . " VALUES (".$post_ID.", 1)");
	} else {
		$query = $wpdb->query("UPDATE " . $table_name . " SET hits=hits+1 WHERE id=".$post_ID);
	}
	
	$table_name_cache = $wpdb->prefix . "PopularityPostsWidgetCache";
	
	$rows_cache = $wpdb->get_var("SELECT * FROM " . $table_name_cache . " WHERE id=".$post_ID." AND date=CURDATE()");
	
		if (!$rows_cache) {
			$query_cache = $wpdb->query("INSERT INTO " . $table_name_cache . " VALUES (".$post_ID.", CURDATE(), 1)");
		} else {
			$query_cache = $wpdb->query("UPDATE " . $table_name_cache . " SET hits=hits+1 WHERE id=".$post_ID." AND date= CURDATE()");
		}
//	}
}

function popularity_posts_widget_init() {
 load_plugin_textdomain( 'popularity_posts_widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function ppw_cache_maintenance() {
			global $wpdb;
			$wpdb->query("DELETE FROM ".$wpdb->prefix."popularitypostswidgetcache WHERE date < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
		}
		
//Returns the post publish date
function ppw_get_PostDate ($post_ID, $show_date, $date_format) {
	if ($show_date) {
		global $wpdb;
		$table_name = $wpdb->prefix . "posts";
		$date = $wpdb->get_var("SELECT post_date FROM " . $table_name . " WHERE id=".$post_ID);
		if ($date_format === 'format_foure') {
			return mysql2date('d/m/Y', $date);
		} elseif ($date_format === 'format_two') {
			return mysql2date('Y/m/d', $date);
		} elseif ($date_format === 'format_three') {
			return mysql2date('m/d/Y', $date);
		} else {
			return mysql2date('M d, Y', $date);
		}
	}
}

//Returns the number of comments in post
function ppw_get_ComCount ($post_ID){
	global $wpdb;
	$table_name = $wpdb->prefix . "comments";
	$com_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $table_name . " WHERE comment_post_ID=".$post_ID);
	return $com_count;
}

//Shorten the large titles
function ppw_get_TrimTitle ($title, $posts_title_length) {
	if (mb_strlen($title) > $posts_title_length) {
		return mb_substr($title, 0, $posts_title_length).'...';
	}
	else {
		return $title;
	}
}

include('kama_thumbnail.php');

function ppw_get_Thumbs ($post_ID, $thumbs_settings) {
	return kama_thumb_img("w=".$thumbs_settings['width']." &h=".$thumbs_settings['height']." &class=wpp-thumbnail &post_id=".$post_ID); 
}

?>