<?php if ( have_posts() ) : ?>

    <div id="loop" class="<?php if ($_COOKIE['mode'] == 'grid') echo 'grid'; else echo 'list'; ?> clear">

    <?php while ( have_posts() ) : the_post(); ?>

        <div <?php post_class('post clear'); ?> id="post_<?php the_ID(); ?>">
            <?php $issue = reset(get_the_terms($post->ID, 'issue')); ?>
            <?php if ( has_post_thumbnail() ) :?>
            <a href="<?php the_permalink() ?>" class="thumb"><?php the_post_thumbnail('thumbnail', array(
                        'alt'	=> trim(strip_tags( $post->post_title )),
                        'title'	=> trim(strip_tags( $post->post_title )),
                    )); ?></a>
            <?php elseif (is_archive() && $issue && has_post_thumbnail($issue->term_id)): ?>
            <a href="<?php the_permalink() ?>" class="thumb"><?php echo get_the_post_thumbnail($issue->term_id, array(200, 100), array(
                        'alt'   => trim(strip_tags( $post->post_title )),
                        'title' => trim(strip_tags( $post->post_title )),
                        'class' => 'wp-post-image-issue'
                    )); ?></a>            
            <?php endif; ?>

            <div class="post-category"><?php the_category(' / '); ?></div>
            <h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>

            <div class="post-meta">by <span class="post-author"><?php the_author(); ?></span>
            <?php if ($issue){?>
                in <span class="post-date"><a href="/issue/<?php echo $issue->slug?>"><?php echo $issue->name;?></a></span>
            <?php } ?>
                on <spanclass="post-date"><?php the_time(__('M j, Y')) ?></span> <em>&bull; </em><?php comments_popup_link(__('No Comments'), __('1 Comment'), __('% Comments'), '', __('Comments Closed')); ?> <?php edit_post_link( __( 'Edit entry'), '<em>&bull; </em>'); ?>
            </div>
            <div class="post-content"><?php if (function_exists('smart_excerpt')) smart_excerpt(get_the_excerpt(), 55); ?></div>
        </div>

    <?php endwhile; ?>

    </div>

<?php endif; ?>