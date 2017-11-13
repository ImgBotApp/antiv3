<?php
add_action('init', 'addShortcodes', 99);

function addShortcodes () {
	add_shortcode( 'extractcountry' , 'get_country' );
	add_shortcode( 'extractlinks' , 'extract_links' );
}
