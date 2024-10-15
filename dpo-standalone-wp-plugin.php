<?php /** @noinspection PhpMissingStrictTypesDeclarationInspection */

/**
 *
 * Plugin Name: DPO Group for Wordpress Standalone
 * Plugin URI: https://github.com/DPO-Group/DPO_Wordpress_Standalone
 * Description: Accept payments for WooCommerce using DPO Group's online payments service
 * Version: 1.1.0
 * Tested: 6.6.2
 * Author: DPO Group
 * Author URI: https://www.dpogroup.com/africa/
 * Developer: App Inlet (Pty) Ltd
 * Developer URI: https://www.appinlet.com/
 *
 * Copyright: © 2024 DPO Group
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
use Dpo\Common\Dpo;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
// Exit if accessed directly.
if ( ! defined('ABSPATH')) {
    exit();
}

const LOCATION = 'Location: ';

require_once 'classes/DPOStandalone.php';
require_once 'vendor/autoload.php';

add_action('plugins_loaded', 'dpo_standalone_init');
add_action('admin_init', 'register_dpo_standalone_plugin_settings');
add_action('admin_post_dpo_standalone_wp_payment', 'dpo_standalone_wp_payment');
add_action('admin_post_nopriv_dpo_standalone_wp_payment', 'dpo_standalone_wp_payment');
add_action('admin_post_dpo_standalone_wp_payment_success', 'dpo_standalone_wp_payment_success');
add_action('admin_post_nopriv_dpo_standalone_wp_payment_success', 'dpo_standalone_wp_payment_success');
add_action('admin_post_dpo_standalone_wp_payment_failure', 'dpo_standalone_wp_payment_failure');
add_action('admin_post_nopriv_dpo_standalone_wp_payment_failure', 'dpo_standalone_wp_payment_failure');

/**
 * @return void
 */
function dpo_standalone_wp_payment(): void
{
    $email  = filter_var($_POST['dpo_standalone_payment_email'], FILTER_SANITIZE_EMAIL);
    $amount = filter_var(
        $_POST['dpo_standalone_payment_amount'],
        FILTER_SANITIZE_NUMBER_FLOAT,
        FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND
    );

    if ($email == '') {
        header('Location:' . $_SERVER['HTTP_REFERER']);

        return;
    }

    /** Validate reCAPTCHA **/
    validate_form();

    $dpoStandaloneStandalone = new DPOStandalone();
    $dpoCommon = new Dpo();

    try {
        $reference = 'DPO_' . random_int(100000, 999999) . '_' . date('Y-m-d');
    } catch (Exception $exception) {
    }

    $eparts    = explode('@', $email);

    $post_id     = wp_insert_post(
        [
            'post_type'   => 'dpo_standalone_order',
            'post_status' => 'dposa_pending',
            'post_title'  => "DPOSA_order_$reference",
        ]
    );
    $success_url = admin_url() . "admin-post.php?action=dpo_standalone_wp_payment_success&post_id=$post_id";
    $failure_url = admin_url() . "admin-post.php?action=dpo_standalone_wp_payment_failure&post_id=$post_id";

    $dataArray = [
        'orderItems'        => $dpoStandaloneStandalone->getOrderItems(),
        'companyToken'      => $dpoStandaloneStandalone->getCompanyToken(),
        'serviceType'       => $dpoStandaloneStandalone->getServiceType(),
        'paymentAmount'     => $amount,
        'paymentCurrency'   => 'KES',
        'companyRef'        => $reference,
        'customerDialCode'  => $dpoStandaloneStandalone->getDialCode(),
        'customerZip'       => $dpoStandaloneStandalone->getCustomerZip(),
        'customerCountry'   => 'KE',
        'customerFirstName' => $eparts[0],
        'customerLastName'  => $eparts[1],
        'customerAddress'   => $dpoStandaloneStandalone->getAddress(),
        'customerCity'      => $dpoStandaloneStandalone->getCustomerCity(),
        'customerPhone'     => $dpoStandaloneStandalone->getCustomerPhone(),
        'customerEmail'     => $email,
        'redirectURL'       => $success_url,
        'backURL'           => $failure_url,
    ];

    $token = '';
    try {
        $token = $dpoCommon->createToken($dataArray);
    } catch (Exception $exception) {
    }

    $data1                 = [];
    $data1['companyToken'] = $dataArray['companyToken'];
    $transToken            = $data1['transToken'] = $token['transToken'];
    $transactionId         = $token['transRef'];
    $dpoStandalonePay                = $dpoCommon->getPayUrl() . '?ID=' . $transToken;

    update_post_meta($post_id, 'dposa_transaction_token', $transToken);
    update_post_meta($post_id, 'dposa_transaction_id', $transactionId);
    update_post_meta($post_id, 'dposa_order_reference', $reference);
    update_post_meta($post_id, 'dposa_order_data', $dataArray);

    // Verify the token
    $result = $dpoCommon->verifyToken($data1);
    if ($result != '') {
        try {
            $result = new SimpleXMLElement($result);
        } catch (Exception $exception) {
        }
    }
    if ( ! is_string($result) && $result->Result->__toString() == '900') {
        // Redirect to payment portal
        echo <<<HTML
<p>Kindly wait while you're redirected to the DPO Group ...</p>
<form action="$dpoStandalonePay" method="post" name="dpo_redirect">
        <input name="transToken" type="hidden" value="$transToken" />
</form>
<script type="text/javascript">document.forms['dpo_redirect'].submit();</script>
HTML;
        die;
    }
}

