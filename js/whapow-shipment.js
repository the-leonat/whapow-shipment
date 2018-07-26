jQuery(document).ready(function () {
    jQuery("body").on('checkout_error', function () {
        jQuery('body').trigger('update_checkout');
    });
});
