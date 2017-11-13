<?php
$args = array( 'post_type' => 'anti_feature', 'posts_per_page' => 10 );
$features = new WP_Query( $args );
$slides = array();
$i = 0;
while ( $features->have_posts() ) : $features->the_post();
  $active = ($i == 0) ? 'active' : '';

  ob_start();
  the_content();
  $content = ob_get_contents();
  ob_end_clean();

  $slides[] = '<div class="item ' . $active . '">'
  . $content .
    '<div class="carousel-caption">
    ' . get_the_title() . '
    </div>
  </div>';
  $i++;
endwhile;
if ($i > 0) {
  createCarousel ('features-carousel', $slides, false, 8000);
}
