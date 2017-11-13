<?php

/**
 * Add meta data field to a purchase.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $purchase_id purchase ID.
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function wpsc_add_purchase_meta( $purchase_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'wpsc_purchase' , $purchase_id, $meta_key , $meta_value, $unique );
}

/**
 * Remove metadata matching criteria from a purchase.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $purchase_id purchase ID
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function wpsc_delete_purchase_meta( $purchase_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'wpsc_purchase', $purchase_id, $meta_key, $meta_value );
}

/**
 * Retrieve purchase meta field for a purchase.
 *
 * @since 3.8.14
 *
 * @param int $purchase_id purchase ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function wpsc_get_purchase_meta( $purchase_id, $key = '', $single = false ) {
	return get_metadata( 'wpsc_purchase' , $purchase_id, $key, $single );
}

/**
 *  Determine if a meta key is set for a given purchase.
 *
 * @since 3.8.14
 *
 * @param int $purchase purchase ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
* @return boolean true of the key is set, false if not.
 *  is true.
 */
function wpsc_purchase_meta_exists( $purchase_id, $meta_key ) {
	return metadata_exists( 'wpsc_purchase' , $purchase_id , $meta_key );
}

/**
 * Update purchase meta field based on purchase ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and purchase ID.
 *
 * If the meta field for the purchase does not exist, it will be added.
 *
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $purchase_id $purchase ID.
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function wpsc_update_purchase_meta( $purchase_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'wpsc_purchase' , $purchase_id , $meta_key , $meta_value , $prev_value );
}

/**
 * Delete everything from purchase meta matching meta key.
 * This meta data function mirrors a corresponding wordpress post meta function.
 * @since 3.8.14
 *
 * @param string $purchase_meta_key Key to search for when deleting.
 * @return bool Whether the purchase meta key was deleted from the database
 */
function wpsc_delete_purchase_meta_by_key( $purchase_meta_key ) {
	return delete_metadata( 'wpsc_purchase' , null , $purchase_meta_key , '' , true );
}

/**
 * Retrieve purchase meta fields, based on purchase ID.
 *
 * The purchase meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $purchase_id purchase ID.
 * @return array
 */
function wpsc_get_purchase_custom( $purchase_id = 0 ) {
	$purchase_id = absint( $purchase_id );
	return wpsc_get_purchase_meta( $purchase_id );
}

/**
 * Retrieve meta field names for a purchase.
 *
 * If there are no meta fields, then nothing(null) will be returned.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param int $purchase_id purchase ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 */
function wpsc_get_purchase_custom_keys( $purchase_id = 0 ) {
	$custom = wpsc_get_purchase_custom( $purchase_id );

	if ( ! is_array( $custom ) ) {
		return;
	}
	if ( $keys = array_keys( $custom ) ) {
		return $keys;
	}
}

/**
 * Retrieve values for a custom purchase field.
 *
 * The parameters must not be considered optional. All of the purchase meta fields
 * will be retrieved and only the meta field key values returned.
 * This meta data function mirrors a corresponding wordpress post meta function.
 *
 * @since 3.8.14
 *
 * @param string $key Meta field key.
 * @param int $purchase_id purchase ID
 * @return array Meta field values.
 */
function wpsc_get_purchase_custom_values( $key = '', $purchase_id = 0 ) {

	if ( ! $key ) {
		return null;
	}

	$custom = wpsc_get_purchase_custom( $purchase_id );

	return isset( $custom[ $key ] ) ? $custom[ $key ] : null;
}

/**
 * Calls function for each meta matching the timestamp criteria.  Callback function
 * will get a single parameter that is an object representing the meta.
 *
 * @since 3.8.14
 *
 * @param int|string $timestamp timestamp to compare meta items against, if int a unix timestamp is assumed,
 *								if string a mysql timestamp is assumed
 * @param string $comparison any one of the supported comparison operators,(=,>=,>,<=,<,<>,!=)
 * @param string $meta_key restrict testing of meta to the values with the specified meta key
 * @return array metadata matching the query
 */
function wpsc_get_purchase_meta_by_timestamp( $timestamp = 0, $comparison = '>', $metakey = '' ) {
	return wpsc_get_meta_by_timestamp( 'wpsc_purchase', $timestamp , $comparison , $metakey );
}

