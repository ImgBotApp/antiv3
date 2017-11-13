<?php
  $shows = get_page_by_path ('next-3-shows');
  $post = get_post ($shows->ID);
?>
<div class="entry-content">
  <?php echo do_shortcode ($post->post_content) ?>
</div>
