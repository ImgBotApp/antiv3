<?php
  $page = get_page_by_path ($PAGE_ID);
  $post = get_post ($page->ID);
  echo nl2br (do_shortcode ($post->post_content));
?>
