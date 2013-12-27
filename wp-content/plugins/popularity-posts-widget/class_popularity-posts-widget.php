<?php

class PopularityPostsWidget extends WP_Widget {
	public function PopularityPostsWidget() {
		$widget_ops = array( 'classname' => 'popularitypostswidget', 'description' => __('Displays popularitypostswidget block at sidebar.') );
		$control_ops = array( 'width' => 200, 'height' => 250, 'id_base' => 'popularitypostswidget' );
		parent::__construct( 'popularitypostswidget', 'PopularityPostsWidget', $widget_ops, $control_ops );
	}
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	public function form($instance) {
	
				$title = isset($instance['title']) ? esc_attr($instance['title']) : "Popularity Posts Widget";
				$number = empty($instance['number']) ? 5 : absint($instance['number']);
				$posts_title_length = empty($instance['posts_title_length']) ? 60 : absint($instance['posts_title_length']);
				$width = empty($instance['width']) ? 160 : absint($instance['width']);
				$height = empty($instance['height']) ? 80 : absint($instance['height']);
		?>
		
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'popularity_posts_widget'); ?></label><br>
		<input type="text" style="width:100%" id="<?php echo $this->get_field_id('title'); ?>" 
		name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>">
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id('range'); ?>"><?php _e('Condition:', 'popularity_posts_widget'); ?></label><br>	
		<select style="width:100%" id="<?php echo $this->get_field_id('range'); ?>"
		name="<?php echo $this->get_field_name('range'); ?>" value="<?php $instance['range']; ?>" >
			<option value="range_alltime" <?php if ($instance['range'] === 'range_alltime') echo "selected"; ?>><?php _e('Total views', 'popularity_posts_widget'); ?>
			<option value="range_today" <?php if ($instance['range'] === 'range_today') echo "selected"; ?>><?php _e('Views today', 'popularity_posts_widget'); ?>
			<option value="range_last7days" <?php if ($instance['range'] === 'range_last7days') echo "selected"; ?>><?php _e('Views last 7 days', 'popularity_posts_widget'); ?>
			<option value="range_last30days" <?php if ($instance['range'] === 'range_last30days') echo "selected"; ?>><?php _e('Views last 30 days', 'popularity_posts_widget'); ?>
		</select>	
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts:', 'popularity_posts_widget'); ?></label><br>	
		<input type="text" style="width:100px" id="<?php echo $this->get_field_id('number'); ?>"
		name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo $number; ?>">
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id('posts_title_length'); ?>"><?php _e('Post title length:', 'popularity_posts_widget'); ?></label><br>
		<input type="text" style="width:100px" id="<?php echo $this->get_field_id('posts_title_length'); ?>" 
		name="<?php echo $this->get_field_name('posts_title_length'); ?>" value="<?php echo $posts_title_length; ?>">
		</p>
		
		<input type="hidden" id="<?php echo $this->get_field_id('hidden'); ?>" 
		name="<?php echo $this->get_field_name('hidden'); ?>" value="<?php echo 'true'; ?>">
		
		<fieldset style="border:1px solid #cccccc; padding:5px;">
		<legend><?php _e('Display Settings', 'popularity_posts_widget'); ?></legend>
		<p style="margin-bottom:1px;">
		<input type="checkbox" name="<?php echo $this->get_field_name('views_checkbox'); ?>"
		id = "<?php echo $this->get_field_id('views_checkbox'); ?>" value = "yes" <?php if ( ($instance['views_checkbox']) || (!$instance['hidden']) ) echo "checked"; ?>>
		<label for="<?php echo $this->get_field_id('views_checkbox'); ?>"><?php _e('Show views?', 'popularity_posts_widget'); ?></label> 		
		</p>
		
		<p style="margin-bottom:1px;">
		<input type="checkbox" name="<?php echo $this->get_field_name('comment_checkbox'); ?>"
		id = "<?php echo $this->get_field_id('comment_checkbox'); ?>" value = "yes" <?php if ($instance['comment_checkbox'] || (!$instance['hidden']) ) echo "checked"; ?>>
		<label for="<?php echo $this->get_field_id('comment_checkbox'); ?>"><?php _e('Show comments?', 'popularity_posts_widget'); ?></label> 	
		</p>
		
		<p style="margin-bottom:3px;">
		<input type="checkbox" name="<?php echo $this->get_field_name('date_checkbox'); ?>"
		id = "<?php echo $this->get_field_id('date_checkbox'); ?>" value = "yes" <?php if ($instance['date_checkbox']) echo "checked"; ?>>
		<label for="<?php echo $this->get_field_id('date_checkbox'); ?>"><?php _e('Show date?', 'popularity_posts_widget'); ?></label> 		
		</p>
		
