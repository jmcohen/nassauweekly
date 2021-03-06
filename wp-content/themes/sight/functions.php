<?php

/*** Theme setup ***/

add_theme_support( 'post-thumbnails' );
add_theme_support( 'automatic-feed-links' );

function sight_setup() {
	update_option('thumbnail_size_w', 290);
    update_option('thumbnail_size_h', 290);
    add_image_size( 'mini-thumbnail', 50, 50, true );
    add_image_size( 'slide', 640, 290, true );
    register_nav_menu('Navigation', __('Navigation'));
    register_nav_menu('Top menu', __('Top menu'));
}
add_action( 'init', 'sight_setup' );

if ( is_admin() && isset($_GET['activated'] ) && $pagenow == 'themes.php' ) {
    update_option( 'posts_per_page', 12 );
    update_option( 'paging_mode', 'default' );
}

/*** Navigation ***/

if ( !is_nav_menu('Navigation') || !is_nav_menu('Top menu') ) {
    $menu_id1 = wp_create_nav_menu('Navigation');
    $menu_id2 = wp_create_nav_menu('Top menu');
    wp_update_nav_menu_item($menu_id1, 1);
    wp_update_nav_menu_item($menu_id2, 1);
}

class extended_walker extends Walker_Nav_Menu{
	function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {

		if ( !$element )
			return;

		$id_field = $this->db_fields['id'];

		//display this element
		if ( is_array( $args[0] ) )
			$args[0]['has_children'] = ! empty( $children_elements[$element->$id_field] );

		//Adds the 'parent' class to the current item if it has children
		if( ! empty( $children_elements[$element->$id_field] ) )
			array_push($element->classes,'parent');

		$cb_args = array_merge( array(&$output, $element, $depth), $args);

		call_user_func_array(array(&$this, 'start_el'), $cb_args);

		$id = $element->$id_field;

		// descend only when the depth is right and there are childrens for this element
		if ( ($max_depth == 0 || $max_depth > $depth+1 ) && isset( $children_elements[$id]) ) {

			foreach( $children_elements[ $id ] as $child ){

				if ( !isset($newlevel) ) {
					$newlevel = true;
					//start the child delimiter
					$cb_args = array_merge( array(&$output, $depth), $args);
					call_user_func_array(array(&$this, 'start_lvl'), $cb_args);
				}
				$this->display_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
			}
			unset( $children_elements[ $id ] );
		}

		if ( isset($newlevel) && $newlevel ){
			//end the child delimiter
			$cb_args = array_merge( array(&$output, $depth), $args);
			call_user_func_array(array(&$this, 'end_lvl'), $cb_args);
		}

		//end this element
		$cb_args = array_merge( array(&$output, $element, $depth), $args);
		call_user_func_array(array(&$this, 'end_el'), $cb_args);
	}
}

/*** Slideshow ***/

$prefix = 'sgt_';

$meta_box = array(
    'id' => 'slide',
    'title' => 'Slideshow Options',
    'page' => 'post',
    'context' => 'side',
    'priority' => 'low',
    'fields' => array(
        array(
            'name' => 'Show in slideshow',
            'id' => $prefix . 'slide',
            'type' => 'checkbox'
        )
    )
);
add_action('admin_menu', 'sight_add_box');

// Add meta box
function sight_add_box() {
    global $meta_box;

    add_meta_box($meta_box['id'], $meta_box['title'], 'sight_show_box', $meta_box['page'], $meta_box['context'], $meta_box['priority']);
}

// Callback function to show fields in meta box
function sight_show_box() {
    global $meta_box, $post;

    // Use nonce for verification
    echo '<input type="hidden" name="sight_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';

    echo '<table class="form-table">';

    foreach ($meta_box['fields'] as $field) {
        // get current post meta data
        $meta = get_post_meta($post->ID, $field['id'], true);

        echo '<tr>',
                '<th style="width:50%"><label for="', $field['id'], '">', $field['name'], '</label></th>',
                '<td>';
                echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
        echo     '<td>',
            '</tr>';
    }

    echo '</table>';
}

