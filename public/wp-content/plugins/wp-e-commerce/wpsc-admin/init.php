<?php
function wpsc_ajax_sales_quarterly() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	$lastdate = sanitize_text_field( $_POST['add_start'] );
	$date = preg_split( '/-/', $lastdate );
	if ( !isset( $date[0] ) )
		$date[0] = 0;
	if ( !isset( $date[1] ) )
		$date[1] = 0;
	if ( !isset( $date[2] ) )
		$date[2] = 0;
	$lastquart = mktime( 0, 0, 0, $date[1], $date[2], $date[0] );
	if ( $lastquart != get_option( 'wpsc_last_quarter' ) ) {
		update_option( 'wpsc_last_date', $lastdate );
		update_option( 'wpsc_fourth_quart', $lastquart );
		$thirdquart = mktime( 0, 0, 0, $date[1] - 3, $date[2], $date[0] );
		update_option( 'wpsc_third_quart', $thirdquart );
		$secondquart = mktime( 0, 0, 0, $date[1] - 6, $date[2], $date[0] );
		update_option( 'wpsc_second_quart', $secondquart );
		$firstquart = mktime( 0, 0, 0, $date[1] - 9, $date[2], $date[0] );
		update_option( 'wpsc_first_quart', $firstquart );
		$finalquart = mktime( 0, 0, 0, $date[1], $date[2], $date[0] - 1 );
		update_option( 'wpsc_final_quart', $finalquart );
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'wpsc_quarterly') )
	add_action( 'admin_init', 'wpsc_ajax_sales_quarterly' );

function wpsc_delete_file() {
	$product_id = absint( $_REQUEST['product_id'] );
	$file_name  = basename( $_REQUEST['file_name'] );
	check_admin_referer( 'delete_file_' . $file_name );

	_wpsc_delete_file( $product_id, $file_name );

	$sendback = wp_get_referer();
	wp_redirect( $sendback );
	exit;
}


if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'delete_file') )
	add_action( 'admin_init', 'wpsc_delete_file' );

/**
 *  Function and action for publishing or unpublishing single products
 */
function wpsc_ajax_toggle_published() {
	$product_id = absint( $_GET['product'] );
	check_admin_referer( 'toggle_publish_' . $product_id );

	$status = (wpsc_toggle_publish_status( $product_id )) ? ('true') : ('false');
	$sendback = add_query_arg( 'flipped', "1", wp_get_referer() );
	wp_redirect( esc_url_raw( $sendback ) );
	exit();
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'toggle_publish') )
	add_action( 'admin_init', 'wpsc_ajax_toggle_published' );

/**
 * Function and action for duplicating products,
 * Refactored for 3.8
 * Purposely not duplicating stick post status (logically, products are most often duplicated because they share many attributes, where products are generally 'featured' uniquely.)
 */
function wpsc_duplicate_product() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	// Get the original post
	$id = absint( $_GET['product'] );
	$post = get_post( $id );

	// Copy the post and insert it
	if ( isset( $post ) && $post != null ) {

		$duplicate = new WPSC_Duplicate_Product( $post->ID );
		$new_id = $duplicate->duplicate_product_process();

		$duplicated = true;
		$sendback = wp_get_referer();
		$sendback = add_query_arg( 'duplicated', (int) $duplicated, $sendback );

		wp_redirect( esc_url_raw( $sendback ) );
		exit();
	} else {
		wp_die( __( 'Sorry, for some reason, we couldn\'t duplicate this product because it could not be found in the database, check there for this ID: ', 'wp-e-commerce' ) . $id );
	}
}

if ( isset( $_GET['wpsc_admin_action'] ) && ( $_GET['wpsc_admin_action'] == 'duplicate_product' ) )
    add_action( 'admin_init', 'wpsc_duplicate_product' );

