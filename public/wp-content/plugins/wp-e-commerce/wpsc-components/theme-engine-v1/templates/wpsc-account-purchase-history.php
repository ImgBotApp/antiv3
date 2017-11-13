<?php
/**
 * The Account > Purchase History template.
 *
 * Displays the user's order history.
 *
 * @package WPSC
 * @since WPSC 3.8.10
 */
global $col_count; ?>

<?php if ( wpsc_has_purchases() ) : ?>

	<table class="logdisplay">

	<?php if ( wpsc_has_purchases_this_month() ) : ?>

			<tr class="toprow">
				<th class="status"><?php _e( 'Status', 'wp-e-commerce' ); ?></th>
				<th class="date"><?php _e( 'Date', 'wp-e-commerce' ); ?></th>
				<th class="price"><?php _e( 'Price', 'wp-e-commerce' ); ?></th>

				<?php if ( get_option( 'payment_method' ) == 2 ) : ?>

					<th class="payment_method"><?php _e( 'Payment Method', 'wp-e-commerce' ); ?></th>

				<?php endif; ?>

			</tr>

			<?php wpsc_user_purchases(); ?>

	<?php else : ?>

			<tr>
				<td colspan="<?php echo $col_count; ?>">

					<?php _e( 'No transactions for this month.', 'wp-e-commerce' ); ?>

				</td>
			</tr>

	<?php endif; ?>

	</table>

<?php else : ?>

	<table>
		<tr>
			<td><?php _e( 'There have not been any purchases yet.', 'wp-e-commerce' ); ?></td>
		</tr>
	</table>

<?php endif; ?>