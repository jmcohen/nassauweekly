<?php
/*
Plugin Name: Current Issue Widget
Plugin URI: http://nassauweekly.com/
Description: Current Issue Widget displays an image link to the latest issue.
Author: Jeremy Cohen
Version: 1
*/
 
 
class CurrentIssueWidget extends WP_Widget
{
  function CurrentIssueWidget()
  {
    $widget_ops = array('classname' => 'CurrentIssueWidget', 'description' => 'Displays a link to the most recent issue.', 'title' => 'Current Issue' );
    $this->WP_Widget('CurrentIssueWidget', 'Current Issue', $widget_ops);
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
    

    echo $before_widget;

    echo $before_title . 'Current Issue' . $after_title;
 

	$args = array('post_type' => 'issue', 'posts_per_page' => '1');
    $currentIssueQuery = new WP_Query( $args );
    $currentIssueQuery->the_post();
    ?>
    <a href="<?php the_permalink(); ?>">

    <?php the_post_thumbnail('medium'); ?>

    </a>

    <?php
 
	wp_reset_query();      
  
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("CurrentIssueWidget");') );