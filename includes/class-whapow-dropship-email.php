<?php

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * A custom Expedited Order WooCommerce Email class
 *
 * @since 0.1
 * @extends \WC_Email
 */
class Whapow_Dropship_Email extends WC_Email
{

    /**
     * Set email defaults
     *
     * @since 0.1
     */
    public function __construct()
    {

        // set ID, this simply needs to be a unique name
        $this->id = 'whapow_shipment_notification';
        $this->customer_email = true;


        // this is the title in WooCommerce Email settings
        $this->title = 'Whapow Shipping Notification';

        // this is the description in WooCommerce email settings
        $this->description = 'Notify Dropshipper when Order changed to Processing';

        // these are the default heading and subject lines that can be overridden using the settings
        $this->heading = 'New Order';
        $this->subject = 'New Order';

        // these define the locations of the templates that this email should use, we'll just use the new order template since this email is similar
        $this->template_html = '../../whapow-shipment/templates/dropship-email.php';
        $this->template_plain = '../../whapow-shipment/templates/dropship-email.php';

        //add_action( 'woocommerce_order_status_failed_to_processing_notification',  array( $this, 'trigger' ) );

        // this sets the recipient to the settings defined below in init_form_fields()
        $this->recipient = $this->get_option('recipient');

        // if none was entered, just use the WP admin email as a fallback
        if (!$this->recipient) {
            $this->recipient = get_option('admin_email');
        }

        // Trigger on new paid orders
		add_action( 'woocommerce_order_status_completed_notification', array( $this, 'trigger' ), 10, 2 );

        // Call parent constructor to load any other defaults not explicity defined here
        parent::__construct();

    }

    /**
     * Determine if the email should actually be sent and setup email merge variables
     *
     * @since 0.1
     * @param int $order_id
     */
    public function trigger($order_id, $order = false)
    {

        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->order                         = $order;
        }
        
        $this->object = $this->order;

        $data = $this->order->get_data();

        if (count($data["meta_data"] > 0)) {
            $meta = $data["meta_data"];
            foreach ($meta as $meta_object) {
                $meta_object = $meta_object->get_data();
                if ($meta_object["key"] === "whapow_shipment_provider_mail") {
                    $this->recipient = $meta_object["value"] . ", shop@whapow.de";
                    break;
                }
            }
        }

        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        //error_log($this->get_attachments());

        // woohoo, send the email!
        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html()
    {
        return wc_get_template_html($this->template_html, array(
            'order' => $this->order,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text' => false,
            'email' => $this,
        ));
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain()
    {
        return wc_get_template_html($this->template_plain, array(
            'order' => $this->order,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text' => false,
            'email' => $this,
        ));
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable this email notification',
                'default' => 'yes',
            ),
            'recipient' => array(
                'title' => 'Recipient(s)',
                'type' => 'text',
                'description' => sprintf('Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', esc_attr(get_option('admin_email'))),
                'placeholder' => '',
                'default' => '',
            ),
            'subject' => array(
                'title' => 'Subject',
                'type' => 'text',
                'description' => sprintf('This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject),
                'placeholder' => '',
                'default' => '',
            ),
            'heading' => array(
                'title' => 'Email Heading',
                'type' => 'text',
                'description' => sprintf(__('This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.'), $this->heading),
                'placeholder' => '',
                'default' => '',
            ),
            'email_type' => array(
				'title'         => __( 'Email type', 'woocommerce' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options(),
				'desc_tip'      => true,
			),
        );
    }
} // end \WC_Expedited_Order_Email class
