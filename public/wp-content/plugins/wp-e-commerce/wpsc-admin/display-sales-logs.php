<?php
/**
 * WP eCommerce edit and view sales page functions
 *
 * These are the main WPSC sales page functions
 *
 * @package wp-e-commerce
 * @since 3.8.8
 */

class WPSC_Purchase_Log_Page {
	private $list_table;
	private $output;
	public $log_id = 0;

	public function __construct() {
		$controller = 'default';
		$controller_method = 'controller_default';

		// If individual purchase log, setup ID and action links.
		if ( isset( $_REQUEST['id'] ) && is_numeric( $_REQUEST['id'] ) ) {
			$this->log_id = (int) $_REQUEST['id'];
		}

		if ( isset( $_REQUEST['c'] ) && method_exists( $this, 'controller_' . $_REQUEST['c'] ) ) {
			$controller = $_REQUEST['c'];
			$controller_method = 'controller_' . $controller;
		} elseif ( isset( $_REQUEST['id'] ) && is_numeric( $_REQUEST['id'] ) ) {
			$controller = 'item_details';
			$controller_method = 'controller_item_details';
		}

		$this->$controller_method();
	}

	private function needs_update() {
		global $wpdb;

		if ( get_option( '_wpsc_purchlogs_3.8_updated' ) )
			return false;

		$c = $wpdb->get_var( "SELECT COUNT(*) FROM " . WPSC_TABLE_PURCHASE_LOGS . " WHERE plugin_version IN ('3.6', '3.7')" );
		if ( $c > 0 )
			return true;

		update_option( '_wpsc_purchlogs_3.8_updated', true );
		return false;
	}

	public function controller_upgrade_purchase_logs_3_7() {
		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_upgrade_purchase_logs_3_7' ) );
	}

	private function purchase_logs_fix_options( $id ) {
		?>
		<select name='<?php echo $id; ?>'>
			<option value='-1'><?php echo esc_html_x( 'Select an Option', 'Dropdown default when called in uniquename dropdown', 'wp-e-commerce' ); ?></option>
			<option value='billingfirstname'><?php esc_html_e( 'Billing First Name', 'wp-e-commerce' ); ?></option>
			<option value='billinglastname'><?php esc_html_e( 'Billing Last Name', 'wp-e-commerce' ); ?></option>
			<option value='billingaddress'><?php esc_html_e( 'Billing Address', 'wp-e-commerce' ); ?></option>
			<option value='billingcity'><?php esc_html_e( 'Billing City', 'wp-e-commerce' ); ?></option>
			<option value='billingstate'><?php esc_html_e( 'Billing State', 'wp-e-commerce' ); ?></option>
			<option value='billingcountry'><?php esc_html_e( 'Billing Country', 'wp-e-commerce' ); ?></option>
			<option value='billingemail'><?php esc_html_e( 'Billing Email', 'wp-e-commerce' ); ?></option>
			<option value='billingphone'><?php esc_html_e( 'Billing Phone', 'wp-e-commerce' ); ?></option>
			<option value='billingpostcode'><?php esc_html_e( 'Billing Post Code', 'wp-e-commerce' ); ?></option>
			<option value='shippingfirstname'><?php esc_html_e( 'Shipping First Name', 'wp-e-commerce' ); ?></option>
			<option value='shippinglastname'><?php esc_html_e( 'Shipping Last Name', 'wp-e-commerce' ); ?></option>
			<option value='shippingaddress'><?php esc_html_e( 'Shipping Address', 'wp-e-commerce' ); ?></option>
			<option value='shippingcity'><?php esc_html_e( 'Shipping City', 'wp-e-commerce' ); ?></option>
			<option value='shippingstate'><?php esc_html_e( 'Shipping State', 'wp-e-commerce' ); ?></option>
			<option value='shippingcountry'><?php esc_html_e( 'Shipping Country', 'wp-e-commerce' ); ?></option>
			<option value='shippingpostcode'><?php esc_html_e( 'Shipping Post Code', 'wp-e-commerce' ); ?></option>
		</select>
		<?php
	}