function wpsc_purchase_log_csv() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	global $wpdb, $wpsc_gateways;
	get_currentuserinfo();
	$count = 0;

	if ( 'key' == $_REQUEST['rss_key'] ) {
		if ( isset( $_REQUEST['start_timestamp'] ) && isset( $_REQUEST['end_timestamp'] ) ) {
			$start_timestamp = $_REQUEST['start_timestamp'];
			$end_timestamp   = $_REQUEST['end_timestamp'];
			$start_end_sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date` BETWEEN '%d' AND '%d' ORDER BY `date` DESC";
			$start_end_sql = apply_filters( 'wpsc_purchase_log_start_end_csv', $start_end_sql );
			$data = $wpdb->get_results( $wpdb->prepare( $start_end_sql, $start_timestamp, $end_timestamp ), ARRAY_A );
			/* translators: %1$s is "start" date, %2$s is "to" date */
			$csv_name = _x( 'Purchase Log %1$s to %2$s.csv', 'exported purchase log csv file name', 'wp-e-commerce' );
			$csv_name = sprintf( $csv_name, date( "M-d-Y", $start_timestamp ), date( "M-d-Y", $end_timestamp ) );
		} elseif ( isset( $_REQUEST['m'] ) ) {
			$year = (int) substr( $_REQUEST['m'], 0, 4);
			$month = (int) substr( $_REQUEST['m'], -2 );
			$month_year_sql = "
				SELECT *
				FROM " . WPSC_TABLE_PURCHASE_LOGS . "
				WHERE YEAR(FROM_UNIXTIME(date)) = %d AND MONTH(FROM_UNIXTIME(date)) = %d
				ORDER BY `id` DESC
			";
			$month_year_sql = apply_filters( 'wpsc_purchase_log_month_year_csv', $month_year_sql );
			$data = $wpdb->get_results( $wpdb->prepare( $month_year_sql, $year, $month ), ARRAY_A );
			/* translators: %1$s is month, %2$s is year */
			$csv_name = _x( 'Purchase Log %1$s/%2$s.csv', 'exported purchase log csv file name', 'wp-e-commerce' );
			$csv_name = sprintf( $csv_name, $month, $year );
		} else {
			$sql = apply_filters( 'wpsc_purchase_log_month_year_csv', "SELECT * FROM " . WPSC_TABLE_PURCHASE_LOGS . " ORDER BY `id` DESC" );
			$data = $wpdb->get_results( $sql, ARRAY_A );
			$csv_name = _x( "All Purchase Logs.csv", 'exported purchase log csv file name', 'wp-e-commerce' );
		}

		$form_sql = "SELECT * FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `active` = '1' AND `type` != 'heading' ORDER BY `checkout_order` DESC;";
		$form_data = $wpdb->get_results( $form_sql, ARRAY_A );

		$headers_array = array(
			_x( 'Purchase ID'   , 'purchase log csv headers', 'wp-e-commerce' ),
			_x( 'Purchase Total', 'purchase log csv headers', 'wp-e-commerce' ),
		);
		$headers2_array = array(
			_x( 'Payment Gateway', 'purchase log csv headers', 'wp-e-commerce' ),
			_x( 'Payment Status' , 'purchase log csv headers', 'wp-e-commerce' ),
			_x( 'Purchase Date'  , 'purchase log csv headers', 'wp-e-commerce' ),
		);
		$form_headers_array = array();

		$output = '';

		foreach ( (array) $form_data as $form_field ) {
			if ( empty ( $form_field['unique_name'] ) ) {
				$form_headers_array[] = $form_field['name'];
			} else {
				$prefix = false === strstr( $form_field['unique_name'], 'billing' ) ? _x( 'Shipping ', 'purchase log csv header field prefix', 'wp-e-commerce' ) : _x( 'Billing ', 'purchase log csv header field prefix', 'wp-e-commerce' );
				$form_headers_array[] = $prefix . $form_field['name'];
			}
		}

		foreach ( (array) $data as $purchase ) {
			$form_headers = '';
			$output .= "\"" . $purchase['id'] . "\","; //Purchase ID
			$output .= "\"" . $purchase['totalprice'] . "\","; //Purchase Total
			foreach ( (array) $form_data as $form_field ) {
				$collected_data_sql = "SELECT * FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` = '" . $purchase['id'] . "' AND `form_id` = '" . $form_field['id'] . "' LIMIT 1";
				$collected_data = $wpdb->get_results( $collected_data_sql, ARRAY_A );
				$collected_data = $collected_data[0];

				if (  ( 'billingstate' == $form_field['unique_name'] || 'shippingstate' == $form_field['unique_name'] ) && is_numeric( $collected_data['value'] ) )
					$output .= "\"" . wpsc_get_state_by_id( $collected_data['value'], 'code' ) . "\","; // get form fields
				else
					$output .= "\"" . str_replace( array( "\r", "\r\n", "\n" ), ' ', $collected_data['value'] ) . "\","; // get form fields
			}

			if ( isset( $wpsc_gateways[$purchase['gateway']] ) && isset( $wpsc_gateways[$purchase['gateway']]['display_name'] ) )
				$output .= "\"" . $wpsc_gateways[$purchase['gateway']]['display_name'] . "\","; //get gateway name
			else
				$output .= "\"\",";


			$status_name = wpsc_find_purchlog_status_name( $purchase['processed'] );

			$output .= "\"" . $status_name . "\","; //get purchase status
			$output .= "\"" . date( "jS M Y", $purchase['date'] ) . "\","; //date

			$cartsql = "SELECT `prodid`, `quantity`, `name` FROM `" . WPSC_TABLE_CART_CONTENTS . "` WHERE `purchaseid`=" . $purchase['id'] . "";
			$cart = $wpdb->get_results( $cartsql, ARRAY_A );

			if ( $count < count( $cart ) )
			    $count = count( $cart );

			$items = count( $cart );
			$i     = 1;

			// Go through all products in cart and display quantity and sku
			foreach ( (array) $cart as $item ) {
				$skuvalue = get_product_meta( $item['prodid'], 'sku', true );
				if( empty( $skuvalue ) )
				    $skuvalue = __( 'N/A', 'wp-e-commerce' );
				$output .= "\"" . $item['quantity'] . "\",";
				$output .= "\"" . str_replace( '"', '\"', $item['name'] ) . "\",";

				if ( $items <= 1 )
					$output .= "\"" . $skuvalue . "\"" ;
				elseif ( $items > 1 && $i != $items  )
					$output .= "\"" . $skuvalue . "\"," ;
				else
					$output .= "\"" . $skuvalue . "\"" ;

				$i++;
			}

			$output .= "\n"; // terminates the row/line in the CSV file
		}
		// Get the most number of products and create a header for them
		$headers3 = array();
		for( $i = 0; $i < $count; $i++ ){
			$headers3[] = _x( 'Quantity', 'purchase log csv headers', 'wp-e-commerce' );
			$headers3[] = _x( 'Product Name', 'purchase log csv headers', 'wp-e-commerce' );
			$headers3[] = _x( 'SKU', 'purchase log csv headers', 'wp-e-commerce' );
		}

		$headers      = '"' . implode( '","', $headers_array ) . '",';
		$form_headers = '"' . implode( '","', $form_headers_array ) . '",';
		$headers2     = '"' . implode( '","', $headers2_array ) . '",';
		$headers3     = '"' . implode( '","', $headers3 ) . '"';

		$headers      = apply_filters( 'wpsc_purchase_log_csv_headers', $headers . $form_headers . $headers2 . $headers3, $data, $form_data );
		$output       = apply_filters( 'wpsc_purchase_log_csv_output' , $output, $data, $form_data );

		/**
		 * Fires when the WPSC purchase log is exported as a CSV
		 */
		do_action( 'wpsc_purchase_log_csv' );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: inline; filename="' . $csv_name . '"' );
		echo $headers . "\n". $output;
		exit;
	}
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'wpsc_downloadcsv') ) {
	add_action( 'admin_init', 'wpsc_purchase_log_csv' );
}

