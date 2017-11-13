<?php
/**
 * The template for displaying link post formats
 *
 * Used for both single and index/archive/search.
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php twentyfifteen_post_thumbnail(); ?>

	<header class="entry-header">
		<?php
			if ( is_single() ) :
				the_title( sprintf( '<h1 class="entry-title"><a href="%s">', esc_url( twentyfifteen_get_link_url() ) ), '</a></h1>' );
			else :
				$target = is_home() ? 'target="_blank"' : '';
				the_title( sprintf( '<h2 class="entry-title"><a href="%s" '.$target.'>', esc_url( twentyfifteen_get_link_url() ) ), '</a></h2>' );
			endif;
		?>
	</header>
	<!-- .entry-header -->

</article><!-- #post-## -->