	public function display_upgrade_purchase_logs_3_7() {
		global $wpdb;
		$numChanged = 0;
		$numQueries = 0;
		$purchlog =  "SELECT DISTINCT id FROM `".WPSC_TABLE_PURCHASE_LOGS."` LIMIT 1";
		$id = $wpdb->get_var($purchlog);
		$usersql = "SELECT DISTINCT `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.value, `".WPSC_TABLE_CHECKOUT_FORMS."`.* FROM `".WPSC_TABLE_CHECKOUT_FORMS."` LEFT JOIN `".WPSC_TABLE_SUBMITTED_FORM_DATA."` ON `".WPSC_TABLE_CHECKOUT_FORMS."`.id = `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.`form_id` WHERE `".WPSC_TABLE_SUBMITTED_FORM_DATA."`.log_id=".$id." ORDER BY `".WPSC_TABLE_CHECKOUT_FORMS."`.`checkout_order`" ;
		$formfields = $wpdb->get_results($usersql);

		if(count($formfields) < 1){
			$usersql = "SELECT DISTINCT  `".WPSC_TABLE_CHECKOUT_FORMS."`.* FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `type` != 'heading'";
			$formfields = $wpdb->get_results($usersql);
		}

		if(isset($_POST)){
			foreach($_POST as $key=>$value){
				if($value != '-1'){
					$complete = $wpdb->update(
				 WPSC_TABLE_CHECKOUT_FORMS,
				 array(
				'unique_name' => $value
				 ),
				 array(
				'id' => $key
				  ),
				 '%s',
				 '%d'
				 );
				}
				$numChanged++;
				$numQueries ++;
			}

			$sql = "UPDATE `".WPSC_TABLE_CHECKOUT_FORMS."` SET `unique_name`='delivertoafriend' WHERE `name` = '2. Shipping details'";
			$wpdb->query($sql);

			add_option('wpsc_purchaselogs_fixed',true);
		}

		include( 'includes/purchase-logs-page/upgrade.php' );
	}

	public function display_upgrade_purchase_logs_3_8() {
		?>
			<div class="wrap">
				<h2><?php echo esc_html( __('Sales', 'wp-e-commerce') ); ?> </h2>
				<div class="updated">
					<p><?php printf( __( 'Your purchase logs have been updated! <a href="%s">Click here</a> to return.' , 'wp-e-commerce' ), esc_url( remove_query_arg( 'c' ) ) ); ?></p>
				</div>
			</div>
		<?php
	}

	public function controller_upgrade_purchase_logs_3_8() {
		if ( $this->needs_update() )
			wpsc_update_purchase_logs();

		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_upgrade_purchase_logs_3_8' ) );
	}

	function purchase_logs_pagination() {
		global $wpdb, $purchlogitem;
		?>
		<span class='tablenav'><span class='tablenav-pages'><span class='pagination-links'>
			<?php
			$href = "#";
			$disabled = "disabled";
			if ( $this->log_id > 1 ) {
				$href = $this->get_purchase_log_url( ( $this->log_id - 1 ) );
				$disabled = '';
			}
			?>
			<a href='<?php esc_url( $href ); ?>' class='prev-page <?php echo $disabled; ?>'>&lsaquo; <?php _e( 'Previous', 'wp-e-commerce' ); ?></a>
			<?php

			$max_purchase_id = wpsc_max_purchase_id();
			$href = "#";
			$disabled = "disabled";
			if ( $max_purchase_id > $this->log_id ) {
				$href = $this->get_purchase_log_url( ( $this->log_id + 1 ) );
				$disabled = '';
			}
			?>
			<a href='<?php esc_url( $href ); ?>' class='next-page <?php echo $disabled; ?>'><?php _e( 'Next', 'wp-e-commerce' ); ?> &rsaquo;</a>

		</span></span></span>
		<?php
	}

	function purchase_logs_checkout_fields(){
		global $purchlogitem;

		if ( ! empty( $purchlogitem->additional_fields ) ) {
		?>
			<div class="metabox-holder">
				<div id="custom_checkout_fields" class="postbox">
					<h3 class='hndle'><?php esc_html_e( 'Additional Checkout Fields' , 'wp-e-commerce' ); ?></h3>
					<div class='inside'>
						<?php
						foreach( (array) $purchlogitem->additional_fields as $value ) {
							$value['value'] = maybe_unserialize ( $value['value'] );
							if ( is_array( $value['value'] ) ) {
								?>
									<p><strong><?php echo $value['name']; ?> :</strong> <?php echo implode( stripslashes( $value['value'] ), ',' ); ?></p>
								<?php
							} else {
								$thevalue = esc_html( stripslashes( $value['value'] ));
								if ( empty( $thevalue ) ) {
									$thevalue = __( '<em>blank</em>', 'wp-e-commerce' );
								}
								?>
									<p><strong><?php echo $value['name']; ?> :</strong> <?php echo $thevalue; ?></p>
								<?php
							}
						}
						?>
					</div>
				</div>
			</div>
		<?php
		}
	}

