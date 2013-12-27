<?php get_header();?>

<div id="issue-archive">

<div class="content-title">
    Issue Archive
        <a href="javascript: void(0);" id="mode"></a>

</div>

<?php 
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
query_posts(array(
        'paged' => $paged,
        'post_type' => 'issue',
        'posts_per_page' => '10',
        'offset' => '0',
    )
); ?>

<?php if ( have_posts() ) : ?>

    <div id="loop" class="<?php if ($_COOKIE['mode'] == 'grid') echo 'grid'; else echo 'list'; ?> clear">

    <?php while ( have_posts() ) : the_post(); ?>

        <div <?php post_class('post clear'); ?> id="post_<?php the_ID(); ?>">
            <?php if ( has_post_thumbnail() ) :?>
            <a href="<?php the_permalink() ?>" class="thumb"><?php the_post_thumbnail('medium', array(
                        'alt'   => trim(strip_tags( $post->post_title )),
                        'title' => trim(strip_tags( $post->post_title )),
                    )); ?></a>
            <?php endif; ?>

            <h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>

            <div class="post-meta"><span class="post-date"><?php the_time(__('M j, Y')); ?></span></div>
            <div class="post-content"><?php echo issue_excerpt(get_the_ID()); ?></div>
        </div>

    <?php endwhile; ?>

    </div>

<?php endif; ?>

</div>

<?php get_template_part('pagination'); ?>

<?php get_footer(); ?>