if ( isset( $_GET['purchase_log_csv'] ) && ( 'true' == $_GET['purchase_log_csv'] ) )
	add_action( 'admin_init', 'wpsc_purchase_log_csv' );

function wpsc_admin_sale_rss() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	global $wpdb;
	if ( ($_GET['rss'] == "true") && ($_GET['rss_key'] == 'key') && ($_GET['action'] == "purchase_log") ) {
		$sql = "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS . "` WHERE `date`!='' ORDER BY `date` DESC";
		$purchase_log = $wpdb->get_results( $sql, ARRAY_A );
		header( "Content-Type: application/xml; charset=UTF-8" );
		header( 'Content-Disposition: inline; filename="WP_E-Commerce_Purchase_Log.rss"' );
		$output = '';
		$output .= "<?xml version='1.0'?>\n\r";
		$output .= "<rss version='2.0'>\n\r";
		$output .= "  <channel>\n\r";
		$output .= "    <title>" . _x( 'WP eCommerce Product Log', 'admin rss product feed', 'wp-e-commerce' ) . "</title>\n\r";
		$output .= "    <link>" . admin_url( 'admin.php?page=' . WPSC_DIR_NAME . '/display-log.php' ) . "</link>\n\r";
		$output .= "    <description>" . _x( 'This is the WP eCommerce Product Log RSS feed', 'admin rss product feed', 'wp-e-commerce' ) . "</description>\n\r";
		$output .= "    <generator>" . _x( 'WP eCommerce Plugin', 'admin rss product feed', 'wp-e-commerce' ) . "</generator>\n\r";

		foreach ( (array)$purchase_log as $purchase ) {
			$purchase_link = admin_url( 'admin.php?page=' . WPSC_DIR_NAME . '/display-log.php' ) . "&amp;purchaseid=" . $purchase['id'];
			$purchase_title = _x( 'Purchase # %d', 'admin rss product feed', 'wp-e-commerce' );
			$purchase_title = sprintf( $purchase_title, $purchase['id'] );
			$output .= "    <item>\n\r";
			$output .= "      <title>{$purchase_title}</title>\n\r";
			$output .= "      <link>$purchase_link</link>\n\r";
			$output .= "      <description>" . _x( 'This is an entry in the purchase log', 'admin rss product feed', 'wp-e-commerce' ) . ".</description>\n\r";
			$output .= "      <pubDate>" . date( "r", $purchase['date'] ) . "</pubDate>\n\r";
			$output .= "      <guid>$purchase_link</guid>\n\r";
			$output .= "    </item>\n\r";
		}
		$output .= "  </channel>\n\r";
		$output .= "</rss>";
		echo $output;
		exit();
	}
}

if ( isset( $_GET['action'] ) && ( 'purchase_log' == $_GET['action'] ) ) {
	add_action( 'admin_init', 'wpsc_admin_sale_rss' );
}

/**
 * Do Purchase Log Actions
 *
 * All purchase log actions are capability and nonce checked before calling
 * the relevent 'wpsc_purchase_log_action-{wpsc_purchase_log_action}' hook.
 *
 * @since  3.9.0
 */
function wpsc_do_purchase_log_actions() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	if ( isset( $_GET['wpsc_purchase_log_action'] ) && isset( $_GET['id'] ) && isset( $_GET['_wpnonce'] ) ) {
		$wpsc_purchase_log_action = sanitize_key( $_GET['wpsc_purchase_log_action'] );

		if ( wp_verify_nonce( $_GET['_wpnonce'], 'wpsc_purchase_log_action_' . $wpsc_purchase_log_action ) ) {

			do_action( 'wpsc_purchase_log_action-' . $wpsc_purchase_log_action, absint( $_GET['id'] ) );

		}
	}

}
add_action( 'admin_init', 'wpsc_do_purchase_log_actions' );

/**
 * Handle clear downloads lock purchase log action
 *
 * The 'wpsc_purchase_log_action-downloads_lock' action hook which calls this function is nonce and capability checked
 * in wpsc_do_purchase_log_actions() before triggering do_action( 'wpsc_purchase_log_action-downloads_lock' ).
 *
 * @since  3.9.0
 *
 * @param  int  $log_id  Purchase log ID.
 */
function wpsc_purchase_log_action_downloads_lock( $log_id ) {

	wpsc_purchlog_clear_download_items( $log_id );

	// Redirect back to purchase logs list
	$sendback = wp_get_referer();
	$sendback = esc_url_raw( add_query_arg( 'cleared', 1, $sendback ) );
	wp_redirect( $sendback );
	exit();

}
add_action( 'wpsc_purchase_log_action-downloads_lock', 'wpsc_purchase_log_action_downloads_lock' );

/**
 * Handle delete purchase log action
 *
 * The 'wpsc_purchase_log_action-delete' action hook which calls this function is nonce and capability checked
 * in wpsc_do_purchase_log_actions() before triggering do_action( 'wpsc_purchase_log_action-delete' ).
 *
 * @since  3.9.0
 *
 * @param  int  $log_id  Purchase log ID.
 */
function wpsc_purchase_log_action_delete( $log_id ) {

	$log = new WPSC_Purchase_Log( $log_id );
	$deleted = $log->delete();

	// Redirect back to purchase logs list
	$sendback = wp_get_referer();
	$sendback = remove_query_arg( array( 'c', 'id' ), $sendback );
	$sendback = esc_url_raw( add_query_arg( 'deleted', absint( $deleted ), $sendback ) );
	wp_redirect( $sendback );
	exit();

}
add_action( 'wpsc_purchase_log_action-delete', 'wpsc_purchase_log_action_delete' );

/**
 * Handle email receipt purchase log action
 *
 * The 'wpsc_purchase_log_action-email_receipt' action hook which calls this function is nonce and capability checked
 * in wpsc_do_purchase_log_actions() before triggering do_action( 'wpsc_purchase_log_action-email_receipt' ).
 *
 * @since  3.9.0
 *
 * @param  int  $log_id  Purchase log ID.
 */
function wpsc_purchase_log_action_email_receipt( $log_id ) {

	$sent = wpsc_purchlog_resend_email( $log_id );

	// Redirect back to purchase logs list
	$sendback = wp_get_referer();
	$sendback = esc_url_raw( add_query_arg( 'sent', absint( $sent ), $sendback ) );
	wp_redirect( $sendback );
	exit();

}
add_action( 'wpsc_purchase_log_action-email_receipt', 'wpsc_purchase_log_action_email_receipt' );

/**
 * Resend Purchase Log Email
 *
 * @param   int|string  $log_id  Required. Purchase log ID (empty string is deprecated).
 * @return  boolean              Sent successfully.
 */
function wpsc_purchlog_resend_email( $log_id = '' ) {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	global $wpdb;

	// Deprecate empty purchase log ID parameter.
	if ( $log_id == '' ) {
		_wpsc_doing_it_wrong( 'wpsc_purchlog_resend_email', __( '$log_id parameter requires a numeric purchase log ID.', 'wp-e-commerce' ), '3.9.0' );

		// Support redirect for legacy purposes for the moment
		$sendback = esc_url_raw( add_query_arg( 'sent', 0, wp_get_referer() ) );
		wp_redirect( $sendback );
		exit();

	}

	$log_id = absint( $log_id );

	if ( $log_id > 0 ) {

		$wpec_taxes_controller = new wpec_taxes_controller();

		if ( is_numeric( $log_id ) ) {
			$purchase_log = new WPSC_Purchase_Log( $log_id );
			return wpsc_send_customer_email( $purchase_log );
		}

	}

	return false;

}

// Deprecate resending purchase log email receipt via URL query
if ( isset( $_REQUEST['email_buyer_id'] ) && is_numeric( $_REQUEST['email_buyer_id'] ) ) {
	_wpsc_doing_it_wrong( 'wpsc_purchlog_resend_email', __( 'Do not trigger resend purchase log email action via email_buyer_id URL query. Instead use the Purchase Log Action Links API.', 'wp-e-commerce' ), '3.9.0' );
}

/**
 * Clear Purchase Log Download Locks
 *
 * @param   string   $log_id  Required. Purchase log ID (empty string is deprecated).
 * @return  boolean
 */
function wpsc_purchlog_clear_download_items( $log_id = '' ) {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	global $wpdb;

	// Deprecate empty purchase log ID parameter.
	if ( $log_id == '' ) {
		_wpsc_doing_it_wrong( 'wpsc_purchlog_clear_download_items', __( '$log_id parameter requires a numeric purchase log ID.', 'wp-e-commerce' ), '3.9.0' );
		return false;
	}

	$log_id = absint( $log_id );

	if ( $log_id > 0 ) {

		$downloadable_items = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . WPSC_TABLE_DOWNLOAD_STATUS . "` WHERE `purchid` = %d", $log_id ), ARRAY_A );

		$wpdb->update( WPSC_TABLE_DOWNLOAD_STATUS, array( 'ip_number' => '' ), array( 'purchid' => $log_id ), '%s', '%d' );

		$email_form_field = $wpdb->get_var( "SELECT `id` FROM `" . WPSC_TABLE_CHECKOUT_FORMS . "` WHERE `type` IN ('email') AND `active` = '1' ORDER BY `checkout_order` ASC LIMIT 1" );
		$email_address = $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `" . WPSC_TABLE_SUBMITTED_FORM_DATA . "` WHERE `log_id` = %d AND `form_id` = '{$email_form_field}' LIMIT 1", $log_id ) );

		foreach ( $downloadable_items as $downloadable_item ) {
			$download_links .= add_query_arg( 'downloadid', $downloadable_item['uniqueid'], home_url() )  . "\n";
		}

		wp_mail( $email_address, __( 'The administrator has unlocked your file', 'wp-e-commerce' ), str_replace( "[download_links]", $download_links, __( 'Dear Customer, We are pleased to advise you that your order has been updated and your downloads are now active. Please download your purchase using the links provided below. [download_links] Thank you for your order.', 'wp-e-commerce' ) ), "From: " . get_option( 'return_email' )  );

		return true;

	}

	return false;

}

