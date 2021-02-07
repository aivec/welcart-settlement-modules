<?php

namespace Aivec\Welcart\SettlementModules;

use Aivec\Welcart\Generic\WelcartUtils;
use Aivec\Welcart\SettlementModules\Interfaces\TransactionState;

/**
 * Order edit page
 */
class OrderEditPage
{
    use HooksAutoloader;

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Sets member vars
     *
     * We use dependency injection here so that any instance of `Module` can use
     * this class as an order edit page wrapper
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @return void
     */
    public function __construct(Module $module) {
        $this->module = $module;
    }

    /**
     * Dynamically adds actions/filters.
     *
     * Only hooks implemented by the child class are registered
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function addHooks() {
        $map = [
            new HookMeta(['afterDetailsSection'], function () {
                add_action('usces_action_order_edit_form_detail_bottom', [$this, 'afterDetailsSectionDI'], 10, 3);
            }),
            new HookMeta(['orderEditFormStatusBlockMiddle'], function () {
                add_action('usces_action_order_edit_form_status_block_middle', [$this, 'orderEditFormStatusBlockMiddleDI'], 10, 3);
            }),
            new HookMeta(['enqueueAssets'], function () {
                add_action('usces_action_order_edit_form_status_block_middle', [$this, 'loadAssetsDI'], 10, 3);
            }),
            new HookMeta(['updateOrderData', 'updateOrderDataCompletion', 'updateOrderDataCancel'], function () {
                add_action('usces_action_update_orderdata', [$this, 'updateOrderDataDI'], 10, 3);
            }),
            new HookMeta(['setActionStatusAndMessage'], function () {
                add_action('usces_after_update_orderdata', [$this, 'setActionStatusAndMessage'], 10, 2);
            }),
            new HookMeta(['settlementInfo'], function () {
                add_action('usces_action_order_edit_form_settle_info', [$this, 'settlementInfoDI'], 10, 2);
            }),
            new HookMeta(['settlementDialog'], function () {
                add_action('usces_action_endof_order_edit_form', [$this, 'settlementDialogDI'], 10, 2);
            }),
            new HookMeta(['injectJavascript'], function () {
                add_action('admin_print_footer_scripts', [$this, 'injectJavascriptDI']);
            }),
            new HookMeta(['filterDeliveryCompanies'], function () {
                add_filter('usces_filter_deli_comps', [$this, 'filterDeliveryCompaniesDI'], 10, 1);
            }),
        ];

        $this->dynamicallyRegisterHooks($map);
    }

    /**
     * Determines whether the `order_id` for the currently open `order_edit_form.php`
     * corresponds to an order that was purchased with this classes settlement `Module`
     * instance.
     *
     * This is a fallback method for when no useful parameters are passed to the hook
     * where you want to check which `Module` was used.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function isMyModuleNoParamsCheck() {
        if (WelcartUtils::isOrderEditPage()) {
            $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : '';
            if (!empty($order_id)) {
                return $this->module->isOrderAssociated($order_id);
            }
        }

        return false;
    }

    /**
     * Returns `true` if the current page is the order edit page and the order_id
     * is associated with our `Module` instance
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function isMyModuleOrderEditPage() {
        if (!WelcartUtils::isOrderEditPage()) {
            return false;
        }
        $orderid = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : null;
        if (empty($orderid)) {
            return false;
        }

        return $this->module->isOrderAssociated($orderid);
    }

    /**
     * Conditional assets loader for the order edit page.
     *
     * If the current page is the order edit page **AND** the payment method used
     * for the order in question corresponds to our injected `Module` instance, `admin_enqueue_scripts`
     * will be used to invoke the `$enqueue` callback parameter and `true` will be returned,
     * otherwise no action will be taken and `false` will be returned.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \use_e_shop $usces
     * @param callable $enqueue
     * @return bool
     */
    public function loadMyModuleAssets(callable $enqueue) {
        if (!$this->isMyModuleOrderEditPage()) {
            return false;
        }

        // if all checks pass, enqueue assets
        add_action('admin_enqueue_scripts', function () use ($enqueue) {
            $enqueue();
        });

        return true;
    }

    /**
     * Action that fires right before the ending `</table>` tag for the extra details section
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $data
     * @param array $cscs_meta
     * @param array $action_args ['order_action', 'order_id', 'cart']
     * @return void
     */
    public function afterDetailsSectionDI($data, $cscs_meta, $action_args) {
        if (strtolower(trim($action_args['order_action'])) !== 'new' && !empty($action_args['order_id'])) {
            if ($this->module->isOrderAssociated((int)$action_args['order_id'])) {
                $this->afterDetailsSection($data, $cscs_meta, $action_args);
            }
        }
    }

