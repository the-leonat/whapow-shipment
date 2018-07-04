<?php
  /**
   * Dropship Email
   *
   * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-new-order.php.
   *
   * HOWEVER, on occasion WooCommerce will need to update template files and you
   * (the theme developer) will need to copy the new files to your theme to
   * maintain compatibility. We try to do this as little as possible, but it does
   * happen. When this occurs the version of the template file will be bumped and
   * the readme will list any important changes.
   *
   * @see 	    https://docs.woocommerce.com/document/template-structure/
   * @author WooThemes
   * @package WooCommerce/Templates/Emails/HTML
   * @version 2.5.0
   */

  if ( ! defined( 'ABSPATH' ) ) {
   	exit;
  }


  do_action( 'woocommerce_email_header', $email_heading, $email );

  $text_align = is_rtl() ? 'right' : 'left';

  do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

  	<h2>
      <?php printf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ); ?>
      (<?php printf( '<time datetime="%s">%s</time>', $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ); ?>)
    </h2>

  <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
  	<thead>
  		<tr>
  			<th class="td" scope="col" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Product', 'woocommerce' ); ?></th>
  			<th class="td" scope="col" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
  		</tr>
  	</thead>
  	<tbody><?php

      $items = $order->get_items();

      foreach ( $items as $item_id => $item ) :
      	if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
      		$product = $item->get_product();
      		?>
      		<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
      			<td class="td" style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
              <?php

      				// Product name
      				echo apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false );

      				// allow other plugins to add additional product information here
      				do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );

      				wc_display_item_meta( $item );

      				// allow other plugins to add additional product information here
      				do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );

      			  ?>
            </td>
      			<td class="td" style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo apply_filters( 'woocommerce_email_order_item_quantity', $item->get_quantity(), $item ); ?></td>
      		</tr>
      		<?php
      	}

      endforeach; ?>
  	</tbody>
  </table>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<h2>an folgende Adresse senden</h2>
<p><?php echo $order->get_formatted_shipping_address(); ?></p>
<p><?php echo $order->get_billing_email(); ?></p>

<?php
   /**
    * @hooked WC_Emails::email_footer() Output the email footer
    */
  do_action( 'woocommerce_email_footer', $email );