		<?php
		if ($instance['date_checkbox'] || !$instance['hidden']) {
		?>
		
		<fieldset style="border:1px solid #cccccc; padding:5px;">
		<legend><?php _e('Date Format', 'popularity_posts_widget'); ?></legend>
		<p style="margin-bottom:3px;">	
		<select style="width:120px" id="<?php echo $this->get_field_id('date_format'); ?>"
		name="<?php echo $this->get_field_name('date_format'); ?>" value="<?php $instance['date_format']; ?>" >
			<option value="format_one" <?php if ($instance['date_format'] === 'format_one') echo "selected"; ?>><?php echo date('M d, Y'); ?>
			<option value="format_two" <?php if ($instance['date_format'] === 'format_two') echo "selected"; ?>><?php echo date('Y/m/d'); ?>
			<option value="format_three" <?php if ($instance['date_format'] === 'format_three') echo "selected"; ?>><?php echo date('m/d/Y'); ?>
			<option value="format_foure" <?php if ($instance['date_format'] === 'format_foure') echo "selected"; ?>><?php echo date('d/m/Y'); ?>
		</select>	
		</p>
		</fieldset>
		
		<?php
		}
		?>
		</fieldset>
		
		<fieldset style="border:1px solid #cccccc; padding:5px; margin-top: 10px;">
		<legend><?php _e('Filter Settings', 'popularity_posts_widget'); ?></legend>
		<p style="margin-bottom:3px;">
		<input type="checkbox" name="<?php echo $this->get_field_name('show_cat'); ?>"
		id = "<?php echo $this->get_field_id('show_cat'); ?>" value = "yes" <?php if ($instance['show_cat']) echo "checked"; ?>>
		<label for="<?php echo $this->get_field_id('show_cat'); ?>"><?php _e('Turn ON categories filter', 'popularity_posts_widget'); ?></label> 
		</p>
		
