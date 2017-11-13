<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "soft" decline, in English.
 *
 * Soft declines indicate the payment method may be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/soft-decline-email-en.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  4.0
 */
?>

Valued customer,

Thank you very much for your order at <?php echo get_option( 'blogname' ); ?>.

Amazon Payments was not able to process your payment.

Please go to <?php echo esc_url( $url ); ?> and update the payment information for your order.
Afterwards we will automatically request payment again from Amazon Payments and you will receive a confirmation email.

Kind regards,

<?php echo get_option( 'blogname' ); ?>