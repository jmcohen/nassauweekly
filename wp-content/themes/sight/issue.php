<?php get_header();?>

 <?php
    $issue_slug = get_query_var('issue');
    query_posts(array('name' => $issue_slug, 'posts_per_page' => 1, 'post_type' => 'issue',  'post_status' => 'publish'));
    the_post();
    $issue = get_post();
    $previous_issue = get_previous_post();
    $next_issue = get_next_post();

    function format_issue_date($issue) {
        return date_format(new DateTime($issue->post_date), 'F d, Y'); 
    }
 ?>

<?php if (has_post_thumbnail($issue->ID)) { ?>
    <div class="issue-thumbnail">
    <?php echo get_the_post_thumbnail( $issue->ID, array(300, 464), array('class' => 'issue-thumbnail')); ?>
    </div>
<?php } ?> 

<div class="issue-info">
<div class="issue-title"><?php echo $issue->post_title;?></div>
<div class="issue-date"><?php echo format_issue_date($issue) ?></div>
</div>

<?php wp_reset_query();?>

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

<div class="adjacent-issues">
    <div class="col left">
        <?php if ($previous_issue): ?>
            <a class="issue-link" href="<?php echo get_permalink($previous_issue->ID)?>" style="float: left;">
                &larr; &nbsp;
                <?php echo format_issue_date($previous_issue); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="col middle">
        <p class="issue-link current" style="text-align: center;">
            <?php echo format_issue_date($issue); ?>
        </p>
    </div>

    <div class="col right">
        <?php if ($next_issue): ?>
            <a class="issue-link" href="<?php echo get_permalink($next_issue->ID)?>" style="float: right;" >
                <?php echo format_issue_date($next_issue); ?>
                &nbsp; &rarr; 

            </a>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