/**
 * @return void
 */
function dpo_standalone_wp_payment_success(): void
{
    $dpoStandalone       = new DPOStandalone();
    $dpoCommon = new Dpo();

    $post_id          = filter_var($_REQUEST['post_id'], FILTER_SANITIZE_NUMBER_INT);
    $transactionToken = filter_var($_REQUEST['TransactionToken'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $reference        = filter_var($_REQUEST['CompanyRef'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $companyToken     = $dpoStandalone->getCompanyToken();
    $dataArray             = [
        'companyToken' => $companyToken,
        'transToken'   => $transactionToken,
    ];

    try {
        $query    = $dpoCommon->verifyToken($dataArray);
        $verified = new SimpleXMLElement($query);
        if ($verified->Result == '000' && $reference == $verified->CompanyRef->__toString()) {
            // Approved
            update_post_meta($post_id, 'dposa_order_status', 'paid');
            update_post_meta(
                $post_id,
                'dposa_order_transaction_currency',
                $verified->TransactionCurrency->__toString()
            );
            update_post_meta($post_id, 'dposa_order_transaction_amount', $verified->TransactionAmount->__toString());
            update_post_meta(
                $post_id,
                'dposa_order_transaction_approval',
                $verified->TransactionApproval->__toString()
            );
            $qstring = "?reference=$reference&amount=$verified->TransactionAmount";
            header(LOCATION . site_url() . '/' . get_option('dpo_standalone_success_url') . $qstring);
        } else {
            $status_desc = $verified->ResultExplanation->__toString();
            update_post_meta($post_id, 'dposa_order_status', 'failed');
            update_post_meta($post_id, 'dposa_order_failed_reason', $status_desc);
            $qstring = "?reference=$reference&reason=$status_desc";
            header(LOCATION . site_url() . '/' . get_option('dpo_standalone_failure_url') . $qstring);
        }
        die();
    } catch (Exception $exception) {
        $qstring = "?reference=$reference&reason=" . esc_url('The transaction could not be verified');
        header(LOCATION . site_url() . '/' . get_option('dpo_standalone_failure_url') . $qstring);
        die();
    }
}

/**
 * @return void
 */
function dpo_standalone_wp_payment_failure(): void
{
    $dpoStandalone       = new DPOStandalone();
    $dpoCommon = new Dpo();

    $post_id          = filter_var($_REQUEST['post_id'], FILTER_SANITIZE_NUMBER_INT);
    $transactionToken = filter_var($_REQUEST['TransactionToken'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $reference        = filter_var($_REQUEST['CompanyRef'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $companyToken     = $dpoStandalone->getCompanyToken();
    $dataArray             = [
        'companyToken' => $companyToken,
        'transToken'   => $transactionToken,
    ];

    try {
        $query       = $dpoCommon->verifyToken($dataArray);
        $verified    = new SimpleXMLElement($query);
        $status_desc = $verified->ResultExplanation->__toString();
        update_post_meta($post_id, 'dposa_order_status', 'failed');
        update_post_meta($post_id, 'dposa_order_failed_reason', $status_desc);
        $qstring = "?reference=$reference&reason=$status_desc";
        header(LOCATION . site_url() . '/' . get_option('dpo_standalone_failure_url') . $qstring);
        die();
    } catch (Exception $exception) {
        $qstring = "?reference=$reference&reason=" . esc_url('The transaction could not be verified');
        header(LOCATION . site_url() . '/' . get_option('dpo_standalone_failure_url') . $qstring);
        die();
    }
}

/**
 * @return void
 */
function register_dpo_standalone_plugin_settings(): void
{
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_company_token');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_service_type');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_test_mode');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_success_url');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_failure_url');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_recaptcha_key');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_recaptcha_secret');

    /******************************* customer fields *******************************/
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_item_details');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_customer_dial_code');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_customer_zip');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_customer_address');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_customer_city');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_customer_phone');
    /******************************* customer fields *******************************/

    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_airtel_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_amex_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_mastercard_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_mpesa_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_mtnmobilemoney_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_orangemoney_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_paypal_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_tigopesa_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_unionpay_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_visa_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_vodaphonempesa_logo');
    register_setting('dpo_standalone_plugin_options', 'dpo_standalone_show_xpay_logo');
}

/**
 * @return void
 */
function dpo_standalone_init(): void
{
    // Add plugin settings
    add_action('admin_menu', 'register_dpo_standalone_plugin_page');

    // Create custom shortcodes
    add_shortcode('dpo_standalone_payment_checkout', 'add_dpo_standalone_payment_shortcode');
    add_shortcode('dpo_standalone_payment_success', 'add_dpo_standalone_payment_success_shortcode');
    add_shortcode('dpo_standalone_payment_failure', 'add_dpo_standalone_payment_failure_shortcode');
}

/**
 * @return string
 */
/**
 * @return string
 */
function add_dpo_standalone_payment_shortcode(): string
{
    $adminUrl          = admin_url() . 'admin-post.php';
    $pay_methods  = [
        'airtelmoney',
        'amex',
        'mastercard',
        'mpesa',
        'mtnmobilemoney',
        'orangemoney',
        'paypal',
        'tigopesa',
        'unionpay',
        'visa',
        'vodaphonempesa',
        'xpay',
    ];
    $logo_options = [];
    foreach ($pay_methods as $pay_method) {
        $logo_options["$pay_method"] = get_option("dpo_standalone_show_{$pay_method}_logo") == 'yes';
    }

    $logoOptionsHtml = '';
    foreach ($logo_options as $logoKey => $logo_option) {
        if ($logo_option) {
            $logoOptionsHtml .= '<img src="' . plugin_dir_url(__FILE__) . "assets/images/dpo-$logoKey.png" . '"' .
                                  " alt='$logoKey' style='height: 20px !important; display: inline;'>";
        }
    }

    $recaptcha_key = get_option('dpo_standalone_recaptcha_key');

    return <<<HTML
<script src="https://www.google.com/recaptcha/api.js"></script>
<script>
function onSubmit(token) {
 document.getElementById("dpo-standalone-form").submit();
}
</script>
<form method="post" action="$adminUrl" id="dpo-standalone-form">
    <input type="hidden" name="action" value="dpo_standalone_wp_payment">
    <table class="form-table">
        <tbody>
            <tr>
                <td style="background-color: transparent; width:50%;" colspan="2">
                    <input style="width:100%" type="email" name="dpo_standalone_payment_email" placeholder="Email" required="">
                </td>
                <td style="background-color: transparent;" colspan="1">
                    <input style="width:100%" type="number" name="dpo_standalone_payment_amount" step="0.01" placeholder="Amount" required="">
                </td>
                <td style="background-color: transparent;" colspan="1">
                    <button style="width:100%" class="g-recaptcha" 
        data-sitekey="$recaptcha_key" 
        data-callback='onSubmit' 
        data-action='dpo_standalone_wp_payment' type="submit">Pay Now</button>
                </td>
            </tr>
            <tr>
                <td colspan="4" style="background-color: transparent;">
                    <span>$logoOptionsHtml</span>
                </td>
            </tr>
        </tbody>
    </table>
</form>
HTML;
}

/**
 * @return string
 */
/**
 * @return string
 */
function add_dpo_standalone_payment_success_shortcode(): string
{
    $reference = isset($_REQUEST['reference']) ? esc_html(
        filter_var($_REQUEST['reference'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
    ) : 'N/A';
    $amount    = isset($_REQUEST['amount']) ? esc_html(filter_var($_REQUEST['amount'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : 'N/A';

    return <<<HTML
<p>Reference: $reference</p>
<p>Amount: $amount</p>
HTML;
}

/**
 * @return string
 */
/**
 * @return string
 */
function add_dpo_standalone_payment_failure_shortcode(): string
{
    $reference = isset($_REQUEST['reference']) ? esc_html(
        filter_var($_REQUEST['reference'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
    ) : 'N/A';
    $reason    = isset($_REQUEST['reason']) ? esc_html(filter_var($_REQUEST['reason'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : 'N/A';

    return <<<HTML
<p>Reference: $reference</p>
<p>Reason: $reason</p>
HTML;
}

/**
 * @return void
 */
function register_dpo_standalone_plugin_page(): void
{
    add_menu_page(
        'DPO Standalone',
        'DPO Standalone',
        'manage_options',
        'dpo_standalone_plugin_settings',
        'dpo_standalone_option_page_content'
    );
}

/**
 * @return void
 */
function dpo_standalone_option_page_content(): void
{
    ?>
    <h2>DPO Standalone Payment Plugin</h2>
    <h3>Plugin Settings</h3>
    <form method="post" action="options.php">
        <?php
        settings_fields('dpo_standalone_plugin_options'); ?>
        <table class="form-table" aria-describedby="setting_table">
            <tbody>
            <tr>
                <th scope="row">Company Token</th>
                <td>
                    <input type="text" name="dpo_standalone_company_token" id="dpo_standalone_company_token"
                           value="<?php
                           echo get_option('dpo_standalone_company_token'); ?>"><br><span class="description"> Enter your DPO Company (Merchant) Token </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Service Type</th>
                <td>
                    <input type="text" name="dpo_standalone_service_type" id="dpo_standalone_service_type" value="<?php
                    echo get_option('dpo_standalone_service_type'); ?>"><br><span class="description"> Insert a default service type number according to the options accepted by the DPO Group </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Success URL</th>
                <td>
                    <input type="text" name="dpo_standalone_success_url" id="dpo_standalone_success_url" value="<?php
                    echo get_option('dpo_standalone_success_url'); ?>"><br><span class="description"> The URL (full or slug) to which the user is redirected on payment success </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Failure URL</th>
                <td>
                    <input type="text" name="dpo_standalone_failure_url" id="dpo_standalone_failure_url" value="<?php
                    echo get_option('dpo_standalone_failure_url'); ?>"><br><span class="description"> The URL (full or slug) to which the user is redirected on payment failure </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Test Mode</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_test_mode" id="dpo_standalone_test_mode" value="yes"
                        <?php
                        echo get_option('dpo_standalone_test_mode') == 'yes' ? 'checked' : ''; ?>
                    ><br><span
                            class="description"> Uses test accounts if enabled. No real transactions processed </span>
                </td>
            </tr>

            <tr>
                <th scope="row">Recaptcha Key</th>
                <td>
                    <input type="text" name="dpo_standalone_recaptcha_key" id="dpo_standalone_recaptcha_key"
                           value="<?php
                           echo get_option('dpo_standalone_recaptcha_key'); ?> ">
                    <br>
                </td>
            </tr>

            <tr>
                <th scope="row">Recaptcha Secret</th>
                <td>
                    <input type="text" name="dpo_standalone_recaptcha_secret" id="dpo_standalone_recaptcha_secret"
                           value="<?php
                           echo get_option('dpo_standalone_recaptcha_secret'); ?> ">
                    <br>
                </td>
            </tr>

            <!-- Create Token Api Fields -->
            <?php
            $fields = [
                'dpo_standalone_item_details'       => [
                    'Item Details',
                    'ItemDetails field will be used to create token'
                ],
                'dpo_standalone_customer_dial_code' => [
                    'Customer Dial Code',
                    'customerDialCode field data will be used to create token'
                ],
                'dpo_standalone_customer_zip'       => [
                    'Customer Zip',
                    'customerZip field data will be used to create token'
                ],
                'dpo_standalone_customer_address'   => [
                    'Customer Address',
                    'customerAddress field data will be used to create token'
                ],
                'dpo_standalone_customer_city'      => [
                    'Customer City',
                    'customerCity field data will be used to create token'
                ],
                'dpo_standalone_customer_phone'     => [
                    'Customer Phone',
                    'customerPhone field data will be used to create token'
                ]
            ];
            foreach ($fields as $fieldKey => $field) {
                ?>
                <tr>
                    <th scope="row"><?php
                        echo $field[0]; ?></th>
                    <td>
                        <input type="text" name="<?php
                        echo $fieldKey; ?>" id="<?php
                        echo $fieldKey; ?>" value='<?php
                        echo get_option("$fieldKey"); ?>'>
                        <br/>
                        <span class="description"><?php
                            echo $field[1]; ?></span>
                    </td>
                </tr>

                <?php
            }
            ?>
            <!-- Create Token Api Fields -->

            <tr>
                <th scope="row">Display Airtel Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_airtel_logo" id="dpo_standalone_show_airtel_logo"
                           value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_airtel_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-airtelmoney.png'; ?>" alt="show-airtel-logo"
                          height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show Airtel logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display Amex Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_amex_logo" id="dpo_standalone_show_amex_logo"
                           value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_amex_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-amex.png'; ?>" alt="show-amex-logo" height="20px"
                          style="margin-bottom: -7px;"><br><span
                            class="description"> Show Amex logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display Mastercard Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_mastercard_logo"
                           id="dpo_standalone_show_mastercard_logo" value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_mastercard_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-mastercard.png'; ?>" alt="show-mastercard-logo"
                          height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show Mastercard logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display MPesa Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_mpesa_logo" id="dpo_standalone_show_mpesa_logo"
                           value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_mpesa_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-mpesa.png'; ?>" alt="show-mpesa-logo"
                          height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show MPesa logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display MTN Mobile Money Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_mtnmobilemoney_logo"
                           id="dpo_standalone_show_mtnmobilemoney_logo" value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_mtnmobilemoney_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-mtnmobilemoney.png'; ?>"
                          alt="show-mtnmobilemoney-logo" height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show MTN Mobile Money logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display Orange Money Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_orangemoney_logo"
                           id="dpo_standalone_show_orangemoney_logo" value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_orangemoney_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-orangemoney.png'; ?>" alt="show-orangemoney-logo"
                          height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show Orange Money logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display Paypal Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_paypal_logo" id="dpo_standalone_show_paypal_logo"
                           value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_paypal_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-paypal.png'; ?>" alt="show-paypal-logo"
                          height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show Paypal logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display TigoPesa Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_tigopesa_logo"
                           id="dpo_standalone_show_tigopesa_logo" value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_tigopesa_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-tigopesa.png'; ?>" alt="show-tigopesa-logo"
                          height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show TigoPesa logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display Union Pay Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_unionpay_logo"
                           id="dpo_standalone_show_unionpay_logo" value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_unionpay_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-unionpay.png'; ?>" alt="show-unionpay-logo"
                          height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show Union Pay logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display Visa Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_visa_logo" id="dpo_standalone_show_visa_logo"
                           value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_visa_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-visa.png'; ?>" alt="show-visa-logo" height="20px"
                          style="margin-bottom: -7px;"><br><span
                            class="description"> Show Visa logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display Vodaphone MPesa Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_vodaphonempesa_logo"
                           id="dpo_standalone_show_vodaphonempesa_logo" value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_vodaphonempesa_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-vodaphonempesa.png'; ?>"
                          alt="show-vodaphonempesa-logo" height="20px" style="margin-bottom: -7px;"><br><span
                            class="description"> Show Vodaphone MPesa logo at checkout </span>
                </td>
            </tr>
            <tr>
                <th scope="row">Display Xpay Logo at Checkout</th>
                <td>
                    <input type="checkbox" name="dpo_standalone_show_xpay_logo" id="dpo_standalone_show_xpay_logo"
                           value="yes"
                        <?php
                        echo get_option('dpo_standalone_show_xpay_logo') == 'yes' ? 'checked' : ''; ?>
                    ><img src="<?php
                    echo plugin_dir_url(__FILE__) . 'assets/images/dpo-xpay.png'; ?>" alt="show-xpay-logo" height="20px"
                          style="margin-bottom: -7px;"><br><span
                            class="description"> Show Xpay logo at checkout </span>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        submit_button('Save Settings'); ?>
    </form>
    <?php
}

/**
 * @return true|void
 */
/**
 * @return true|void
 */
function validate_form()
{
    $referer_location = $_SERVER['HTTP_REFERER'];

    $token  = $_POST['g-recaptcha-response'];
    $action = $_POST['action'];
    $secret = get_option('dpo_standalone_recaptcha_secret');

    // call curl to POST request
    $curlRequest = curl_init();
    curl_setopt($curlRequest, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($curlRequest, CURLOPT_POST, 1);
    curl_setopt($curlRequest, CURLOPT_POSTFIELDS, http_build_query(['secret' => $secret, 'response' => $token]));
    curl_setopt($curlRequest, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curlRequest);
    curl_close($curlRequest);
    $arrResponse = json_decode($response, true);

    // verify the response
    if ($arrResponse['success'] == '1' && $arrResponse['action'] == $action && $arrResponse['score'] >= 0.5) {
        return true;
    } else {
        header('Location:' . $referer_location);
        exit(0);
    }
}
