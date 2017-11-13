<?php
/**
 * The sidebar containing the main widget area
 *
 */

if ( has_nav_menu( 'primary' ) || has_nav_menu( 'social' ) || is_active_sidebar( 'sidebar-1' )  ) : ?>
	<nav id="site-navigation" class="navbar-inverse navbar-static-top" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
				data-target="#navbar-collapse-primary" aria-expanded="false">
	        <span class="sr-only">Toggle navigation</span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	        <span class="icon-bar"></span>
	      </button>
	      <a class="navbar-brand" href="<?php bloginfo( 'url' ) ?>/#">Home</a>
	    <!-- </div> -->
			</div>
			<div class="collapse navbar-collapse" id="navbar-collapse-primary">
			<?php if ( has_nav_menu( 'primary' ) ) : ?>
							<?php
							// Primary navigation menu.
							wp_nav_menu( array(
								'menu_class'     => 'nav navbar-nav',
								'theme_location' => 'primary'
							) );
						?>


			<?php endif; ?>
			<?php get_template_part ('partials/social-links'); ?>
			</div><!-- .navbar-collapse -->
		</div><!-- .container-fluid -->
	</nav><!-- .main-navigation -->

<?php endif; ?>