add_action('save_post', 'sight_save_data');

// Save data from meta box
function sight_save_data($post_id) {
    global $meta_box;

    // verify nonce
    if (!wp_verify_nonce($_POST['sight_meta_box_nonce'], basename(__FILE__))) {
        return $post_id;
    }

    // check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    // check permissions
    if ('page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return $post_id;
        }
    } elseif (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    foreach ($meta_box['fields'] as $field) {
        $old = get_post_meta($post_id, $field['id'], true);
        $new = $_POST[$field['id']];

        if ($new && $new != $old) {
            update_post_meta($post_id, $field['id'], $new);
        } elseif ('' == $new && $old) {
            delete_post_meta($post_id, $field['id'], $old);
        }
    }
}

/*** Options ***/

function options_admin_menu() {
	// here's where we add our theme options page link to the dashboard sidebar
	add_theme_page("Sight Theme Options", "Theme Options", 'edit_themes', basename(__FILE__), 'options_page');
}
add_action('admin_menu', 'options_admin_menu');

function options_page() {
    if ( $_POST['update_options'] == 'true' ) { options_update(); }  //check options update
	?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"><br /></div>
		<h2>Sight Theme Options</h2>

        <form method="post" action="">
			<input type="hidden" name="update_options" value="true" />

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="logo_url"><?php _e('Custom logo URL:'); ?></label></th>
                    <td><input type="text" name="logo_url" id="logo_url" size="50" value="<?php echo get_option('logo_url'); ?>"/><br/><span
                            class="description"> <a href="<?php bloginfo("url"); ?>/wp-admin/media-new.php" target="_blank">Upload your logo</a> (max 290px x 128px) using WordPress Media Library and insert its URL here </span><br/><br/><img src="<?php echo (get_option('logo_url')) ? get_option('logo_url') : get_bloginfo('template_url') . '/images/logo.png' ?>"
                     alt=""/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="bg_color"><?php _e('Custom background color:'); ?></label></th>
                    <td><input type="text" name="bg_color" id="bg_color" size="20" value="<?php echo get_option('bg_color'); ?>"/><span
                            class="description"> e.g., <strong>#27292a</strong> or <strong>black</strong></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="ss_disable"><?php _e('Disable slideshow:'); ?></label></th>
                    <td><input type="checkbox" name="ss_disable" id="ss_disable" <?php echo (get_option('ss_disable'))? 'checked="checked"' : ''; ?>/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="ss_timeout"><?php _e('Timeout for slideshow (ms):'); ?></label></th>
                    <td><input type="text" name="ss_timeout" id="ss_timeout" size="20" value="<?php echo get_option('ss_timeout'); ?>"/><span
                            class="description"> e.g., <strong>7000</strong></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label><?php _e('Pagination:'); ?></label></th>
                    <td>
                        <input type="radio" name="paging_mode" value="default" <?php echo (get_option('paging_mode') == 'default')? 'checked="checked"' : ''; ?>/><span class="description">Default + WP Page-Navi support</span><br/>
                        <input type="radio" name="paging_mode" value="ajax" <?php echo (get_option('paging_mode') == 'ajax')? 'checked="checked"' : ''; ?>/><span class="description">AJAX-fetching posts</span><br/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="ga"><?php _e('Google Analytics code:'); ?></label></th>
                    <td><textarea name="ga" id="ga" cols="48" rows="18"><?php echo get_option('ga'); ?></textarea></td>
                </tr>
            </table>

            <p><input type="submit" value="Save Changes" class="button button-primary" /></p>
        </form>
    </div>
<?php
}

// Update options

function options_update() {
	update_option('logo_url', $_POST['logo_url']);
	update_option('bg_color', $_POST['bg_color']);
	update_option('ss_disable', $_POST['ss_disable']);
	update_option('ss_timeout', $_POST['ss_timeout']);
	update_option('paging_mode', $_POST['paging_mode']);
	update_option('ga', stripslashes_deep($_POST['ga']));
}

/*** Widgets ***/