		<?php
		if ($instance['show_cat']) {
		
		global $wpdb;
		$table_name = $wpdb->prefix . "terms";
		
		$rows = $wpdb->get_results("SELECT wp_terms.name, wp_term_taxonomy.term_taxonomy_id 
									FROM wp_terms, wp_term_taxonomy
									WHERE wp_terms.term_id=wp_term_taxonomy.term_id
									AND wp_term_taxonomy.taxonomy='category' ");
									
			echo '<fieldset style="border:1px solid #cccccc; padding:5px; margin-top: 5px;">';
			echo '<legend>';
			_e('Select needed categories', 'popularity_posts_widget');
			echo '</legend>';
			foreach ($rows as $row) {
			?>	
			
			<p style="margin-bottom:1px;" >
			<input type="checkbox" name="<?php echo $this->get_field_name($row->term_taxonomy_id); ?>"
			id = "<?php echo $this->get_field_id($row->term_taxonomy_id); ?>" value = <?php echo $row->term_taxonomy_id; ?> <?php if($instance[$row->term_taxonomy_id]) echo "checked"; ?>>
			<label for="<?php echo $this->get_field_id($row->term_taxonomy_id); ?>"><?php echo $row->name; ?></label> 		
			</p>
			
			<?php
			}
			echo "</fieldset>";		
			
				$rows_id = $wpdb->get_results("SELECT object_id, term_taxonomy_id
										   FROM wp_term_relationships
										   ");
										   
						foreach ($rows_id as $row_id) {
							if (isset($instance[$row_id->term_taxonomy_id])) {
							$res = $res." id=".$row_id->object_id." OR";
							}	
						}
						
			$res = substr($res, 0, strlen($res)-2);	
			
			update_option('categories_filter',$res);	
											   
		}
		?>
		</fieldset>
		
		<fieldset style="border:1px solid #cccccc; padding:5px; margin-top: 10px;">
		<legend><?php _e('Thumbnail settings', 'popularity_posts_widget'); ?></legend>
		<p style="margin-bottom:3px;">
		<input type="checkbox" name="<?php echo $this->get_field_name('show_thumbs'); ?>"
		id = "<?php echo $this->get_field_id('show_thumbs'); ?>" value = "yes" <?php if ($instance['show_thumbs']) echo "checked"; ?>>
		<label for="<?php echo $this->get_field_id('show_thumbs'); ?>"><?php _e('Display posts thumbnails', 'popularity_posts_widget'); ?></label> 
		</p>
		
		<?php
		if ($instance['show_thumbs']) {
		
			?>	
			
			<p style="margin-bottom:1px; font-size:10px;" >
			Recomends to set Width option to full width of youre sidebar.
			</p>
			
			<p style="margin-bottom:1px;" >
			<label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width:', 'popularity_posts_widget'); ?></label>
			<input type="text" style="width:60px" id="<?php echo $this->get_field_id('width'); ?>" 
			name="<?php echo $this->get_field_name('width'); ?>" value="<?php echo $width; ?>">px
			</p>
			
			<p style="margin-bottom:1px;" >
			<label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height:', 'popularity_posts_widget'); ?></label>
			<input type="text" style="width:60px" id="<?php echo $this->get_field_id('height'); ?>" 
			name="<?php echo $this->get_field_name('height'); ?>" value="<?php echo $height; ?>">px
			</p>
		
			<?php
		}
		?>
		</fieldset>
		
		<!--
		<fieldset style="border:1px solid #cccccc; padding:5px; margin-top: 10px;">
		<legend><?php _e('Advanced Settings', 'popularity_posts_widget'); ?></legend>
		<p style="margin-bottom:3px;">
			<input type="checkbox" name="<?php echo $this->get_field_name('show_unique'); ?>"
			id = "<?php echo $this->get_field_id('show_unique'); ?>" value = "yes" <?php if ($instance['show_unique']) echo "checked"; ?>>
			<label for="<?php echo $this->get_field_id('show_unique'); ?>"><?php _e('Count only unique visitors views?', 'popularity_posts_widget'); ?></label> 		
		</p>
			
			<p style="margin-bottom:1px; font-size:10px;" >
			Cookies enable required.
			</p>
			
		</fieldset>
		-->
		
		<?php	
		
		/*
		if ($instance['show_unique']) {
			update_option('show_unique', 'true', '', 'no');
		} else {
			update_option('show_unique', 'false', '', 'no');
		}
		*/
	}
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	public function update ($new_instance , $old_instance) {
		$instance = $new_instance;
		$instance['res'] = $new_instance['res'];
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['title'] = empty($instance['title']) ? "" : $instance['title'];
		$instance['number'] = (int) $new_instance['number'];
		$instance['number'] = empty($instance['number']) ? 5 : $instance['number'];
		$instance['posts_title_length'] = (int) $new_instance['posts_title_length'];
		$instance['posts_title_length'] = empty($instance['posts_title_length']) ? 60 : $instance['posts_title_length'];
		$instance['date_checkbox'] = $new_instance['date_checkbox'] ? true : false;
		$instance['views_checkbox'] = $new_instance['views_checkbox'] ? true : false;
		$instance['comment_checkbox'] =  $new_instance['comment_checkbox'] ? true : false;
		$instance['width'] = (int) $new_instance['width'];
		$instance['width'] = empty($instance['width']) ? 160 : $instance['width'];
		$instance['height'] = (int) $new_instance['height'];
		$instance['height'] = empty($instance['height']) ? 80 : $instance['height'];
		return $instance;
	}
	
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	public function widget ($args , $instance) {
		extract($args);
	
		$thumbs_settings = array (
		'width'  => $instance['width'],
		'height' => $instance['height']
		);
	
		if ( ($instance['comment_checkbox'] || $instance['views_checkbox']) && $instance['date_checkbox']) { $date_pref = " | ";}
		else {$date_pref = ""; }
		
		if ($instance['views_checkbox'] && $instance['comment_checkbox']) { $com_pref = " | ";}
		else {$com_pref = ""; }
		
		//////Categories filter
		if ($instance['show_cat']) {
			if ($instance['range'] === "range_alltime") {
				$res = get_option('categories_filter');
				$cat_res = ' WHERE '.$res;
			} elseif ($instance['range'] === "range_last7days" || $instance['range'] === "range_last30days" ) {
				$cat_res = ' AND ('.$res.') '; 
			} else {
				$cat_res = ' '; 
			}
		}
		///////End filter
		
		global $wpdb;
		$table_name = $wpdb->prefix . "PopularityPostsWidget";
		$table_name_cache = $wpdb->prefix . "PopularityPostsWidgetCache";
		
		if ($instance['range'] === "range_alltime" ) {
			$rows = $wpdb->get_results("SELECT * FROM " . $table_name . " ".$cat_res." ORDER BY hits DESC LIMIT " . $instance['number'], ARRAY_A);
		} elseif ($instance['range'] === "range_today") {
			$rows = $wpdb->get_results("SELECT * FROM " . $table_name_cache . " WHERE date=CURDATE() ".$cat_res." ORDER BY hits DESC LIMIT " . $instance['number'], ARRAY_A);
		} elseif ($instance['range'] === "range_last7days" || $instance['range'] === "range_last30days") {
			if ($instance['range'] === "range_last7days") $num_days = 7;
			if ($instance['range'] === "range_last30days") $num_days = 30;
			$rows = $wpdb->get_results("SELECT id, SUM(hits) FROM " . $table_name_cache . " WHERE date > DATE_SUB(CURDATE(), INTERVAL ".$num_days." DAY) ".$cat_res." GROUP BY id ORDER BY SUM(hits) DESC LIMIT " . $instance['number'], ARRAY_A);
		} 
		
		echo $before_widget;
		if ($instance['title']) echo $before_title . $instance['title'] . $after_title;
		
		////////////// Loop ///////////////////
		foreach ($rows as $row) {
	
			$title_posts=get_the_title($row['id']);
			$permalink=get_permalink($row['id']);
			
			if ($instance['range'] === "range_alltime" || $instance['range'] === "range_today" ) {
				$hits=$row['hits'];
			}    else {
				$hits=$row['SUM(hits)'];
			}      
			  
			$hits_to_show = $instance['views_checkbox'] ? 'Views ('.$hits.') ' : "";
			$comments_to_show = $instance['comment_checkbox'] ? 'Comments ('.ppw_get_ComCount($row['id']).')': "";   
			
			//Style file
			require ('style/style-one.php');
	
		}
		////////////// End of loop //////////////
		
		echo $after_widget;	
	}	
}

?>