	public function purchase_log_custom_fields(){
		if( wpsc_purchlogs_has_customfields() ){?>
			<div class='metabox-holder'>
				<div id='purchlogs_customfields' class='postbox'>
					<h3 class='hndle'><?php esc_html_e( 'Users Custom Fields' , 'wp-e-commerce' ); ?></h3>
					<div class='inside'>
						<?php $messages = wpsc_purchlogs_custommessages(); ?>
						<?php $files = wpsc_purchlogs_customfiles(); ?>
						<?php if(count($files) > 0){ ?>
							<h4><?php esc_html_e( 'Cart Items with Custom Files' , 'wp-e-commerce' ); ?>:</h4>
							<?php
							foreach($files as $file){
								echo $file;
							}
						}?>
						<?php if(count($messages) > 0){ ?>
							<h4><?php esc_html_e( 'Cart Items with Custom Messages' , 'wp-e-commerce' ); ?>:</h4>
							<?php
							foreach($messages as $message){
								echo esc_html( $message['title'] ) . ':<br />' . nl2br( esc_html( $message['message'] ) );
							}
						} ?>
					</div>
				</div>
			</div>
		<?php
		}
	}

	private function purchase_log_cart_items() {
		while( wpsc_have_purchaselog_details() ) : wpsc_the_purchaselog_item(); ?>
		<tr>
			<td><?php echo wpsc_purchaselog_details_name(); ?></td> <!-- NAME! -->
			<td><?php echo wpsc_purchaselog_details_SKU(); ?></td> <!-- SKU! -->
			<td><?php echo wpsc_purchaselog_details_quantity(); ?></td> <!-- QUANTITY! -->
			<td>
		 <?php
		echo wpsc_currency_display( wpsc_purchaselog_details_price() );
		do_action( 'wpsc_additional_sales_amount_info', wpsc_purchaselog_details_id() );
		 ?>
	 </td> <!-- PRICE! -->
			<td><?php echo wpsc_currency_display( wpsc_purchaselog_details_shipping() ); ?></td> <!-- SHIPPING! -->
			<?php if( wpec_display_product_tax() ): ?>
				<td><?php echo wpsc_currency_display( wpsc_purchaselog_details_tax() ); ?></td> <!-- TAX! -->
			<?php endif; ?>
			<!-- <td><?php echo wpsc_currency_display( wpsc_purchaselog_details_discount() ); ?></td> --> <!-- DISCOUNT! -->
			<td class="amount"><?php echo wpsc_currency_display( wpsc_purchaselog_details_total() ); ?></td> <!-- TOTAL! -->
		</tr>
		<?php
		do_action( 'wpsc_additional_sales_item_info', wpsc_purchaselog_details_id() );
		endwhile;
	}

	public function controller_item_details() {

		if ( ! isset( $_REQUEST['id'] ) || ( isset( $_REQUEST['id'] ) && ! is_numeric( $_REQUEST['id'] ) ) ) {
			wp_die( __( 'Invalid sales log ID', 'wp-e-commerce'  ) );
		}

		global $purchlogitem;

		// TODO: seriously get rid of all these badly coded purchaselogs.class.php functions in 4.0
		$purchlogitem = new wpsc_purchaselogs_items( $this->log_id );

		$columns = array(
			'title'    => __( 'Name', 'wp-e-commerce' ),
			'sku'      => __( 'SKU', 'wp-e-commerce' ),
			'quantity' => __( 'Quantity','wp-e-commerce' ),
			'price'    => __( 'Price', 'wp-e-commerce' ),
			'shipping' => __( 'Item Shipping', 'wp-e-commerce'),
		);

		if ( wpec_display_product_tax() ) {
			$columns['tax'] = __( 'Item Tax', 'wp-e-commerce' );
		}

		$columns['total'] = __( 'Item Total','wp-e-commerce' );

		register_column_headers( 'wpsc_purchase_log_item_details', $columns );

		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_purchase_log' ) );
		add_action( 'wpsc_purchlogitem_metabox_start', array( $this, 'purchase_log_custom_fields' ) );
	}

