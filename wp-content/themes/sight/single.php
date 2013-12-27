<?php get_header(); ?>

<?php if ( have_posts() ) : ?>
<?php while ( have_posts() ) : the_post(); ?>

<div class="content-title">
  <?php the_category(' <span>/</span> '); ?>
  <a href="http://facebook.com/share.php?u="
    <?php the_permalink() ?>&amp;t=<?php echo urlencode(the_title('','', false)) ?>" target="_blank" class="f" title="Share on Facebook">
  </a>
  <a href="http://twitter.com/home?status="
    <?php the_title(); ?> <?php echo getTinyUrl(get_permalink($post->ID)); ?>" target="_blank" class="t" title="Spread the word on Twitter">
  </a>
  <a href="http://digg.com/submit?phase=2&amp;url="
    <?php the_permalink() ?>&amp;title=<?php the_title(); ?>" target="_blank" class="di" title="Bookmark on Del.icio.us">
  </a>
  <a href="http://stumbleupon.com/submit?url="
    <?php the_permalink() ?>&amp;title=<?php echo urlencode(the_title('','', false)) ?>" target="_blank" class="su" title="Share on StumbleUpon">
  </a>
</div>

<div class="entry">
  <div <?php post_class('single clear'); ?> id="post_<?php the_ID(); ?>">
  <div class="post-meta">
    <h1>
      <?php the_title(); ?>
    </h1>
    by <span class="post-author">
    <?php the_author(); ?>
      <a href="
        <?php echo get_author_posts_url(get_the_author_meta('ID')); ?>">
      </a></span>
      <?php $issue = reset(get_the_terms($post->ID, 'issue')); ?>
      <?php if ($issue){?>
          in <span class="post-date"><a href="/issue/<?php echo $issue->slug?>"><?php echo $issue->name;?></a></span>
      <?php } ?>
    </span> on <span
                        class="post-date">
      <?php the_time(__('M j, Y')) ?>
    </span> <?php edit_post_link( __( 'Edit entry'), '&bull; '); ?><a
                        href="#comments" class="post-comms">
      <?php comments_number(__('No Comments'), __('1 Comment'), __('% Comments'), '', __('Comments Closed') ); ?>
    </a>
  </div>
  <div class="post-content">
    <?php the_content(); ?>
    <br />
<iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo urlencode(get_permalink($post->ID)); ?>&amp;layout=standard&amp;show_faces=false&amp;
width=450&amp;action=like&amp;colorscheme=light" scrolling="no" frameborder="0" allowTransparency="true" style="border:none; overflow:hidden; width:450px; height:60px;">
</iframe>
  </div>
            </div>
        </div>

        <?php endwhile; ?>
  <?php endif; ?>

  <?php comments_template(); ?>

  <?php get_footer(); ?>