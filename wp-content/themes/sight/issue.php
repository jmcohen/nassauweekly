<?php get_header();?>

 <?php
    $issue_slug = get_query_var('issue');
    $issues_ = get_posts(array('name' => $issue_slug, 'posts_per_page' => 1, 'post_type' => 'issue',  'post_status' => 'publish'));
    $issue = $issues_[0];
 ?>

<?php if (has_post_thumbnail($issue->ID)) { ?>
    <div class="issue-thumbnail">
    <?php echo get_the_post_thumbnail( $issue->ID, array(300, 464), array('class' => 'issue-thumbnail')); ?>
    </div>
<?php } ?> 

<div class="issue-info">
<div class="issue-title"><?php echo $issue->post_title;?></div>
<div class="issue-date"><?php echo date_format(new DateTime($issue->post_date), 'F d, Y'); ?></div>
</div>

<?php query_posts(array('post_type' => 'post','posts_per_page' => '100','issue' => $issue->ID)); ?>

<?php if ( have_posts() ) : ?>

    <div class="issue-posts">

        <?php while ( have_posts() ) : the_post(); ?>

        <p class="issue-post"><span class="title"><a href="<?php the_permalink();?>"><?php the_title(); ?></a> <span class="author"><?php the_author(); ?></span></p>

        <?php endwhile; ?>

    </div>

<?php endif; ?>

<?php wp_reset_query();?>

<?php 
    wp_reset_postdata();
    $query = new WP_Query(array('post_type' => 'nw_verbatim', 'posts_per_page' => '100', 'issue' => $issue->ID));

    if ($query->have_posts()){
        echo "<div class='issue-this-weeks-verbatim'><p>This Week's Verbatim</p></div>";
    }

    while ($query->have_posts()){
        $query->next_post();
        $verbatim = $query->post;
        $customFields = get_post_custom($verbatim->ID);

        echo '<div class="issue-verbatim">';
        echo '<div class="issue-context">';
        echo $verbatim->post_title;
        echo ':</div>';
        echo '<div class="issue-quotation">';
        echo nl2br($customFields['quotation'][0]);
        echo '</div></div>';
    }
    wp_reset_postdata();
?>

<?php get_footer(); ?>
