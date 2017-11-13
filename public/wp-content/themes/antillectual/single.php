<?php get_header(); ?>
	<div id="primary" class="content-area">
		<main id="main" class="site-main single-post" role="main">
			<?php
			// Start the loop.
			while ( have_posts() ) : the_post();
			?>
			<h2 class="title"><?php the_title(); ?></h2>
			<div class="outer">
				<div class="inner">
				<?php the_content(); ?>
				</div><!-- .inner -->
			</div><!-- .outer -->

			<?php
			// the_post_navigation( array(
			// 	'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next', 'twentyfifteen' ) . '</span> ' .
			// 		'<span class="screen-reader-text">' . __( 'Next post:', 'twentyfifteen' ) . '</span> ' .
			// 		'<span class="post-title">%title</span>',
			// 	'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous', 'twentyfifteen' ) . '</span> ' .
			// 		'<span class="screen-reader-text">' . __( 'Previous post:', 'twentyfifteen' ) . '</span> ' .
			// 		'<span class="post-title">%title</span>',
			// ) );
			?>
			<?php
			// End the loop.
			endwhile;
			?>
		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_footer(); ?>