// Deprecate clearing purchase log download locks via URL query
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'clear_locks') ) {
	_wpsc_doing_it_wrong( 'wpsc_purchlog_clear_download_items', __( 'Do not trigger clear purchase log download locks action via wpsc_admin_action = clear_locks URL query. Instead use the Purchase Log Action Links API.', 'wp-e-commerce' ), '3.9.0' );
}

//bulk actions for purchase log
function wpsc_purchlog_bulk_modify() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	if ( $_POST['purchlog_multiple_status_change'] != -1 ) {
		if ( is_numeric( $_POST['purchlog_multiple_status_change'] ) && $_POST['purchlog_multiple_status_change'] != 'delete' ) {
			foreach ( (array)$_POST['purchlogids'] as $purchlogid ) {
				wpsc_purchlog_edit_status( $purchlogid, $_POST['purchlog_multiple_status_change'] );
				$updated++;
			}
		} elseif ( $_POST['purchlog_multiple_status_change'] == 'delete' ) {
			foreach ( (array)$_POST['purchlogids'] as $purchlogid ) {

				$log = new WPSC_Purchase_Log( $purchlogid );
				$deleted_log = $log->delete();
				if ( $deleted_log ) {
					$deleted++;
				}

			}
		}
	}
	$sendback = wp_get_referer();
	if ( isset( $updated ) ) {
		$sendback = add_query_arg( 'updated', $updated, $sendback );
	}
	if ( isset( $deleted ) ) {
		$sendback = add_query_arg( 'deleted', $deleted, $sendback );
	}
	if ( isset( $_POST['view_purchlogs_by'] ) ) {
		$sendback = add_query_arg( 'view_purchlogs_by', $_POST['view_purchlogs_by'], $sendback );
	}
	if ( isset( $_POST['view_purchlogs_by_status'] ) ) {
		$sendback = add_query_arg( 'view_purchlogs_by_status', $_POST['view_purchlogs_by_status'], $sendback );
	}
	wp_redirect( esc_url_raw( $sendback ) );
	exit();
}

