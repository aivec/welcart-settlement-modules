<?php

namespace Aivec\Welcart\SettlementModules;

use Aivec\Welcart\SettlementModules\Interfaces\Initializer;

/**
 * Registers all necessary checkout hooks and delegates
 * requests to checkout model after performing necessary
 * checks and data sanatization
 */
class CheckoutHooks implements Initializer
{
    use HooksAutoloader;

    const PURCHASE_NONCE_NAME = '_welpurchase';

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Whether to issue the buyer points in accordance with ポイント率初期値 on purchase.
     *
     * @var bool
     */
    private $issuePointsOnPurchase;

    /**
     * Registers Welcart checkout hooks.
     *
     * We use dependency injection here so that any instance of `Module` can extend
     * this class and perform checkout actions
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @param bool   $issuePointsOnPurchase Whether to issue the buyer points in accordance with ポイント率初期値 on purchase.
     *                                      Default: `true`
     * @return void
     */
    public function __construct(Module $module, $issuePointsOnPurchase = true) {
        $this->module = $module;
        $this->issuePointsOnPurchase = $issuePointsOnPurchase;
    }

    /**
     * Adds mandatory actions/filters
     *
     * Returns current instance for optional chaining
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return CheckoutHooks
     */
    public function init() {
        if (is_admin()) {
            return $this;
        }
        add_action('usces_action_reg_orderdata', [$this, 'registerOrderDataDI'], 10, 2);                        // STEP 5
        add_filter('usces_filter_is_complete_settlement', [$this, 'filterPointIssuanceDI'], 10, 3);
        $this->addHooks();
        return $this;
    }

    /**
     * Dynamically adds actions/filters.
     *
     * Only hooks implemented by the child class are registered
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    private function addHooks() {
        if (!$this->shouldRegisterHooks()) {
            return;
        }
        $map = [
            new HookMeta(['actingProcessing'], function () {
                add_action('usces_action_acting_processing', [$this, 'actingProcessingDI'], 10, 2);              // STEP 1
            }),
            new HookMeta(['filterActingProcessing'], function () {
                add_filter('usces_filter_acting_processing', [$this, 'filterActingProcessingDI'], 10, 3);        // STEP 2
            }),
            new HookMeta(['actingReturnResults'], function () {
                add_filter('usces_filter_check_acting_return_results', [$this, 'actingReturnResultsDI'], 10, 1); // STEP 3
            }),
            new HookMeta(['registerOrderDataStatus'], function () {
                add_filter('usces_filter_reg_orderdata_status', [$this, 'registerOrderDataStatusDI'], 10, 2);    // STEP 4
            }),
            new HookMeta(['errorPageMessage'], function () {
                add_filter('usces_filter_get_error_settlement', [$this, 'errorPageMessageDI'], 10, 1);
            }),
            new HookMeta(['filterSettlementCompletionPage'], function () {
                add_filter('usces_filter_completion_settlement_message', [$this, 'filterSettlementCompletionPageDI'], 10, 2);
            }),
        ];

        $this->dynamicallyRegisterHooks($map);
    }

    /**
     * Verifies nonce sent by purchase form
     *
     * @see ConfirmPage::filterBeforeBackButtonDI()
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    private function verifyPurchaseNonce() {
        $nonce = '';
        if (isset($_REQUEST[self::PURCHASE_NONCE_NAME])) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST[self::PURCHASE_NONCE_NAME]));
        }
        if (!wp_verify_nonce($nonce, $this->module->getActing())) {
            http_response_code(403);
            die('Forbidden');
        }
    }

    /**
     * Processes acting data and uses PRG pattern to avoid form re-submission
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $acting_flag // the string to compare against the payment method
     * @param string $post_query
     * @return void
     */
    public function actingProcessingDI($acting_flag, $post_query) {
        if ($acting_flag === $this->module->getActingFlag()) {
            $this->verifyPurchaseNonce();
            $this->actingProcessing($acting_flag, $post_query);
        }
    }

    /**
     * Processes acting data and uses PRG pattern to avoid form re-submission
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $acting_flag // the string to compare against the payment method
     * @param string $post_query
     * @return void
     */
    protected function actingProcessing($acting_flag, $post_query) {
    }

    /**
     * Filters the `acting_status` string returned by `\usc_e_shop::acting_processing()`
     *
     * The only `acting_status` recognized by Welcart is `error`. All other strings are ignored.
     *
     * *WARNING:* This filter erroneously passes the `$acting_flag` as the first parameter even though
     * technically `$acting_status` is the value being filtered. This doesn't change the fact that
     * WordPress' filter system expects the **first parameter** to contain the filtered value which
     * is why we pass back `$acting_flag`.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @param string $acting_flag
     * @param string $post_query
     * @param string $acting_status
     * @return string
     */
    public function filterActingProcessingDI($acting_flag, $post_query, $acting_status) {
        global $usces;

        $entry = $usces->cart->get_entry();
        $payments = $usces->getPayments($entry['order']['payment_name']);
        $real_acting_flag = 'acting' === $payments['settlement'] ? $payments['module'] : $payments['settlement'];
        if ($real_acting_flag === $this->module->getActingFlag()) {
            $this->verifyPurchaseNonce();
            return $this->filterActingProcessing($acting_flag, $post_query, $acting_status);
        }

        return $acting_flag;
    }

