<?php
add_action('init', 'addPostTypes', 99);

function addPostTypes () {
	register_post_type( 'anti_feature',
    array(
      'labels' => array(
        'name' => __( 'Features' ),
        'singular_name' => __( 'Feature' )
      ),
      'public' => true,
      'has_archive' => true,
      'rewrite' => array('slug' => 'features'),
    )
  );

	register_post_type( 'anti_video',
		array(
			'labels' => array(
				'name' => __( 'Videos' ),
				'singular_name' => __( 'Video' )
			),
			'public' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'videos'),
		)
	);
}