if (function_exists('register_sidebar')) {
    register_sidebar(array(
        'name'=>'Site description',
        'before_widget' => '<div class="site-description">',
        'after_widget' => '</div>'
    ));
    register_sidebar(array(
        'name'=>'Sidebar',
        'before_widget' => '<div id="%1$s" class="%2$s widget">',
        'after_widget' => '</div></div>',
        'before_title' => '<h3>',
        'after_title' => '</h3><div class="widget-body clear">'
    ));
}

class GetConnected extends WP_Widget {

    function GetConnected() {
        parent::WP_Widget(false, $name = 'Sight Social Links');
    }

    function widget($args, $instance) {
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
            <?php echo $before_widget; ?>
                <?php if ( $title )
                    echo $before_title . $title . $after_title;  else echo '<div class="widget-body clear">'; ?>

                    <!-- Twitter -->
                    <?php if ( get_option('twitter_url') ) : ?>
                    <div class="getconnected_twitter">
                    <a href="<?php echo get_option('twitter_url'); ?>">Twitter</a>
                    <span><?php if ( function_exists('twittercount') ) twittercount( get_option('twitter_url') ); ?> followers</span>
                    </div>
                    <?php endif; ?>
                    <!-- /Twitter -->

                    <!-- Facebook -->
                    <?php if ( get_option('fb_url') ) : ?>
                    <div class="getconnected_fb">
                    <a href="<?php echo get_option('fb_url'); ?>">Facebook</a>
                    <span><?php echo get_option('fb_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Facebook -->

                    <!-- Flickr -->
                    <?php if ( get_option('flickr_url') ) : ?>
                    <div class="getconnected_flickr">
                    <a href="<?php echo get_option('flickr_url'); ?>">Flickr group</a>
                    <span><?php echo get_option('flickr_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Flickr -->

                    <!-- Behance -->
                    <?php if ( get_option('behance_url') ) : ?>
                    <div class="getconnected_behance">
                    <a href="<?php echo get_option('behance_url'); ?>">Behance</a>
                    <span><?php echo get_option('behance_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Behance -->

                    <!-- Delicious -->
                    <?php if ( get_option('delicious_url') ) : ?>
                    <div class="getconnected_delicious">
                    <a href="<?php echo get_option('delicious_url'); ?>">Delicious</a>
                    <span><?php echo get_option('delicious_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Delicious -->

                    <!-- Stumbleupon -->
                    <?php if ( get_option('stumbleupon_url') ) : ?>
                    <div class="getconnected_stumbleupon">
                    <a href="<?php echo get_option('stumbleupon_url'); ?>">Stumbleupon</a>
                    <span><?php echo get_option('stumbleupon_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Stumbleupon -->

                    <!-- Tumblr -->
                    <?php if ( get_option('tumblr_url') ) : ?>
                    <div class="getconnected_tumblr">
                    <a href="<?php echo get_option('tumblr_url'); ?>">Tumblr</a>
                    <span><?php echo get_option('tumblr_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Tumblr -->

                    <!-- Vimeo -->
                    <?php if ( get_option('vimeo_url') ) : ?>
                    <div class="getconnected_vimeo">
                    <a href="<?php echo get_option('vimeo_url'); ?>">Vimeo</a>
                    <span><?php echo get_option('vimeo_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Vimeo -->

                    <!-- Youtube -->
                    <?php if ( get_option('youtube_url') ) : ?>
                    <div class="getconnected_youtube">
                    <a href="<?php echo get_option('youtube_url'); ?>">Youtube</a>
                    <span><?php echo get_option('youtube_text'); ?></span>
                    </div>
                    <?php endif; ?>
                    <!-- /Youtube -->

            <?php echo $after_widget; ?>
        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        
        update_option('feedburner_url', $_POST['feedburner_url']);
        update_option('twitter_url', $_POST['twitter_url']);
        update_option('fb_url', $_POST['fb_url']);
        update_option('flickr_url', $_POST['flickr_url']);
        update_option('behance_url', $_POST['behance_url']);
        update_option('delicious_url', $_POST['delicious_url']);
        update_option('stumbleupon_url', $_POST['stumbleupon_url']);
        update_option('tumblr_url', $_POST['tumblr_url']);
        update_option('vimeo_url', $_POST['vimeo_url']);
        update_option('youtube_url', $_POST['youtube_url']);
        
        update_option('fb_text', $_POST['fb_text']);
        update_option('flickr_text', $_POST['flickr_text']);
        update_option('behance_text', $_POST['behance_text']);
        update_option('delicious_text', $_POST['delicious_text']);
        update_option('stumbleupon_text', $_POST['stumbleupon_text']);
        update_option('tumblr_text', $_POST['tumblr_text']);
        update_option('vimeo_text', $_POST['vimeo_text']);
        update_option('youtube_text', $_POST['youtube_text']);
        
        return $instance;
    }

    function form($instance) {

        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

            <script type="text/javascript">
                (function($) {
                    $(function() {
                        $('.social_options').hide();
                        $('.social_title').toggle(
                            function(){ $(this).next().slideDown(100) },
                            function(){ $(this).next().slideUp(100) }
                        );
                    })
                })(jQuery)
            </script>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">FeedBurner</a>
                <p class="social_options">
                    <label for="feedburner_url"><?php _e('FeedBurner feed url:'); ?></label>
                    <input type="text" name="feedburner_url" id="feedburner_url" class="widefat"
                           value="<?php echo get_option('feedburner_url'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Twitter</a>
                <p class="social_options">
                    <label for="twitter_url">Profile url:</label>
                    <input type="text" name="twitter_url" id="twitter_url" class="widefat" value="<?php echo get_option('twitter_url'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Facebook</a>
                <p class="social_options">
                    <label for="fb_url">Profile url:</label>
                    <input type="text" name="fb_url" id="fb_url" class="widefat" value="<?php echo get_option('fb_url'); ?>"/>
                    <label for="fb_text">Description:</label>
                    <input type="text" name="fb_text" id="fb_text" class="widefat" value="<?php echo get_option('fb_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Flickr</a>
                <p class="social_options">
                    <label for="flickr_url">Profile url:</label>
                    <input type="text" name="flickr_url" id="flickr_url" class="widefat" value="<?php echo get_option('flickr_url'); ?>"/>
                    <label for="flickr_text">Description:</label>
                    <input type="text" name="flickr_text" id="flickr_text" class="widefat" value="<?php echo get_option('flickr_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Behance</a>
                <p class="social_options">
                    <label for="behance_url">Profile url:</label>
                    <input type="text" name="behance_url" id="behance_url" class="widefat" value="<?php echo get_option('behance_url'); ?>"/>
                    <label for="behance_text">Description:</label>
                    <input type="text" name="behance_text" id="behance_text" class="widefat" value="<?php echo get_option('behance_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Delicious</a>
                <p class="social_options">
                    <label for="delicious_url">Profile url:</label>
                    <input type="text" name="delicious_url" id="delicious_url" class="widefat" value="<?php echo get_option('delicious_url'); ?>"/>
                    <label for="delicious_text">Description:</label>
                    <input type="text" name="delicious_text" id="delicious_text" class="widefat" value="<?php echo get_option('delicious_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Stumbleupon</a>
                <p class="social_options">
                    <label for="stumbleupon_url">Profile url:</label>
                    <input type="text" name="stumbleupon_url" id="stumbleupon_url" class="widefat" value="<?php echo get_option('stumbleupon_url'); ?>"/>
                    <label for="stumbleupon_text">Description:</label>
                    <input type="text" name="stumbleupon_text" id="stumbleupon_text" class="widefat" value="<?php echo get_option('stumbleupon_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Tumblr</a>
                <p class="social_options">
                    <label for="tumblr_url">Profile url:</label>
                    <input type="text" name="tumblr_url" id="tumblr_url" class="widefat" value="<?php echo get_option('tumblr_url'); ?>"/>
                    <label for="tumblr_text">Description:</label>
                    <input type="text" name="tumblr_text" id="tumblr_text" class="widefat" value="<?php echo get_option('tumblr_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Vimeo</a>
                <p class="social_options">
                    <label for="vimeo_url">Profile url:</label>
                    <input type="text" name="vimeo_url" id="vimeo_url" class="widefat" value="<?php echo get_option('vimeo_url'); ?>"/>
                    <label for="vimeo_text">Description:</label>
                    <input type="text" name="vimeo_text" id="vimeo_text" class="widefat" value="<?php echo get_option('vimeo_text'); ?>"/>
                </p>
            </div>

            <div style="margin-bottom: 5px;">
                <a href="javascript: void(0);" class="social_title" style="font-size: 13px; display: block; margin-bottom: 5px;">Youtube</a>
                <p class="social_options">
                    <label for="youtube_url">Profile url:</label>
                    <input type="text" name="youtube_url" id="youtube_url" class="widefat" value="<?php echo get_option('youtube_url'); ?>"/>
                    <label for="youtube_text">Description:</label>
                    <input type="text" name="youtube_text" id="youtube_text" class="widefat" value="<?php echo get_option('youtube_text'); ?>"/>
                </p>
            </div>
        <?php
    }

}
add_action('widgets_init', create_function('', 'return register_widget("GetConnected");'));

class Recentposts_thumbnail extends WP_Widget {

    function Recentposts_thumbnail() {
        parent::WP_Widget(false, $name = 'Sight Recent Posts');
    }

    function widget($args, $instance) {
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
            <?php echo $before_widget; ?>
            <?php if ( $title ) echo $before_title . $title . $after_title;  else echo '<div class="widget-body clear">'; ?>

            <?php
                global $post;
                if (get_option('rpthumb_qty')) $rpthumb_qty = get_option('rpthumb_qty'); else $rpthumb_qty = 5;
                $q_args = array(
                    'numberposts' => $rpthumb_qty,
                );
                $rpthumb_posts = get_posts($q_args);
                foreach ( $rpthumb_posts as $post ) :
                    setup_postdata($post);
            ?>

                <a href="<?php the_permalink(); ?>" class="rpthumb clear">
                    <?php if ( has_post_thumbnail() && !get_option('rpthumb_thumb') ) {
                        the_post_thumbnail('mini-thumbnail');
                        $offset = 'style="padding-left: 65px;"';
                    }
                    ?>
                    <span class="rpthumb-title" <?php echo $offset; ?>><?php the_title(); ?></span>
                    <span class="rpthumb-date" <?php echo $offset; unset($offset); ?>><?php the_time(__('M j, Y')) ?></span>
                </a>

            <?php endforeach; ?>

            <?php echo $after_widget; ?>
        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        update_option('rpthumb_qty', $_POST['rpthumb_qty']);
        update_option('rpthumb_thumb', $_POST['rpthumb_thumb']);
        return $instance;
    }

    function form($instance) {
        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="rpthumb_qty">Number of posts:  </label><input type="text" name="rpthumb_qty" id="rpthumb_qty" size="2" value="<?php echo get_option('rpthumb_qty'); ?>"/></p>
            <p><label for="rpthumb_thumb">Hide thumbnails:  </label><input type="checkbox" name="rpthumb_thumb" id="rpthumb_thumb" <?php echo (get_option('rpthumb_thumb'))? 'checked="checked"' : ''; ?>/></p>
        <?php
    }

}
add_action('widgets_init', create_function('', 'return register_widget("Recentposts_thumbnail");'));

/*** Comments ***/

function commentslist($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment; ?>
	<li>
        <div id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
            <table>
                <tr>
                    <td>
                        <?php echo get_avatar($comment, 70, get_bloginfo('template_url').'/images/no-avatar.png'); ?>
                    </td>
                    <td>
                        <div class="comment-meta">
                            <?php printf(__('<p class="comment-author"><span>%s</span> says:</p>'), get_comment_author_link()) ?>
                            <?php printf(__('<p class="comment-date">%s</p>'), get_comment_date('M j, Y')) ?>
                            <?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
                        </div>
                    </td>
                    <td>
                        <div class="comment-text">
                            <?php if ($comment->comment_approved == '0') : ?>
                                <p><?php _e('Your comment is awaiting moderation.') ?></p>
                                <br/>
                            <?php endif; ?>
                            <?php comment_text() ?>
                        </div>
                    </td>
                </tr>
            </table>
         </div>
<?php
}

/*** Misc ***/

function feedcount($feedurl='http://feeds.feedburner.com/wpshower') {
    $feedid = explode('/', $feedurl);
    $feedid = end($feedid);
    $twodayago = date('Y-m-d', strtotime('-2 days', time()));
    $onedayago = date('Y-m-d', strtotime('-1 days', time()));
    $today = date('Y-m-d');

    $api = "https://feedburner.google.com/api/awareness/1.0/GetFeedData?uri=$feedid&dates=$twodayago,$onedayago";

    //Initialize a cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $api);
    $data = curl_exec($ch);
    $base_code = curl_getinfo($ch);
    curl_close($ch);

    if ($base_code['http_code']=='401'){
        $burner_count_circulation = 'This feed does not permit Awareness API access';
        $burner_date = $today;
    } else {

        $xml = new SimpleXMLElement($data); //Parse XML via SimpleXML Class
        $bis = $xml->attributes();  //Bis Contain first attribute, It usually is ok or fail in FeedBurner

        if ($bis=='ok'){
            foreach ($xml->feed as $feed) {
                if ($feed->entry[1]['circulation']=='0'){
                    $burner_count_circulation = $feed->entry[0]['circulation'];
                    $burner_date  =  $feed->entry[0]['date'];
                } else {
                    $burner_count_circulation = $feed->entry[1]['circulation'];
                    $burner_date  =  $feed->entry[1]['date'];
                }
            }
        }

        if ($bis=='fail'){
            switch ($xml->err['code']) {
                case 1:
                    $burner_count_circulation = 'Feed Not Found';
                    break;
                case 5:
                    $burner_count_circulation = 'Missing required parameter (URI)';
                    break;
                case 6:
                    $burner_count_circulation = 'Malformed parameter (DATES)';
                    break;
            }
            $burner_date = $today;
        }

    }
    if ( $bis != 'fail' && $burner_count_circulation != '' ) {
        echo '<span>'.$burner_count_circulation.' readers</span>';
    } else {
        echo '<span>'.$burner_count_circulation.'</span>';
    }
}

function twittercount($twitter_url='http://twitter.com/wpshower') {
    $twitterid = explode('/', $twitter_url);
    $twitterid = end($twitterid);
    $xml = @simplexml_load_file("http://twitter.com/users/show.xml?screen_name=$twitterid");
	echo $xml[0]->followers_count;
}

function get_issue_title($issue_slug) {
    $issue_posts_ = get_posts(array('name' => $issue_slug, 'post_type' => 'issue', 'post_status' => 'publish', 'posts_per_page' => 1));
    $issue_post = $issue_posts_[0];
    return $issue_post->post_title;
}

function seo_title() {
    global $page, $paged;
    $sep = " | "; # delimiter
    $newtitle = get_bloginfo('name'); # default title

    # Single & Page ##################################
    if (is_single() || is_page())
        $newtitle = single_post_title("", false);

    # Category ######################################
    if (is_category())
        $newtitle = single_cat_title("", false);

    # Tag ###########################################
    if (is_tag())
     $newtitle = single_tag_title("", false);

    # Search result ################################
    if (is_search())
     $newtitle = "Search Result " . $s;

    # Taxonomy #######################################
    if (is_tax()) {
        $curr_tax = get_taxonomy(get_query_var('taxonomy'));
        $curr_term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy')); # current term data
        if (get_query_var('taxonomy') == 'byline') {
            $newtitle = $curr_term->name;
        } else if (get_query_var('taxonomy') == 'issue'){         
            $issue_slug = get_query_var('issue');
            $newtitle = get_issue_title($issue_slug);
        }
        # if it's term
        else if (!empty($curr_term)) {
            $newtitle = $curr_tax->label . $sep . $curr_term->name;
        } else {
            $newtitle = $curr_tax->label;
        }
    }

    # Page number
    if ($paged >= 2 || $page >= 2)
            $newtitle .= $sep . sprintf('Page %s', max($paged, $page));

    # Home & Front Page ########################################
    if (is_home() || is_front_page()) {
        $newtitle = get_bloginfo('name');
    } else {
        $newtitle .=  $sep . get_bloginfo('name');
    }

	return $newtitle;
}
add_filter('wp_title', 'seo_title');

function new_excerpt_length($length) {
	return 200;
}
add_filter('excerpt_length', 'new_excerpt_length');


function getTinyUrl($url) {
    $tinyurl = file_get_contents("http://tinyurl.com/api-create.php?url=".$url);
    return $tinyurl;
}

function smart_excerpt($string, $limit) {
    $words = explode(" ",$string);
    if ( count($words) >= $limit) $dots = '...';
    echo implode(" ",array_splice($words,0,$limit)).$dots;
}

function comments_link_attributes(){
    return 'class="comments_popup_link"';
}
add_filter('comments_popup_link_attributes', 'comments_link_attributes');

function next_posts_attributes(){
    return 'class="nextpostslink"';
}
add_filter('next_posts_link_attributes', 'next_posts_attributes');


/* Verbatim */
 
add_action( 'init', 'create_post_type' );
function create_post_type() {
	register_post_type( 'nw_verbatim',
		array(
			'labels' => array(
				'name' => __( 'Verbatim' ),
				'singular_name' => __( 'Verbatims' )
			),
		'public' => true,
		'has_archive' => true,
        'rewrite' => array('slug' => 'verbatim'),
		'supports' => array(''),
        'taxonomies' => array('issue'),
		'exclude_from_search' => true,
		)
	);
}

add_action("admin_init", "admin_init");   
function admin_init(){ 
	add_meta_box("verbatim-meta", "Verbatim", "verbatim", "nw_verbatim", "normal", "low");
}   

function verbatim(){ 
	global $post; 
	$custom = get_post_custom($post->ID); 
	$context = get_the_title();  # "s.g. overheard at Ivy"
	$quotation = $custom['quotation'][0];
    $issue = get_the_terms($post->ID, 'issue');
	?> 
		<label><b>Context</b>: ("overheard at Ivy")</label><br />
		<input name="post_title" value="<?php echo $context; ?>" /> 
		<br /> <br />
		<label><b>Quotation</b>:</label><br />
		<textarea cols="50" rows="5" name="quotation"><?php echo $quotation; ?></textarea><br />
	<?php
}  

add_action('save_post', 'save_details');
function save_details(){ 
	global $post; 
 	update_post_meta($post->ID, "quotation", $_POST["quotation"]); 
}

add_action("manage_posts_custom_column", "verbatim_custom_columns");

add_filter("manage_edit-nw_verbatim_columns", "verbatim_edit_columns");
 
 
function verbatim_edit_columns($columns){ 
	$columns = array (	"cb" =>
 "<
input type='checkbox' />
", 
"title" => "Overheard at...",
"quotation" => "Quotation", 
"issue" => "Issue");
  	return $columns;

}


function verbatim_custom_columns($column){
	global $post;

    if ($column == 'issue'){
        $terms = get_the_terms($post->ID, 'issue');
        if ( $terms && ! is_wp_error( $terms ) ){
            foreach ( $terms as $term ) {
                $issue_name = $term->name;
                $issue_date = new DateTime(get_post($term->term_id)->post_date);
                echo $issue_name . ' (' . date_format($issue_date, 'M d, Y') . ')';
                // echo date_format(date_parse($issue_date->post_date), 'Y');
            }                
        }
      }
    else{
	   $custom = get_post_custom();
	   echo $custom[$column][0];
    }
}


function get_verbatim_ajax() {
	$args = array('post_type' => 'nw_verbatim', 'orderby' => 'rand', 'posts_per_page' => '1');
	$the_query = new WP_Query( $args );
	$the_query->the_post();
	$context = trim(get_the_title(), ':') . ':';
	$quotation = nl2br(post_custom('quotation'));
	$data = array('context' => $context,
				  'quotation' => $quotation,
				  'date' => get_the_date());
	echo json_encode($data);
	die();
}
add_action( 'wp_ajax_getverbatim', 'get_verbatim_ajax' );
add_action( 'wp_ajax_nopriv_getverbatim', 'get_verbatim_ajax' );

// /* Issue taxonomy */
// 
// function issue_init() {
// 	register_taxonomy(
// 		'issue',
// 		'post',
// 		array(
// 			'label' => __( 'Issue' ),
// 		)
// 	);
// }
// add_action( 'init', 'issue_init' );

/* Exclude pages from search results */
function SearchFilter($query) {
	if ($query->is_search) {
		$query->set('post_type', 'post');
	}
	return $query;
}

add_filter('pre_get_posts','SearchFilter');

/* Modifying the CPT-onomies plugin so that issues are selected with a dropdown */
// add_filter( 'custom_post_type_onomies_meta_box_format', 'issue_custom_post_type_onomies_meta_box_format', 1, 3 );
function issue_custom_post_type_onomies_meta_box_format( $format, $taxonomy, $post_type ) {
   if ( $taxonomy == 'issue' )
      return 'dropdown';
   return $format;
}

add_action("template_redirect", 'custom_template_redirects');

function custom_template_redirects()
{
    if (get_query_var("issue")) {
        include(TEMPLATEPATH . '/issue.php');
        die();
    }

    if (is_post_type_archive('issue')){
        include(TEMPLATEPATH . '/issues.php');
        die();
    }

    if (is_post_type_archive('nw_verbatim')){
        include(TEMPLATEPATH . '/verbatims.php');
        die();
    }
}


add_filter( 'query_vars', 'issue_query_vars' );
function issue_query_vars( $query_vars )
{
    $query_vars[] = 'issue';
    return $query_vars;
}

function issue_excerpt($issue_id){
    $author_list = array();
    $args = array(  'post_type' => 'post', 
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'issue',
                            'field' => 'id',
                            'terms' => $issue_id
                            )));
    $query = new WP_Query( $args );
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $author = get_userdata(get_the_author_meta('ID'));
            array_push($author_list, '<a href="' . get_permalink() . '">' . '<em>' . get_the_title() . '</em>' . '</a>' . ' by ' . get_the_author());
         }
    }
    wp_reset_postdata();
    return join('  &middot;  ', $author_list);
}

/*
    display the "more by the same author" widget:
    lists a fixed number of randomly chosen articles written by author
    of the article currently being viewed.
 */
function echo_more_articles() {
    // choose one of the authors (most articles just have one)
    $bylines_ = wp_get_post_terms(get_the_ID(), 'byline');
    shuffle($bylines_);
    $byline_slug = $bylines_[0]->slug;
    $byline_name = $bylines_[0]->name;
        
    // get 3 random articles by the same author 
    $num_more_articles = 3;
    $posts = get_posts(
        array(
            'posts_per_page' => $num_more_articles,
            'orderby' => 'rand',
            'post__not_in' => array(get_the_ID()), // exclude the current post, naturally
            'byline' => $byline_slug,
        )
    );

    if (empty($posts)) {
        return;
    }

    $links = array();
    foreach ($posts as $post) {
        $issue = reset(get_the_terms($post->ID, 'issue'));
         array_push($links, '<li><p><a href="'.get_permalink($post->ID).
            '">'.$post->post_title.'</a> <span class="gray">in <a href="/issue/'.
            $issue->slug.'"> '.$issue->name.'</span></p></li>');
    }

    ?>
    <div class="more-widget">
        <h3>More by <?php echo $byline_name; ?> </h3>
        <div class="widget-body clear">
            <ul>
                <?php echo join('', $links); ?>
            </ul>
        </div>
    </div>
  <?php
}


?>
