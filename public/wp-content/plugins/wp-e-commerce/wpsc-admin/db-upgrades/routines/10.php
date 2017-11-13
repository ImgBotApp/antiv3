<?php

/**
 * Control database upgrade to version 10
 *
 * @access private
 * @since 3.8.14
 *
 */
function _wpsc_db_upgrade_10() {
	_wpsc_add_region_name_meta();
	_wpsc_maybe_create_visitor_tables();
	_wpsc_cleanup_visitor_meta_checkout_details();
}

/**
 * 3.8.14 supports country meta and a feature that let's a countries regions be called by the
 * proper name.  Here we initialize the values.
 *
 * @access private
 * @since 3.8.14
 *
 */
function _wpsc_add_region_name_meta() {
	$wpsc_country = new WPSC_Country( 'US' );
	$wpsc_country->set( 'region_label', __( 'State', 'wp-e-commerce' ) );

	$wpsc_country = new WPSC_Country( 'CA' );
	$wpsc_country->set( 'region_label', __( 'Province', 'wp-e-commerce' ) );
}

/**
 * 3.8.14 maintains visitor information in a visitors table, create it
 *
 * @access private
 * @since 3.8.14
 *
 */
function _wpsc_maybe_create_visitor_tables() {
	_wpsc_create_visitor_table();
	_wpsc_create_visitor_meta_table();
	_wpsc_meta_migrate_anonymous_users();
}

/**
 * starting in 3.8.14 visitors information belongs in the vistor table, not the users table, start
 * the migration.  This migration could take a very long time so it's done as a cron
 *
 * @access private
 * @since 3.8.14
 *
 */
function _wpsc_meta_migrate_anonymous_users() {
	global $wpdb;

	// get the users table auto increment value, and set the visitor meta auto increment to match.
	// we do this so that new customer cookies don't collide with existing customer cookies after
	// the migration.  Note we can't use max of user id because users may have been deleted.
	$sql = 'SHOW TABLE STATUS WHERE NAME = "' . $wpdb->users . '"';
	$status = $wpdb->get_results( $sql );

	$wpdb->query( 'ALTER TABLE ' . $wpdb->wpsc_visitors . ' AUTO_INCREMENT = ' . $status[0]->Auto_increment );

	wp_suspend_cache_addition( true );

	$role = get_role( 'wpsc_anonymous' );

	if ( $role ) {
		remove_role( 'wpsc_anonymous', __( 'Anonymous', 'wp-e-commerce' ) );
	}

	wp_schedule_single_event( time() + 5 , 'wpsc_migrate_anonymous_user_cron' );

}


/**
 * Create the table for visitors
 *
 * @since 3.8.14
 * @access private
 *
 */
function _wpsc_create_visitor_table() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	global $charset_collate;

	$sql = 'CREATE TABLE IF NOT EXISTS '. $wpdb->wpsc_visitors .' ('
			.'id bigint(20) unsigned NOT NULL AUTO_INCREMENT, '
			.'user_id bigint(20) unsigned DEFAULT NULL , '
			.'last_active timestamp NULL DEFAULT NULL, '
			.'expires timestamp NULL DEFAULT NULL, '
			.'created timestamp NULL DEFAULT NULL, '
			.'PRIMARY KEY  ( `id` ), '
			.'KEY user_id ( `user_id` ), '
			.'KEY expires ( `expires` ), '
			.'KEY last_active ( `last_active` ), '
			.'KEY created ( `created` ) '
			.') '. $charset_collate;

	dbDelta( $sql );

	_wpsc_create_well_known_visitors();
}

/**
 * Create the meta table for visitor meta
 *
 * @since 3.8.14
 * @access private
 *
 */
function _wpsc_create_visitor_meta_table() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	global $charset_collate;

	$sql = 'CREATE TABLE IF NOT EXISTS '. $wpdb->wpsc_visitormeta .' ('
			.'meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT, '
			.'wpsc_visitor_id bigint(20) unsigned NOT NULL DEFAULT 0 , '
			.'meta_key varchar(255) DEFAULT NULL, '
			.'meta_value longtext, '
			.'meta_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, '
			.'PRIMARY KEY  (meta_id), '
			.'KEY wpsc_visitor_id (wpsc_visitor_id), '
			.'KEY meta_key (meta_key(191)), '
			.'KEY meta_value (meta_value(20)), '
			.'KEY meta_key_and_value (meta_key(191),meta_value(32)), '
			.'KEY meta_timestamp_index ( `meta_timestamp` ) '
			.') '. $charset_collate;

	dbDelta( $sql );
}
