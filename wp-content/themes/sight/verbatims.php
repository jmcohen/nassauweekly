<?php get_header();?>

<div class="content-title">
    Verbatim
</div>

<div style="padding: 30px 0 0 30px; font-size: 16px; font-style: italic;">Verbatims may be submitted to thenassauweekly@gmail.com.</div>

<?php 
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
query_posts(array(
        'paged' => $paged,
        'post_type' => 'nw_verbatim',
        'posts_per_page' => '30',
        'offset' => '0',
    )
); ?>


<?php if ( have_posts() ) : ?>

    <div id="loop" class="list clear">

    <?php while ( have_posts() ) : the_post(); ?>

	<div class="post post-verbatim" id="post_<?php the_ID(); ?>">

        <div class="post-verbatim-context"><?php the_title(); ?>:</div>
        <div class="post-verbatim-date"><?php echo get_the_date(); ?></div>
        <div class="post-verbatim-quotation"><?php global $post; echo nl2br(get_post_meta($post->ID,'quotation',true)); ?></div>
	</div>

    <?php endwhile; ?>

    </div>

<?php endif; ?>

<?php get_template_part('pagination'); ?>

<?php get_footer(); ?>
