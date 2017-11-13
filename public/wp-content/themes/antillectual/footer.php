<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the "site-content" div and all content after.
 *
 * @package WordPress
 * @subpackage Twenty_Fifteen
 * @since Twenty Fifteen 1.0
 */
?>

	</div><!-- .site-content -->
	<footer id="contact" class="site-footer container-fluid" role="contentinfo">
		<div class="outer row">
			<div class="col-sm-4">
				<?php
				loadPageBySlug ('contact/contact-1');
				?>
			</div>
			<div class="col-sm-4">
				<?php
				loadPageBySlug ('contact/contact-2');
				?>
			</div>
			<div class="col-sm-4">
				<?php
				loadPageBySlug ('contact/contact-3');
				?>
			</div>
			<?php //get_template_part ('partials/social-links'); ?>
		</div>
	</footer><!-- .site-footer -->

	<section id="partners">
		<div class="partners outer">
			<div class="inner">
				<a href="http://www.fairtrademerch.com" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/partners/ftm.png" /></a>
				<a href="http://www.fondspodiumkunsten.nl" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/partners/nfpk.png" /></a>
				<a href="http://www.hititdrums.nl" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/partners/hitit.png" /></a>
				<a href="http://www.justlikeyourmom.com" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/partners/jlym.png" /></a>
				<a href="http://www.typewriterdistro.nl" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/partners/twd.png" style="margin-top: -6px;"/></a>
				<a href="http://www.peta2.de" target="_blank"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/partners/peta2.png" /></a>
			</div>
		</div>
	</section>

</div><!-- .site -->
</div><!-- #page -->
<?php wp_footer(); ?>

</body>
</html>
