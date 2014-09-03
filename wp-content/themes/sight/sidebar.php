<div class="sidebar">
    <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('Sidebar') ) :
        $widget_args = array(
            'after_widget' => '</div></div>',
            'before_title' => '<h3>',
            'after_title' => '</h3><div class="widget-body clear">'
        );
    ?>

    <h1>Sidebar!</h1>
    <?php the_widget( 'Recentposts_thumbnail', 'title=Recent posts', $widget_args); ?>

    <?php //the_widget( 'GetConnected', 'title=Get Connected', $widget_args); ?>

    <?php endif; ?>

    <?php 
    // if this is an article page, insert the "more by the same author" widget,
    // which is defined in functions.php
        if (is_single()) {
            echo_more_articles();
        }
    ?>

</div>

<!-- Automatically set the height of the sidebar to be equal to that of the content.
     This is crucial for the "more by the same author" widget, which is pinned to the
     BOTTOM of the page. -->
<script type="text/javascript">
    jQuery(document).ready(function($) {
        var contentHeight = $('#content').height() - $('#content .comments').height(); 
        $('.sidebar').height(contentHeight);
    });
</script>