if ( isset( $_REQUEST['wpsc_admin_action2'] ) && ($_REQUEST['wpsc_admin_action2'] == 'purchlog_bulk_modify') ) {
	add_action( 'admin_init', 'wpsc_purchlog_bulk_modify' );
}

/**
 * Update Purchase Log Notes
 *
 * @param  int     $purchlog_id     Purchase log ID.
 * @param  string  $purchlog_notes  Notes.
 */
function wpsc_purchlogs_update_notes( $purchlog_id = 0, $purchlog_notes = '' ) {
	if ( isset( $_POST['wpsc_purchlogs_update_notes_nonce'] ) && wp_verify_nonce( $_POST['wpsc_purchlogs_update_notes_nonce'], 'wpsc_purchlogs_update_notes' ) ) {
		if ( 0 == $purchlog_id && isset( $_POST['purchlog_id'] ) && '' == $purchlog_notes ) {
			$purchlog_id = absint( $_POST['purchlog_id'] );
			$purchlog_notes = stripslashes( $_POST['purchlog_notes'] );
		}

		if ( $purchlog_id > 0 ) {
			$purchase_log = new WPSC_Purchase_Log( $purchlog_id );
			$purchase_log->set( 'notes', $purchlog_notes );
			$purchase_log->save();
		}
	}
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && $_REQUEST['wpsc_admin_action'] == 'purchlogs_update_notes' ) {
	add_action( 'admin_init', 'wpsc_purchlogs_update_notes' );
}

