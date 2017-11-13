<?php
  $args = array(
			'post_type' => 'product',
			'posts_per_page' => 100
			);
	$loop = new WP_Query( $args );
	if ( $loop->have_posts() ) {
    while ( $loop->have_posts() ) : $product = $loop->the_post();
      wc_get_template_part( 'partials/store-item' );
    endwhile; // end of the loop. 
  } else {
		echo __( 'No products found' );
	}
wp_reset_postdata();
