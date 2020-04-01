<?php
namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;

/**
 * Order edit page
 */
class OrderEditPage {

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Register hooks
     *
     * We use dependency injection here so that any instance of `Module` can use
     * this class as a order edit page wrapper
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @throws InvalidArgumentException Thrown if module is not an instance of `Module`.
     * @return void
     */
    public function __construct(Module $module) {
        $this->module = $module;
        add_action('usces_action_order_edit_form_status_block_middle', [$this, 'orderEditFormStatusBlockMiddleDI'], 10, 3);
        add_action('usces_action_order_edit_form_status_block_middle', [$this, 'loadAssetsDI'], 10, 3);
        add_action('usces_action_update_orderdata', [$this, 'updateOrderDataDI'], 10, 3);
        add_action('usces_after_update_orderdata', [$this, 'setActionStatusAndMessage'], 10, 2);
    }

    /**
     * Arbitrary hook used for enqueueing assets. Delegates to enqueueAssets for this `Module`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $data
     * @param array $cscs_meta
     * @param array $action_args
     * @return void
     */
    public function loadAssetsDI($data, $cscs_meta, $action_args) {
        if ($data['order_payment_name'] === $this->module->getPaymentName()) {
            $this->enqueueAssets($data, $cscs_meta, $action_args);
        }
    }

    /**
     * Override for enqueueing assets for the order_edit_page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $data
     * @param array $cscs_meta
     * @param array $action_args
     * @return void
     */
    protected function enqueueAssets($data, $cscs_meta, $action_args) {
    }

    /**
     * Delegates to orderEditFormStatusBlockMiddle for this `Module`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $data
     * @param array $cscs_meta
     * @param array $action_args
     * @return void
     */
    public function orderEditFormStatusBlockMiddleDI($data, $cscs_meta, $action_args) {
        if ($data['order_payment_name'] === $this->module->getPaymentName()) {
            $this->orderEditFormStatusBlockMiddle($data, $cscs_meta, $action_args);
        }
    }

    /**
     * Action for extra rows in status block form table
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $data
     * @param array $cscs_meta
     * @param array $action_args
     * @return void
     */
    protected function orderEditFormStatusBlockMiddle($data, $cscs_meta, $action_args) {
    }

    /**
     * Delegates update order processing if current Module.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global use_e_shop $usces
     * @param stdClass $new_orderdata
     * @param string   $old_status
     * @param stdClass $old_orderdata
     * @return void
     */
    public function updateOrderDataDI($new_orderdata, $old_status, $old_orderdata) {
        global $usces;
        $payments = $usces->getPayments($old_orderdata->order_payment_name);
        $acting_flg = 'acting' === $payments['settlement'] ? $payments['module'] : $payments['settlement'];
        if ($acting_flg === $this->module->getActingFlag()) {
            $this->updateOrderData($new_orderdata, $old_status, $old_orderdata);
            $shipped = explode(',', $new_orderdata->order_status);
            $status = trim($shipped[0]);
            switch ($status) {
                case 'completion':
                    $this->updateOrderDataCompletion($new_orderdata, $old_status, $old_orderdata);
                    break;
                case 'cancel':
                    $this->updateOrderDataCancel($new_orderdata, $old_status, $old_orderdata);
                    break;
            }
        }
    }

    /**
     * Update order data
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param stdClass $new_orderdata
     * @param string   $old_status
     * @param stdClass $old_orderdata
     * @return void
     */
    protected function updateOrderData($new_orderdata, $old_status, $old_orderdata) {
    }

    /**
     * Called on completion status
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param stdClass $new_orderdata
     * @param string   $old_status
     * @param stdClass $old_orderdata
     * @return void
     */
    protected function updateOrderDataCompletion($new_orderdata, $old_status, $old_orderdata) {
    }

    /**
     * Called on cancel status
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param stdClass $new_orderdata
     * @param string   $old_status
     * @param stdClass $old_orderdata
     * @return void
     */
    protected function updateOrderDataCancel($new_orderdata, $old_status, $old_orderdata) {
    }

    /**
     * Set action status and message after single order update
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int   $order_id
     * @param array $res
     * @return void
     */
    public function setActionStatusAndMessage($order_id, $res) {
    }
}
