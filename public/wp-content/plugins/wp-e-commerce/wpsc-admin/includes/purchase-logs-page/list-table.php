<div class="wrap">
	<div id="icon-users" class="icon32"><br/></div>
	<h2>
		<?php esc_html_e( 'Sales Log', 'wp-e-commerce' ); ?>

		<?php
			if ( isset($_REQUEST['s']) && $_REQUEST['s'] )
				printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;', 'wp-e-commerce' ) . '</span>', esc_html( stripslashes( $_REQUEST['s'] ) ) ); ?>
	</h2>

	<?php if ( ! empty( $_REQUEST['updated'] ) || ! empty( $_REQUEST['deleted'] ) ): ?>
		<div id="message" class="updated">
			<p>
				<?php
					if ( ! empty( $_REQUEST['updated'] ) )
						printf( _n( '%s item updated.', '%s items updated.', $_REQUEST['updated'], 'wp-e-commerce' ), number_format_i18n( $_REQUEST['updated'] ) );
				?>
				<?php
					if ( ! empty( $_REQUEST['deleted'] ) )
						printf( _n( '%s item deleted.', '%s items deleted.', $_REQUEST['deleted'], 'wp-e-commerce' ), number_format_i18n( $_REQUEST['deleted'] ) );
				?>
			</p>
		</div>
	<?php endif ?>

	<?php if( get_option( 'wpsc_purchaselogs_fixed' ) == false || ( wpsc_check_uniquenames() ) ): ?>
        <div class='error' style='padding:8px;line-spacing:8px;'><span ><?php printf( __( 'When upgrading the WP eCommerce Plugin from 3.6.* to 3.7, it is required that you associate your checkout form fields with the new Purchase Logs system. To do so please <a href="%s">click here</a>', 'wp-e-commerce' ), esc_url( add_query_arg( 'c', 'upgrade_purchase_logs_3_7' ) ) ); ?></span></div>
   <?php  endif; ?>

	<?php if ( $this->needs_update() ): ?>
		<div class='error' style='padding:8px;line-spacing:8px;'><span ><?php printf( __( 'It has been detected that some of your purchase logs were not updated properly when you upgrade to WP eCommerce %s. Please <a href="%s">click here</a> to fix this problem.', 'wp-e-commerce' ), WPSC_VERSION, esc_url( add_query_arg( 'c', 'upgrade_purchase_logs_3_8' ) ) ); ?></span></div>
	<?php endif; ?>

	<form id="purchase-logs-search" method-"get" action="">
		<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
		<?php $this->list_table->search_box( __( 'Search Sales Logs', 'wp-e-commerce' ), 'post' ); ?>
		<?php if ( ! empty( $_REQUEST['status'] ) ): ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $_REQUEST['status'] ); ?>" />
		<?php endif ?>
	</form>


	<?php
		if ( $this->list_table->is_views_enabled() )
			$this->list_table->views();
	?>
	<br class="clear" />

	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="purchase-logs-filter" method="get" action="">
		<?php do_action( 'wpsc_purchase_logs_list_table_before' ); ?>
		<!-- For plugins, we also need to ensure that the form posts back to our current page -->
		<!-- Now we can render the completed list table -->

		<?php $this->list_table->display() ?>
		<input type="hidden" name="page" value="wpsc-purchase-logs" />

		<?php if ( ! $this->list_table->is_pagination_enabled() && $this->list_table->get_pagenum() ):?>
			<input type="hidden" name="last_paged" value="<?php echo esc_attr( $this->list_table->get_pagenum() ); ?>" />
		<?php endif ?>

		<?php if ( ! $this->list_table->is_sortable() && isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ): ?>
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $_REQUEST['orderby'] ); ?>" />
			<input type="hidden" name="order" value="<?php echo esc_attr( $_REQUEST['order'] ); ?>" />
		<?php endif; ?>

		<?php if ( isset( $_REQUEST['s'] ) ): ?>
			<input type="hidden" name="s" value="<?php echo esc_attr( $_REQUEST['s'] ); ?>" />
		<?php endif; ?>

		<?php if ( ! empty( $_REQUEST['status'] ) ): ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $_REQUEST['status'] ); ?>" />
		<?php endif ?>
		<?php do_action( 'wpsc_purchase_logs_list_table_after' ); ?>
	</form>

	<p>
		<a class='admin_download' href='<?php echo esc_url( add_query_arg( 'action', 'download_csv' ) ); ?>' >
			<img class='wpsc_pushdown_img' src='<?php echo WPSC_CORE_IMAGES_URL; ?>/download.gif' alt='' title='' />
			<span><?php _e( 'Download CSV', 'wp-e-commerce' ); ?></span>
		</a>
	</p>
</div>