    /**
     * Action that fires right before the ending `</table>` tag for the extra details section
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $data
     * @param array $cscs_meta
     * @param array $action_args ['order_action', 'order_id', 'cart']
     * @return void
     */
    protected function afterDetailsSection($data, $cscs_meta, $action_args) {
    }

    /**
     * 支払情報 section of order edit page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $data
     * @param mixed $action_args ['order_action', 'order_id', 'cart']
     * @return void
     */
    public function settlementInfoDI($data, $action_args) {
        if (strtolower(trim($action_args['order_action'])) !== 'new' && !empty($action_args['order_id'])) {
            if ($this->module->isOrderAssociated((int)$action_args['order_id'])) {
                $this->settlementInfo($data, $action_args);
            }
        }
    }

    /**
     * 支払情報 section of order edit page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $data
     * @param mixed $action_args ['order_action', 'order_id', 'cart']
     * @return void
     */
    protected function settlementInfo($data, $action_args) {
    }

    /**
     * 決済情報 dialog box
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $data
     * @param mixed $action_args ['order_action', 'order_id', 'cart']
     * @return void
     */
    public function settlementDialogDI($data, $action_args) {
        if (strtolower(trim($action_args['order_action'])) !== 'new' && !empty($action_args['order_id'])) {
            if ($this->module->isOrderAssociated((int)$action_args['order_id'])) {
                $this->settlementDialog($data, $action_args);
            }
        }
    }

    /**
     * 決済情報 dialog box
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $data
     * @param mixed $action_args ['order_action', 'order_id', 'cart']
     * @return void
     */
    protected function settlementDialog($data, $action_args) {
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
        if (strtolower(trim($action_args['order_action'])) !== 'new' && !empty($action_args['order_id'])) {
            if ($this->module->isOrderAssociated((int)$action_args['order_id'])) {
                $this->enqueueAssets($data, $cscs_meta, $action_args);
            }
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
        if (strtolower(trim($action_args['order_action'])) !== 'new' && !empty($action_args['order_id'])) {
            if ($this->module->isOrderAssociated((int)$action_args['order_id'])) {
                $this->orderEditFormStatusBlockMiddle($data, $cscs_meta, $action_args);
            }
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
     * Displays transaction state as a row within an HTML table
     *
     * This method should be called from within an overridden `orderEditFormStatusBlockMiddle` method.
     * Doing so will display the transaction state under `ステータス` on the left hand side of the
     * order edit page.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param TransactionState $state
     * @param string|null      $id span id
     * @return void
     */
    public function displayTransactionState(TransactionState $state, $id = null) {
        if ($id === null) {
            $id = $this->module->getActing() . '-acting-status';
        }
        ?>
        <tr>
            <td class="label status"><?php _e('Settlement status', 'usces'); ?></td>
            <td class="col1 status">
                <span class="settlement-status">
                    <span id="<?php echo esc_attr($id); ?>" class="acting-status <?php echo $state->getCssClass(); ?>">
                        <?php echo $state->getDisplayText(); ?>
                    </span>
                </span>
            </td>
        </tr>
        <?php
    }

    /**
     * Delegates update order processing if current Module.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param \stdClass $new_orderdata
     * @param string    $old_status
     * @param \stdClass $old_orderdata
     * @return void
     */
    public function updateOrderDataDI($new_orderdata, $old_status, $old_orderdata) {
        if ($this->module->isOrderAssociated((int)$old_orderdata->ID)) {
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
     * @param \stdClass $new_orderdata
     * @param string    $old_status
     * @param \stdClass $old_orderdata
     * @return void
     */
    protected function updateOrderData($new_orderdata, $old_status, $old_orderdata) {
    }

    /**
     * Called on completion status
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param \stdClass $new_orderdata
     * @param string    $old_status
     * @param \stdClass $old_orderdata
     * @return void
     */
    protected function updateOrderDataCompletion($new_orderdata, $old_status, $old_orderdata) {
    }

    /**
     * Called on cancel status
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param \stdClass $new_orderdata
     * @param string    $old_status
     * @param \stdClass $old_orderdata
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

    /**
     * Admin footer hook for injecting JavaScript
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function injectJavascriptDI() {
        if ($this->isMyModuleNoParamsCheck()) {
            $this->injectJavascript();
        }
    }

    /**
     * Admin footer hook for injecting JavaScript
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    protected function injectJavascript() {
    }

    /**
     * Filters delivery companies displayed in dropdown list
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $deli_comps
     * @return array
     */
    public function filterDeliveryCompaniesDI($deli_comps) {
        if ($this->isMyModuleNoParamsCheck()) {
            $deli_comps = $this->filterDeliveryCompanies($deli_comps);
        }

        return $deli_comps;
    }

    /**
     * Filters delivery companies displayed in dropdown list
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $deli_comps
     * @return array
     */
    protected function filterDeliveryCompanies($deli_comps) {
        return $deli_comps;
    }
}
