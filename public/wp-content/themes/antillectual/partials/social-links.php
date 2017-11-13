<?php if ( has_nav_menu( 'social' ) ) : ?>
    <?php
      // Social links navigation menu.
      wp_nav_menu( array(
        'theme_location' => 'social',
        'menu_class'     => 'navbar-right nav navbar-nav navbar-social social-navigation',
        'depth'          => 1,
        'link_before'    => '<span class="screen-reader-text">',
        'link_after'     => '</span>'
      ) );
    ?>
<?php endif; ?>