/**
 * Delete a purchase log
 *
 * @deprecated  Use WPSC_Purchase_Log->delete() instead.
 *
 * @param   int|string  $purchlog_id  Required. Purchase log ID (empty string is deprecated).
 * @return  boolean                   Deleted successfully.
 */
function wpsc_delete_purchlog( $purchlog_id = '' ) {

	global $wpdb;

	// Deprecate empty purchase log ID parameter.
	if ( $purchlog_id == '' ) {
		_wpsc_doing_it_wrong( 'wpsc_delete_purchlog', __( '$purchlog_id parameter requires a numeric purchase log ID.', 'wp-e-commerce' ), '3.9.0' );
		return false;
	}

	$log = new WPSC_Purchase_Log( $purchlog_id );

	return $log->delete();

}

// Deprecate deleting purchase log via URL query
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'delete_purchlog' ) ) {
	_wpsc_doing_it_wrong( 'wpsc_delete_purchlog', __( 'Do not trigger delete purchase log action via wpsc_admin_action = delete_purchlog URL query. Instead use the Purchase Log Action Links API.', 'wp-e-commerce' ), '3.9.0' );
}

function _wpsc_action_flush_rewrite_rules() {
	flush_rewrite_rules( false );
}

function wpsc_update_option_product_category_hierarchical_url() {
	_wpsc_action_flush_rewrite_rules();
}

add_action( 'update_option_product_category_hierarchical_url', 'wpsc_update_option_product_category_hierarchical_url' );

function _wpsc_action_sanitize_option_grid_number_per_row( $value, $option ) {
	$value = (int) $value;
	if ( $value === 0 ) {
		add_settings_error( $option, 'invalid_grid_number_per_row', __( 'You just set the number of item per row for the grid view to 0. This means the column width will fall back to using whatever CSS you have for it. This could break your theme layout, so please make sure you have adjusted your theme\'s CSS accordingly.', 'wp-e-commerce' ) );
	}

	return $value;
}
add_filter( 'sanitize_option_grid_number_per_row', '_wpsc_action_sanitize_option_grid_number_per_row', 10, 2 );

/**
 * Automatically enable "Anyone can register" if registration before checkout is required.
 *
 * @since  3.8.9
 * @access private
 * @param  mixed $old_value Old value
 * @param  mixed $new_value New value
 */
function _wpsc_action_update_option_require_register( $old_value, $new_value ) {
	if ( $new_value == 1 && ! get_option( 'users_can_register' ) ) {
		update_option( 'users_can_register', 1 );
		$message = __( 'You wanted to require your customers to log in before checking out. However, the WordPress setting <a href="%s">"Anyone can register"</a> was disabled. WP eCommerce has enabled that setting for you automatically.', 'wp-e-commerce' );
		$message = sprintf( $message, admin_url( 'options-general.php' ) );
		add_settings_error( 'require_register', 'users_can_register_turned_on', $message, 'updated' );
	}
}
add_action( 'update_option_require_register', '_wpsc_action_update_option_require_register', 10, 2 );

