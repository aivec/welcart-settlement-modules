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
     * We use dependency injection here so that any instance of Module can extend
     * this class and perform checkout actions
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @throws InvalidArgumentException Thrown if module is not an instance of \Aivec\Welcart\SettlementModules\Module.
     * @return void
     */
    public function __construct(Module $module) {
        $this->module = $module;
        add_action('usces_action_acting_processing', array($this, 'actingProcessingDI'), 10, 2);              // STEP 1
        add_filter('usces_filter_check_acting_return_results', array($this, 'actingReturnResultsDI'), 10, 1); // STEP 2
        add_filter('usces_filter_reg_orderdata_status', array($this, 'registerOrderDataStatusDI'), 10, 2);    // STEP 3
        add_action('usces_action_reg_orderdata', array($this, 'registerOrderDataDI'), 10, 2);                 // STEP 4
    }

    /**
     * Processes acting data and uses PRG pattern to avoid form re-submission
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $acting_flag // the string to compare against the payment method
     * @param mixed  $post_query
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
     * @param mixed  $post_query
     * @return void
     */
    protected function actingProcessing($acting_flag, $post_query) {
    }

    /**
     * Populates results object for use later in order_processing. Uses acting_return to determine
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
     * Populates results object for use later in order_processing. Uses acting_return to determine
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
     * @param string $status
     * @param array  $entry
     * @return string
     */
    public function registerOrderDataStatusDI($status, $entry) {
        global $usces;
        $payments = $usces->getPayments($_SESSION['usces_entry']['order']['payment_name']);
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
     * @param  array $args
     *           'cart'=>$cart, 'entry'=>$entry, 'order_id'=>$order_id,
     *           'member_id'=>$member['ID'], 'payments'=>$set, 'charging_type'=>$charging_type,
     *           'results'=>$results
     * @return void
     */
    public function registerOrderDataDI($args) {
        global $usces;
        $payments = $usces->getPayments($_SESSION['usces_entry']['order']['payment_name']);
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
     * @param array $html
     * @return string
     */
    public function errorPageMessageDI($html) {
        if (isset($_REQUEST['acting']) && $this->module->getActing() === $_REQUEST['acting']) {
            $html .= $this->errorPageMessage($html);
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
        return '';
    }
}
