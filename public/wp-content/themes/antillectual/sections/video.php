<?php
$args = array( 'post_type' => 'anti_video', 'posts_per_page' => 10 );
$videos = new WP_Query( $args );
$slides = array();
$i = 0;
while ( $videos->have_posts() ) : $videos->the_post();
  $active = ($i == 0) ? 'active' : '';

  ob_start();
  the_content();
  $content = ob_get_contents();
  ob_end_clean();

  $slides[] = '<div class="item ' . $active . ' embed-responsive embed-responsive-16by9">'
  . $content .'</div>';
  $i++;
endwhile;

createCarousel ('video-carousel', $slides, true);
?>
