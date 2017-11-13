<?php

function _wpsc_is_country_disabled( $country, $args ) {
	$defaults = array(
			'acceptable'        => null,
			'acceptable_ids'    => null,
			'selected'          => '',
			'disabled'          => null,
			'disabled_ids'      => null,
			'placeholder'       => __( 'Please select a country', 'wp-e-commerce' ),
			'include_invisible' => false,
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	$isocode = $country->get_isocode();
	$id      = $country->get_id();

	if ( is_array( $acceptable ) && ! in_array( $isocode, $acceptable ) )
		return true;

	if ( is_array( $acceptable_ids ) && ! in_array( $id, $acceptable_ids ) )
		return true;

	if ( is_array( $disabled ) && in_array( $isocode, $disabled ) )
		return true;

	if ( is_array( $disabled_ids ) && in_array( $id, $disabled_ids ) )
		return true;

	return false;
}

/**
 * Get the country dropdown options, presumably for the checkout or customer profile pages
 *
 * @param 	string|array  	$args
 *
 * @return 	string			HTML representation of the dropdown
 */
function _wpsc_country_dropdown_options( $args = '' ) {
	$defaults = array(
			'acceptable'        => null,
			'acceptable_ids'    => null,
			'selected'          => '',
			'disabled'          => null,
			'disabled_ids'      => null,
			'placeholder'       => __( 'Please select a country', 'wp-e-commerce' ),
			'include_invisible' => false,
	);

	$args   = wp_parse_args( $args, $defaults );
	$output = '';

	$countries = WPSC_Countries::get_countries( $args['include_invisible'] );

	// if the user has a choice of countries the
	if ( ( count( $countries ) > 1 ) && ! empty( $args['placeholder'] ) ) {
		$output .= "<option value=''>" . esc_html( $args['placeholder'] ) . "</option>\n\r";
	}

	foreach ( $countries as $country ) {

		$isocode = $country->get_isocode();
		$name    = $country->get_name();

		// if there is only one country in the list select it
		if ( count( $countries ) == 1 ) {
			$args['selected'] = $isocode;
		}

		// if we're in admin area, and the legacy country code "UK" or "TP" is selected as the
		// base country, we should display both this and the more proper "GB" or "TL" options
		// and distinguish these choices somehow
		if ( is_admin() && 11 > wpsc_core_get_db_version() ) {
			if ( in_array( $isocode, array( 'TP', 'UK' ) ) ) {
				/* translators: This string will mark the legacy isocode "UK" and "TP" in the country selection dropdown as "legacy" */
				$name = sprintf( __( '%s (legacy)', 'wp-e-commerce' ), $name );
			} elseif ( in_array( $isocode, array( 'GB', 'TL' ) ) ) {
				/* translators: This string will mark the legacy isocode "GB" and "TL" in the country selection dropdown as "ISO 3166" */
				$name = sprintf( __( '%s (ISO 3166)', 'wp-e-commerce' ), $name );
			}
		}

		$output .= sprintf(
				'<option value="%1$s" %2$s %3$s>%4$s</option>' . "\n\r",
				/* %1$s */ esc_attr( $isocode ),
				/* %2$s */ selected( $args['selected'], $isocode, false ),
				/* %3$s */ disabled( _wpsc_is_country_disabled( $country, $args ), true, false ),
				/* %4$s */ esc_html( $name )
		);
	}

	return $output;
}

/**
 * Get the country dropdown HTML, presumably for the checkout or customer profile pages
 *
 * @param 	string|array  	$args
 *
 * @return 	string			HTML representation of the dropdown
 */
function wpsc_get_country_dropdown( $args = '' ) {

	$defaults = array(
			'name'                  => 'wpsc_countries',
			'id'                    => 'wpsc-country-dropdown',
			'class'                 => '',
			'additional_attributes' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	// we are going to remember everytime we create a country dropdown so that we can put a unique id
	// on each HTML element
	static $country_dropdown_counts = array();

	if ( ! isset( $country_dropdown_counts[ $args['id'] ] ) ) {
		$country_dropdown_counts[ $args['id'] ] = 1;
	} else {
		$country_dropdown_counts[ $args['id'] ] = $country_dropdown_counts[ $args['id'] ] + 1;
		$args['id'] = $args['id']  . '-' . $country_dropdown_counts[ $args['id'] ];
	}

	$output = sprintf(
			'<select name="%1$s" id="%2$s" class="%3$s wpsc-country-dropdown" %4$s>',
			/* %1$s */ esc_attr( $args['name'] ),
			/* %2$s */ esc_attr( $args['id'] ),
			/* %3$s */ esc_attr( $args['class'] ),
			/* %4$s */ $args['additional_attributes']
	);

	$output .= _wpsc_country_dropdown_options( $args );

	$output .= '</select>';

	return $output;
}

/**
 * Echo the country dropdown HTML, presumably for the checkout or customer profile pages
 *
 * @param 	string|array  	$args
 *
 * @return 	string			HTML representation of the dropdown
 */
function wpsc_country_dropdown( $args = '' ) {
	echo wpsc_get_country_dropdown( $args );
}