/**
 * Automatically turn off "require registration before checkout" if "Anyone can register" is disabled.
 *
 * @since  3.8.9
 * @access private
 * @param  mixed $old_value Old value
 * @param  mixed $new_value New value
 */
function _wpsc_action_update_option_users_can_register( $old_value, $new_value ) {
	if ( ! $new_value && get_option( 'require_register' ) ) {
		update_option( 'require_register', 0 );
		$message = __( 'You just disabled the "Anyone can register" setting. As a result, the <a href="%s">"Require registration before checking out"</a> setting has been disabled.', 'wp-e-commerce' );
		$message = sprintf( $message, admin_url( 'options-general.php?page=wpsc-settings&tab=checkout' ) );
		add_settings_error( 'users_can_register', 'require_register_turned_off', $message, 'updated' );
	}
}
add_action( 'update_option_users_can_register', '_wpsc_action_update_option_users_can_register', 10, 2 );

/**
 * wpsc_update_page_urls gets the permalinks for products pages and stores them in the options for quick reference
 * @public
 *
 * @since 3.6
 * @param $auto (Boolean) true if coming from WordPress Permalink Page, false otherwise
 * @return nothing
 */
function wpsc_update_page_urls( $auto = false ) {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	wpsc_update_permalink_slugs();
	wpsc_core_load_page_titles();
	wpsc_register_post_types();

	if ( ! $auto ) {
		$sendback = wp_get_referer();
		if ( isset( $updated ) )
			$sendback = add_query_arg( 'updated', $updated, $sendback );

		if ( isset( $_SESSION['wpsc_settings_curr_page'] ) )
			$sendback = add_query_arg( 'tab', $_SESSION['wpsc_settings_curr_page'], $sendback );

		wp_redirect( esc_url_raw( $sendback ) );
		exit();
	}
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'update_page_urls') )
	add_action( 'admin_init', 'wpsc_update_page_urls' );

//change the regions tax settings
function wpsc_change_region_tax() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	global $wpdb;
	if ( is_array( $_POST['region_tax'] ) ) {
		foreach ( $_POST['region_tax'] as $region_id => $tax ) {
			if ( is_numeric( $region_id ) && is_numeric( $tax ) ) {
				$previous_tax = $wpdb->get_var( $wpdb->prepare( "SELECT `tax` FROM `" . WPSC_TABLE_REGION_TAX . "` WHERE `id` = %d LIMIT 1", $region_id ) );
				if ( $tax != $previous_tax ) {
					$wpdb->update(
						WPSC_TABLE_REGION_TAX,
						array(
						    'tax' => $tax
						),
						array(
						    'id' => $region_id
						),
						'%s',
						'%d'
					    );
					$changes_made = true;
				}
			}
		}
		$sendback = wp_get_referer();
		wp_redirect( $sendback );
	}
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'change_region_tax') )
	add_action( 'admin_init', 'wpsc_change_region_tax' );

function wpsc_product_files_existing() {
	//List all product_files, with checkboxes

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	$product_id = absint( $_GET["product_id"] );
	$file_list = wpsc_uploaded_files();

	$args = array(
		'post_type' => 'wpsc-product-file',
		'post_parent' => $product_id,
		'numberposts' => -1,
		'post_status' => 'all'
	);
	$attached_files = (array)get_posts( $args );

	$attached_files_by_file = array();
	foreach ( $attached_files as $key => $attached_file ) {
		$attached_files_by_file[$attached_file->post_title] = & $attached_files[$key];
	}

	$output = "<span class='admin_product_notes select_product_note '>" . esc_html__( 'Choose a downloadable file for this product:', 'wp-e-commerce' ) . "</span><br>";
	$output .= "<form method='post' class='product_upload'>";
	$output .= '<div class="ui-widget-content multiple-select select_product_file" style="width:100%">';
	$num = 0;
	foreach ( (array)$file_list as $file ) {
		$num++;
		$checked_curr_file = "";
		if ( isset( $attached_files_by_file[$file['display_filename']] ) ) {
			$checked_curr_file = "checked='checked'";
		}

		$output .= "<p " . ((($num % 2) > 0) ? '' : "class='alt'") . " id='select_product_file_row_$num'>\n";
		$output .= "  <input type='checkbox' name='select_product_file[]' value='" . $file['real_filename'] . "' id='select_product_file_$num' " . $checked_curr_file . " />\n";
		$output .= "  <label for='select_product_file_$num'>" . $file['display_filename'] . "</label>\n";
		$output .= "</p>\n";
	}

	$output .= "</div>";
	$output .= "<input type='hidden' id='hidden_id' value='$product_id' />";
	$output .= "<input data-nonce='" . _wpsc_create_ajax_nonce( 'upload_product_file' ) . "' type='submit' name='save' name='product_files_submit' class='button-primary prdfil' value='" . esc_html__( 'Save Product Files', 'wp-e-commerce' ) . "' />";
	$output .= "</form>";
	$output .= "<div class='" . ((is_numeric( $product_id )) ? "edit_" : "") . "select_product_handle'><div></div></div>";
	$output .= "<script type='text/javascript'>\n\r";
	$output .= "var select_min_height = " . (25 * 3) . ";\n\r";
	$output .= "var select_max_height = " . (25 * ($num + 1)) . ";\n\r";
	$output .= "</script>";


	echo $output;
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ($_REQUEST['wpsc_admin_action'] == 'product_files_existing') )
	add_action( 'admin_init', 'wpsc_product_files_existing' );

