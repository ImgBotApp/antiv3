<?php if ( have_posts() ) : ?>

	<?php if ( is_home() && ! is_front_page() ) : ?>
		<header>
			<h2 class="page-title screen-reader-text"><?php single_post_title(); ?></h2>
		</header>
	<?php endif; ?>

	<?php
	while ( have_posts() ) : the_post();
		get_template_part( 'content-link' );
	endwhile;

// If no content, include the "No posts found" template.
else :
	get_template_part( 'content', 'none' );
endif;