	public function controller_packing_slip() {

		if ( ! isset( $_REQUEST['id'] ) || ( isset( $_REQUEST['id'] ) && ! is_numeric( $_REQUEST['id'] ) ) ) {
			wp_die( __( 'Invalid sales log ID', 'wp-e-commerce'  ) );
		}

		global $purchlogitem;

		$purchlogitem = new wpsc_purchaselogs_items( $this->log_id );

		$columns = array(
			'title'    => __( 'Item Name', 'wp-e-commerce' ),
			'sku'      => __( 'SKU', 'wp-e-commerce' ),
			'quantity' => __( 'Quantity', 'wp-e-commerce' ),
			'price'    => __( 'Price', 'wp-e-commerce' ),
			'shipping' => __( 'Item Shipping','wp-e-commerce' ),
		);

		if ( wpec_display_product_tax() ) {
			$columns['tax'] = __( 'Item Tax', 'wp-e-commerce' );
		}

		$columns['total'] = __( 'Item Total','wp-e-commerce' );

		$cols = count( $columns ) - 2;

		register_column_headers( 'wpsc_purchase_log_item_details', $columns );

		if ( file_exists( get_stylesheet_directory() . '/wpsc-packing-slip.php' ) ) {
			$packing_slip_file = get_stylesheet_directory() . '/wpsc-packing-slip.php';
		} else {
			$packing_slip_file = 'includes/purchase-logs-page/packing-slip.php';
		}

		$packing_slip_file = apply_filters( 'wpsc_packing_packing_slip_path', $packing_slip_file );

		include( $packing_slip_file );

		exit;
	}

	public function controller_default() {
		//Create an instance of our package class...
		$this->list_table = new WPSC_Purchase_Log_List_Table();
		$this->process_bulk_action();
		$this->list_table->prepare_items();
		add_action( 'wpsc_display_purchase_logs_page', array( $this, 'display_list_table' ) );
	}

	public function display_purchase_log() {
		if ( wpec_display_product_tax() )
			$cols = 5;
		else
			$cols = 4;
		$receipt_sent = ! empty( $_GET['sent'] );
		$receipt_not_sent = isset( $_GET['sent'] ) && ! $_GET['sent'];
		include( 'includes/purchase-logs-page/item-details.php' );
	}

	public function download_csv() {
		$_REQUEST['rss_key'] = 'key';
		wpsc_purchase_log_csv();
	}

	public function process_bulk_action() {
		global $wpdb;
		$current_action = $this->list_table->current_action();

		do_action( 'wpsc_sales_log_process_bulk_action', $current_action );

		if ( ! $current_action || ( 'download_csv' != $current_action && empty( $_REQUEST['post'] ) ) ) {
			if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				wp_redirect( esc_url_raw( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) ) );
				exit;
			}

			unset( $_REQUEST['post'] );
			return;
		}

		if ( 'download_csv' == $current_action ) {
			$this->download_csv();
			exit;
		}

		$sendback = remove_query_arg( array(
			'_wpnonce',
			'_wp_http_referer',
			'action',
			'action2',
			'confirm',
			'post',
			'last_paged'
		) );

		if ( 'delete' == $current_action ) {

			// delete action
			if ( empty( $_REQUEST['confirm'] ) ) {
				$this->list_table->disable_search_box();
				$this->list_table->disable_bulk_actions();
				$this->list_table->disable_sortable();
				$this->list_table->disable_month_filter();
				$this->list_table->disable_views();
				$this->list_table->set_per_page(0);
				add_action( 'wpsc_purchase_logs_list_table_before', array( $this, 'action_list_table_before' ) );
				return;
			} else {
				if ( empty( $_REQUEST['post'] ) )
					return;

				$ids = array_map( 'intval', $_REQUEST['post'] );

				foreach ( $ids as $id ) {
					$log = new WPSC_Purchase_Log( $id );
					$log->delete();
				}

				$sendback = add_query_arg( array(
					'paged'   => $_REQUEST['last_paged'],
					'deleted' => count( $_REQUEST['post'] ),
				), $sendback );

			}
		}

		// change status actions
		if ( is_numeric( $current_action ) && ! empty( $_REQUEST['post'] ) ) {

			foreach ( $_REQUEST['post'] as $id )
				wpsc_purchlog_edit_status( $id, $current_action );

			$sendback = add_query_arg( array(
				'updated' => count( $_REQUEST['post'] ),
			), $sendback );
		}

		wp_redirect( esc_url_raw( $sendback ) );
		exit;
	}

	public function action_list_table_before() {
		include( 'includes/purchase-logs-page/bulk-delete-confirm.php' );
	}

	public function display_list_table() {
		if ( ! empty( $this->output ) ) {
			echo $this->output;
			return;
		}

		include( 'includes/purchase-logs-page/list-table.php' );
	}

	private function get_purchase_log_url( $id ) {
		$location = add_query_arg( array(
			'page' => 'wpsc-purchase-logs',
			'c'    => 'item_details',
			'id'   => $id,
		), admin_url( 'index.php' ) );

		return esc_url( $location );
	}

}
