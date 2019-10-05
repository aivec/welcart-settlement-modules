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
     * We use dependency injection here so that any instance of Module can use
     * this class as a order edit page wrapper
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @throws InvalidArgumentException Thrown if module is not an instance of \Aivec\Welcart\SettlementModules\Module.
     * @return void
     */
    public function __construct($module) {
        if (!($module instanceof Module)) {
            throw new InvalidArgumentException(
                'the provided module is not an instance of \Aivec\Welcart\SettlementModules\Module'
            );
        }

        $this->module = $module;
        add_action('usces_action_order_edit_form_status_block_middle', array($this, 'orderEditFormStatusBlockMiddleDI'), 10, 3);
        add_action('usces_action_order_edit_form_status_block_middle', array($this, 'loadAssetsDI'), 10, 3);
    }

    /**
     * Arbitrary hook used for enqueueing assets. Delegates to enqueueAssets for this Module
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
     * Delegates to orderEditFormStatusBlockMiddle for this Module
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
}
