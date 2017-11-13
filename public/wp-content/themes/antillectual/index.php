<?php get_header(); ?>
	<div id="primary" class="content-area">

		<section id="home">
			<div class="outer">
				<div class="inner">
					<?php get_template_part ('sections/features'); ?>
				</div>
			</div>
		</section><!-- #featured -->

		<main id="latest" class="site-main" role="main">
			<div class="outer">
				<div class="inner">
				<?php get_template_part ('sections/latest'); ?>
				</div>
			</div>
		</main><!-- .site-main -->

		<section id="shows">
			<h2 class="title">Upcoming shows</h2>
			<div class="outer">
				<div class="inner">
				<?php get_template_part ('sections/shows-short'); ?>
				</div>
			</div>
		</section><!-- #shows -->

		<section id="video">
			<h2 class="title">Video</h2>
			<div class="outer">
				<div class="inner">
				<?php get_template_part ('sections/video'); ?>
				</div>
			<div>
		</section><!-- #video -->

		<section id="music">
			<h2 class="title">Music</h2>
			<div class="outer">
				<div class="inner container-fluid">
				<?php get_template_part ('sections/music'); ?>
				</div>
			</div>
		</section><!-- #music -->

		<section id="store">
			<h2 class="title">Store</h2>
			<div class="outer">
				<div class="inner container-fluid">
				<?php get_template_part ('sections/store'); ?>
				</div>
			</div>
		</section><!-- #store -->

		<!-- <section id="instagram">
			<div class="outer">
				<div class="inner">
				<?php // get_template_part ('sections/instagram'); ?>
				</div>
			</div>
		</section> -->
		<!-- #instagram -->

	</div><!-- .content-area -->

<?php get_footer(); ?>
