<div class="store-item col-sm-3 col-xs-6">
	<a href="<?php the_permalink(); ?>" <?php if (is_home()) echo 'target="_blank"'; ?>>
		<?php
    global $product;
			if ( has_post_thumbnail() ) {
				$image_caption = get_post( get_post_thumbnail_id() )->post_excerpt;
				$image_link    = wp_get_attachment_url( get_post_thumbnail_id() );
				$image         = get_the_post_thumbnail( $post->ID, apply_filters(
				'single_product_large_thumbnail_size', 'shop_catalog' ), 
				array(
					'title'	=> get_the_title( get_post_thumbnail_id())
				) );

				$attachment_count = count( $product->get_gallery_attachment_ids() );

				if ( $attachment_count > 0 ) {
					$gallery = '[product-gallery]';
				} else {
					$gallery = '';
				}

				echo $image;

			} else {

				echo apply_filters( 'woocommerce_single_product_image_html',
				sprintf( '<img src="%s" alt="%s" />', wc_placeholder_img_src(),
				__( 'Placeholder', 'woocommerce' ) ), $post->ID );

			}
		?>
		<div class="product-info">
			<strong><?php echo $product->get_title(); ?></strong>
			<em>€ <?php echo $product->get_price(); ?></em>
		</div>
	</a>
</div>