    /**
     * Filters the `acting_status` string returned by `\usc_e_shop::acting_processing()`
     *
     * The only `acting_status` recognized by Welcart is `error`. All other strings are ignored.
     *
     * *WARNING:* This filter erroneously passes the `$acting_flag` as the first parameter even though
     * technically `$acting_status` is the value being filtered. This doesn't change the fact that
     * WordPress' filter system expects the **first parameter** to contain the filtered value which
     * is why we pass back `$acting_flag`.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $acting_flag
     * @param string $post_query
     * @param string $acting_status
     * @return string
     */
    protected function filterActingProcessing($acting_flag, $post_query, $acting_status) {
        return $acting_flag;
    }

    /**
     * Populates results object for use later in order_processing. Uses `acting_return` to determine
     * whether to process the order or error out
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $results
     * @return array
     */
    public function actingReturnResultsDI($results) {
        if ($_GET['acting'] === $this->module->getActing()) {
            return $this->actingReturnResults($results);
        }
        return $results;
    }

    /**
     * Populates results object for use later in order_processing. Uses `acting_return` to determine
     * whether to process the order or error out
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $results
     * @return array
     */
    protected function actingReturnResults($results) {
        return $results;
    }

    /**
     * Sets order status
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @param string $status
     * @param array  $entry
     * @return string
     */
    public function registerOrderDataStatusDI($status, $entry) {
        global $usces;

        $payments = $usces->getPayments($entry['order']['payment_name']);
        $acting_flg = 'acting' === $payments['settlement'] ? $payments['module'] : $payments['settlement'];
        if ($acting_flg === $this->module->getActingFlag()) {
            return $this->registerOrderDataStatus($status, $entry);
        }
        return $status;
    }

    /**
     * Sets order status
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $status
     * @param array  $entry
     * @return string
     */
    protected function registerOrderDataStatus($status, $entry) {
        return $status;
    }

    /**
     * Register order data
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @param  array $args
     *           'cart'=>$cart, 'entry'=>$entry, 'order_id'=>$order_id,
     *           'member_id'=>$member['ID'], 'payments'=>$set, 'charging_type'=>$charging_type,
     *           'results'=>$results
     * @return void
     */
    public function registerOrderDataDI($args) {
        global $usces;

        if (!isset($args['entry']['order']['payment_name'])) {
            return;
        }
        $payments = $usces->getPayments($args['entry']['order']['payment_name']);
        if (empty($payments)) {
            return;
        }
        $acting_flg = 'acting' === $payments['settlement'] ? $payments['module'] : $payments['settlement'];
        if ($acting_flg === $this->module->getActingFlag()) {
            $usces->set_order_meta_value(Module::ACTING_FLAG_ORDER_META_KEY, $acting_flg, $args['order_id']);
            $this->registerOrderData($args);
        }
    }

    /**
     * Register order data
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param  array $args
     *           'cart'=>$cart, 'entry'=>$entry, 'order_id'=>$order_id,
     *           'member_id'=>$member['ID'], 'payments'=>$set, 'charging_type'=>$charging_type,
     *           'results'=>$results
     * @return void
     */
    protected function registerOrderData($args) {
    }

    /**
     * Creates error message for error page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @param array $html
     * @return string
     */
    public function errorPageMessageDI($html) {
        global $usces;

        if (!isset($_SESSION['usces_entry']['order']['payment_name'])) {
            return $html;
        }
        $payments = $usces->getPayments($_SESSION['usces_entry']['order']['payment_name']);
        if (empty($payments)) {
            return $html;
        }
        $acting_flg = 'acting' === $payments['settlement'] ? $payments['module'] : $payments['settlement'];
        if ($acting_flg === $this->module->getActingFlag()) {
            $html = $this->errorPageMessage($html);
        }
        return $html;
    }

    /**
     * Creates error message for error page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $html
     * @return string
     */
    protected function errorPageMessage($html) {
        return $html;
    }

    /**
     * Filters HTML displayed on settlement completion page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @param string $html
     * @param array  $usces_entries
     * @return string
     */
    public function filterSettlementCompletionPageDI($html, array $usces_entries) {
        global $usces;

        $payments = $usces->getPayments($usces_entries['order']['payment_name']);
        $acting_flg = 'acting' === $payments['settlement'] ? $payments['module'] : $payments['settlement'];
        if ($acting_flg === $this->module->getActingFlag()) {
            $html = $this->filterSettlementCompletionPage($html, $usces_entries);
        }

        return $html;
    }

    /**
     * Filters HTML displayed on settlement completion page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $html
     * @param array  $usces_entries
     * @return string
     */
    protected function filterSettlementCompletionPage($html, array $usces_entries) {
        return $html;
    }

    /**
     * Filters point issuance for the current `Module`
     *
     * `$complete` must be set to `true` in order for points to be allotted on purchase
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param bool   $complete
     * @param string $payment_name
     * @param string $status
     * @return bool
     */
    public function filterPointIssuanceDI($complete, $payment_name, $status) {
        $options = get_option('usces');
        if ((int)$options['point_assign'] === 0) {
            return $complete;
        }

        $payments = usces_get_system_option('usces_payment_method', 'name');
        if (isset($payments[$payment_name]['settlement'])) {
            if ($payments[$payment_name]['settlement'] === $this->module->getActingFlag()) {
                if ($this->issuePointsOnPurchase === true) {
                    $complete = true;
                } else {
                    $complete = $this->filterPointIssuance($complete, $payment_name, $status);
                }
            }
        }

        return $complete;
    }

    /**
     * Filters point issuance for the current `Module`
     *
     * `$complete` must be set to `true` in order for points to be allotted on purchase. NOTE: this method
     * is not called if `$issuePointsOnPurchase` is `true`.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param bool   $complete
     * @param string $payment_name
     * @param string $status
     * @return bool
     */
    protected function filterPointIssuance($complete, $payment_name, $status) {
        return $complete;
    }
}
