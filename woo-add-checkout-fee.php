<?php
/*
Plugin Name: Woo Add Checkout Fee
Plugin URI: https://github.com/dataforge/woo-add-checkout-fee
Description: Adds an electronic payment fee to WooCommerce checkout
Version: 1.10
Author: Dataforge
GitHub Plugin URI: https://github.com/dataforge/woo-add-checkout-fee
License: GPL2
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'woo_add_checkout_fee_admin_menu' );
function woo_add_checkout_fee_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Woo Add Checkout Fee',
        'Woo Add Checkout Fee',
        'manage_woocommerce',
        'woo-add-checkout-fee',
        'woo_add_checkout_fee_settings_page'
    );
}

function woo_add_checkout_fee_settings_page() {
    $update_msg = '';

    // Handle "Check for Plugin Updates" button
    if (isset($_POST['woo_acf_check_update']) && check_admin_referer('woo_acf_settings_nonce', 'woo_acf_settings_nonce')) {
        // Simulate the cron event for plugin update check
        do_action('wp_update_plugins');
        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache(true);
        }
        // Remove the update_plugins transient to force a check
        delete_site_transient('update_plugins');
        // Call the update check directly as well
        if (function_exists('wp_update_plugins')) {
            wp_update_plugins();
        }
        // Get update info
        $plugin_file = plugin_basename(__FILE__);
        $update_plugins = get_site_transient('update_plugins');
        if (isset($update_plugins->response) && isset($update_plugins->response[$plugin_file])) {
            $new_version = $update_plugins->response[$plugin_file]->new_version;
            $update_msg = '<div class="updated"><p>Update available: version ' . esc_html($new_version) . '.</p></div>';
        } else {
            $update_msg = '<div class="updated"><p>No update available for this plugin.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Woo Add Checkout Fee</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'woo_add_checkout_fee_settings_group' );
            do_settings_sections( 'woo-add-checkout-fee' );
            submit_button();
            ?>
        </form>
        <?php
        if ( ! empty( $update_msg ) ) {
            echo $update_msg;
        }
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('woo_acf_settings_nonce', 'woo_acf_settings_nonce'); ?>
            <input type="hidden" name="woo_acf_check_update" value="1">
            <?php submit_button('Check for Plugin Updates', 'secondary'); ?>
        </form>
    </div>
    <?php
}

add_action( 'admin_init', 'woo_add_checkout_fee_settings_init' );
function woo_add_checkout_fee_settings_init() {
    register_setting( 'woo_add_checkout_fee_settings_group', 'woo_add_checkout_fee_enabled' );
    register_setting( 'woo_add_checkout_fee_settings_group', 'woo_add_checkout_fee_percentage' );
    register_setting( 'woo_add_checkout_fee_settings_group', 'woo_add_checkout_fee_fixed' );

    add_settings_section(
        'woo_add_checkout_fee_section',
        'Fee Settings',
        null,
        'woo-add-checkout-fee'
    );

    add_settings_field(
        'woo_add_checkout_fee_enabled',
        'Enable Fee',
        'woo_add_checkout_fee_enabled_field_render',
        'woo-add-checkout-fee',
        'woo_add_checkout_fee_section'
    );

    add_settings_field(
        'woo_add_checkout_fee_percentage',
        'Percentage Fee (%)',
        'woo_add_checkout_fee_percentage_field_render',
        'woo-add-checkout-fee',
        'woo_add_checkout_fee_section'
    );

    add_settings_field(
        'woo_add_checkout_fee_fixed',
        'Fixed Fee (cents)',
        'woo_add_checkout_fee_fixed_field_render',
        'woo-add-checkout-fee',
        'woo_add_checkout_fee_section'
    );
}

function woo_add_checkout_fee_enabled_field_render() {
    $value = get_option( 'woo_add_checkout_fee_enabled', '0' );
    ?>
    <input type="checkbox" name="woo_add_checkout_fee_enabled" value="1" <?php checked( $value, '1' ); ?> /> Enable the checkout fee
    <?php
}

function woo_add_checkout_fee_percentage_field_render() {
    $value = get_option( 'woo_add_checkout_fee_percentage', '2.9' );
    ?>
    <input type="number" step="0.01" min="0" name="woo_add_checkout_fee_percentage" value="<?php echo esc_attr( $value ); ?>" /> %
    <?php
}

function woo_add_checkout_fee_fixed_field_render() {
    $value = get_option( 'woo_add_checkout_fee_fixed', '30' );
    ?>
    <input type="number" step="1" min="0" name="woo_add_checkout_fee_fixed" value="<?php echo esc_attr( $value ); ?>" /> cents
    <?php
}

add_action( 'woocommerce_cart_calculate_fees', 'woo_add_checkout_fee_surcharge' );
function woo_add_checkout_fee_surcharge() {
    global $woocommerce;

    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    $enabled = get_option( 'woo_add_checkout_fee_enabled', '0' );
    if ( $enabled !== '1' ) {
        return;
    }

    $percentage = floatval( get_option( 'woo_add_checkout_fee_percentage', '2.9' ) ) / 100;
    $fixed_fee_cents = floatval( get_option( 'woo_add_checkout_fee_fixed', '30' ) );
    $fixed_fee = $fixed_fee_cents / 100;

    $surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage + $fixed_fee;
    $woocommerce->cart->add_fee( 'Electronic Payment Fee', $surcharge, true, '' );
}
