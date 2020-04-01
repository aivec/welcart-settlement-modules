<?php
namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;

/**
 * Registers all necessary checkout hooks and delegates
 * requests to checkout model after performing necessary
 * checks and data sanatization
 */
class CheckoutHooks {

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Registers Welcart checkout hooks.
     *
     * We use dependency injection here so that any instance of `Module` can extend
     * this class and perform checkout actions
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @throws InvalidArgumentException Thrown if module is not an instance of `Module`.
     * @return void
     */
    public function __construct(Module $module) {
        $this->module = $module;
        add_action('usces_action_acting_processing', [$this, 'actingProcessingDI'], 10, 2);              // STEP 1
        add_filter('usces_filter_acting_processing', [$this, 'filterActingProcessingDI'], 10, 3);        // STEP 2
        add_filter('usces_filter_check_acting_return_results', [$this, 'actingReturnResultsDI'], 10, 1); // STEP 3
        add_filter('usces_filter_reg_orderdata_status', [$this, 'registerOrderDataStatusDI'], 10, 2);    // STEP 4
        add_action('usces_action_reg_orderdata', [$this, 'registerOrderDataDI'], 10, 2);                 // STEP 5
        add_filter('usces_filter_get_error_settlement', [$this, 'errorPageMessageDI'], 10, 1);
        add_filter('usces_filter_completion_settlement_message', [$this, 'filterSettlementCompletionPageDI'], 10, 2);
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
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $acting_flag
     * @param string $post_query
     * @param string $acting_status
     * @return string
     */
    public function filterActingProcessingDI($acting_flag, $post_query, $acting_status) {
        if ($acting_flag === $this->module->getActingFlag()) {
            return $this->filterActingProcessing($acting_flag, $post_query, $acting_status);
        }

        return $acting_status;
    }

    /**
     * Filters the `acting_status` string returned by `\usc_e_shop::acting_processing()`
     *
     * The only `acting_status` recognized by Welcart is `error`. All other strings are ignored.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $acting_flag
     * @param string $post_query
     * @param string $acting_status
     * @return string
     */
    protected function filterActingProcessing($acting_flag, $post_query, $acting_status) {
        return $acting_status;
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
            return;
        }
        $payments = $usces->getPayments($_SESSION['usces_entry']['order']['payment_name']);
        if (empty($payments)) {
            return;
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
     * @global \usc_e_shop $usces
     * @param string $html
     * @param array  $usces_entries
     * @return string
     */
    protected function filterSettlementCompletionPage($html, array $usces_entries) {
        return $html;
    }
}