function wpsc_update_variations() {
	$product_id = absint( $_POST["product_id"] );
	$product_type_object = get_post_type_object('wpsc-product');
	if (!current_user_can($product_type_object->cap->edit_post, $product_id))
		return;

	//Setup postdata
	$post_data = array();
	$post_data['edit_var_val'] = isset( $_POST['edit_var_val'] ) ? $_POST["edit_var_val"] : '';

	//Add or delete variations
	wpsc_edit_product_variations( $product_id, $post_data );
}

if ( isset($_POST["edit_var_val"]) )
	add_action( 'admin_init', 'wpsc_update_variations', 50 );

function wpsc_delete_variation_set() {
	check_admin_referer( 'delete-variation' );

	if ( is_numeric( $_GET['deleteid'] ) ) {
		$variation_id = absint( $_GET['deleteid'] );

		$variation_set = get_term( $variation_id, 'wpsc-variation', ARRAY_A );


		$variations = get_terms( 'wpsc-variation', array(
					'hide_empty' => 0,
					'parent' => $variation_id
				) );

		foreach ( (array)$variations as $variation ) {
			$return_value = wp_delete_term( $variation->term_id, 'wpsc-variation' );
		}

		if ( !empty( $variation_set ) ) {
			$return_value = wp_delete_term( $variation_set['term_id'], 'wpsc-variation' );
		}
		$deleted = 1;
	}

	$sendback = wp_get_referer();
	if ( isset( $deleted ) ) {
		$sendback = add_query_arg( 'deleted', $deleted, $sendback );
	}
	$sendback = remove_query_arg( array(
				'deleteid',
				'variation_id'
					), $sendback );

	wp_redirect( esc_url_raw( $sendback ) );
	exit();
}

if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( 'wpsc-delete-variation-set' == $_REQUEST['wpsc_admin_action'] ) )
	add_action( 'admin_init', 'wpsc_delete_variation_set' );

function wpsc_backup_theme() {

	if ( ! wpsc_is_store_admin() ) {
		return;
	}

	$wp_theme_path = get_stylesheet_directory();
	wpsc_recursive_copy( $wp_theme_path, WPSC_THEME_BACKUP_DIR );
	$_SESSION['wpsc_themes_backup'] = true;
	$sendback = wp_get_referer();
	wp_redirect( $sendback );

	exit();
}
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( $_REQUEST['wpsc_admin_action'] == 'backup_themes' ) )
	add_action( 'admin_init', 'wpsc_backup_theme' );

/**
 * Delete a coupon
 *
 * @since 3.8
 */
function wpsc_delete_coupon(){

	global $wpdb;

	check_admin_referer( 'delete-coupon' );

	$deleted = 0;

	if ( isset( $_GET['delete_id'] ) ) {
		$coupon = new WPSC_Coupon( $_GET['delete_id'] );
		$coupon->delete();
		$deleted = 1;
	}

	$sendback = wp_get_referer();

	if ( $deleted ) {
		$sendback = add_query_arg( 'deleted', $deleted, $sendback );
	}

	$sendback = remove_query_arg( array( 'deleteid', 'wpsc_admin_action' ), $sendback );
	wp_redirect( esc_url_raw( $sendback ) );
	exit();

}

// Delete Coupon
if ( isset( $_REQUEST['wpsc_admin_action'] ) && ( 'wpsc-delete-coupon' == $_REQUEST['wpsc_admin_action'] ) ) {
	add_action( 'admin_init', 'wpsc_delete_coupon' );
}

function _wpsc_action_update_option_base_country( $old_value, $new_value ) {
	global $wpdb;
	$region_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(`regions`.`id`) FROM `" . WPSC_TABLE_REGION_TAX . "` AS `regions` INNER JOIN `" . WPSC_TABLE_CURRENCY_LIST . "` AS `country` ON `country`.`id` = `regions`.`country_id` WHERE `country`.`isocode` IN('%s')",  $new_value ) );
	if ( ! $region_count )
		update_option( 'base_region', '' );
}
add_action( 'update_option_base_country', '_wpsc_action_update_option_base_country', 10, 2 );
