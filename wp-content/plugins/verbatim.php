<?php
/*
Plugin Name: Verbatim Widget
Plugin URI: http://nassauweekly.com/
Description: Verbatim Widget grabs a random verbatim and displays it on your sidebar.
Author: Jeremy Cohen
Version: 1
*/
 
 
class VerbatimWidget extends WP_Widget
{
  function VerbatimWidget()
  {
    $widget_ops = array('classname' => 'VerbatimWidget', 'description' => 'Displays a random quote', 'title' => 'Verbatim' );
    $this->WP_Widget('VerbatimWidget', 'Random Verbatim', $widget_ops);
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
    
    ?>
    <script type="text/javascript">
    jQuery.ready(document, function(){
	jQuery.ajax({
		type: "post",url: "admin-ajax.php",data: { action: 'gethello3', count: count, _ajax_nonce: '<?php echo $nonce; ?>' },
		beforeSend: function() {jQuery("#helloworld").fadeOut('fast');}, //fadeIn loading just when link is clicked
		success: function(html){ //so, if data is retrieved, store it in html
			jQuery("#helloworld").html(html); //fadeIn the html inside helloworld div
			jQuery("#helloworld").fadeIn("fast"); //animation
		}
	}); //close jQuery.ajax
	});
	</script>
	
	<?php

    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . '<a href="#" class="reloadButton">' . 'Verbatim' . '</a>' . '<a class="reloadButton"><img class="reloadImage" height="15px" src="http://www.nassauweekly.com/wp-content/themes/sight/images/refresh.png"/></a>' . $after_title;
    ?>
	<div id="verbatim-buffer">
		<div class="verbatim">
			<p class="context"></p>
			<p class="quotation"></p>
			<p class="date"></p>
		</div>
	</div>
	<script type="text/javascript">
		(function($) {
			var reloadVerbatim = function(){
				$("#verbatim-buffer").css('height', $(".verbatim").height());
 				$('.verbatim').fadeOut();
				jQuery.post('/wp-admin/admin-ajax.php', {'action' : 'getverbatim'}, function(response) {
					var verbatim = JSON.parse(response);
					$('.context').html(verbatim['context']);
					$('.quotation').html(verbatim['quotation']);
					$('.date').html(verbatim['date']);
					$('.verbatim').fadeIn();
					$("#verbatim-buffer").css('height', $(".verbatim").height());
				});
			}
			reloadVerbatim();
			$('.reloadButton').click(function(event){
 				event.preventDefault();
				reloadVerbatim();
			 });
		})(jQuery)
	</script>
	<?php      
	wp_reset_query();      
  
    echo $after_widget;
  }
 
}
add_action( 'widgets_init', create_function('', 'return register_widget("VerbatimWidget");') );