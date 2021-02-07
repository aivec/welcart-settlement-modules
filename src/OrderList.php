<?php

namespace Aivec\Welcart\SettlementModules;

use Aivec\Welcart\SettlementModules\Interfaces\TransactionState;

/**
 * Order List hooks
 */
class OrderList
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
            new HookMeta(['filterOrderlistDetailValue'], function () {
                add_filter('usces_filter_orderlist_detail_value', [$this, 'filterOrderlistDetailValueDI'], 10, 4);
            }),
            new HookMeta(['batchUpdateOrderStatus', 'batchUpdateOrderStatusCompletion', 'batchUpdateOrderStatusCancel'], function () {
                add_action('usces_action_collective_order_status', [$this, 'batchUpdateOrderStatusDI'], 10, 3);
            }),
            new HookMeta(['filterErrorLog'], function () {
                add_filter('usces_filter_order_item_ajax', [$this, 'filterErrorLogDI'], 10, 1);
            }),
        ];

        $this->dynamicallyRegisterHooks($map);
    }

    /**
     * Batch updates based on selected order status.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see function updateOrderData
     * @global wpdb $wpdb
     * @param WlcOrderList|dataList $obj {@see use-e-shop/classes/orderList[2].class.php}
     * @return void
     */
    public function batchUpdateOrderStatusDI($obj) {
        global $wpdb;

        check_admin_referer('order_list', 'wc_nonce');

        $table_name = $wpdb->prefix . 'usces_order';
        $ids = isset($_POST['listcheck']) ? $_POST['listcheck'] : [];

        $msgstr = '';
        foreach ((array)$ids as $id) :
            $query = $wpdb->prepare(
                "SELECT 
                order_status,
                mem_id,
                order_getpoint,
                order_usedpoint,
                order_payment_name 
                FROM {$table_name}
                WHERE ID = %d",
                $id
            );
            $order_res = $wpdb->get_row($query, ARRAY_A);
            if ($this->module->isOrderAssociated((int)$id)) {
                $msgstr = $this->batchUpdateOrderStatus($msgstr, $order_res, $_REQUEST, $id, $obj);

                $change_word = '';
                if (isset($_REQUEST['change']['word']) && !is_array($_REQUEST['change']['word'])) {
                    $change_word = $_REQUEST['change']['word'];
                } elseif (isset($_REQUEST['change']['word']['order_reciept'])) {
                    $change_word = $_REQUEST['change']['word']['order_reciept'];
                } elseif (isset($_REQUEST['change']['word']['order_status'])) {
                    $change_word = $_REQUEST['change']['word']['order_status'];
                }

                switch ($change_word) {
                    case 'completion':
                        $msgstr = $this->batchUpdateOrderStatusCompletion($msgstr, $order_res, $_REQUEST, $id, $obj);
                        break;
                    case 'cancel':
                        $msgstr = $this->batchUpdateOrderStatusCancel($msgstr, $order_res, $_REQUEST, $id, $obj);
                        break;
                }
            }
        endforeach;
        if (!empty($msgstr)) {
            $obj->set_action_status('error', $msgstr);
        } else {
            $obj->set_action_status('success', __('I completed collective operation.', 'usces'));
        }
    }

    /**
     * Batch updates based on selected order status.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see function updateOrderData
     * @param string                $msgstr
     * @param array                 $order_res
     * @param array                 $req $_REQUEST
     * @param integer               $id
     * @param WlcOrderList|dataList $obj {@see use-e-shop/classes/orderList[2].class.php}
     * @return string
     */
    protected function batchUpdateOrderStatus($msgstr, $order_res, $req, $id, $obj) {
        return $msgstr;
    }

    /**
     * Batch updates for completion status.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see function updateOrderData
     * @param string                $msgstr
     * @param array                 $order_res
     * @param array                 $req $_REQUEST
     * @param integer               $id
     * @param WlcOrderList|dataList $obj {@see use-e-shop/classes/orderList[2].class.php}
     * @return string
     */
    protected function batchUpdateOrderStatusCompletion($msgstr, $order_res, $req, $id, $obj) {
        return $msgstr;
    }

    /**
     * Batch updates for cancel status.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see function updateOrderData
     * @param string                $msgstr
     * @param array                 $order_res
     * @param array                 $req $_REQUEST
     * @param integer               $id
     * @param WlcOrderList|dataList $obj {@see use-e-shop/classes/orderList[2].class.php}
     * @return string
     */
    protected function batchUpdateOrderStatusCancel($msgstr, $order_res, $req, $id, $obj) {
        return $msgstr;
    }

    /**
     * Filter error log html.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $res  html to be displayed
     * @return string
     */
    public function filterErrorLogDI($res) {
        if (isset($_POST['mode'])) {
            if ($_POST['mode'] === 'get_settlement_error_log_detail') {
                if (strpos($res, $this->module->getPaymentName()) !== false) {
                    $res = $this->filterErrorLog($res);
                }
            }
        }
        return $res;
    }

    /**
     * Filter error log html.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $res  html to be displayed
     * @return string
     */
    protected function filterErrorLog($res) {
        return $res;
    }

    /**
     * Filters order list row column value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $detail
     * @param string $value
     * @param string $key
     * @param int    $order_id
     * @return string
     */
    public function filterOrderlistDetailValueDI($detail, $value, $key, $order_id) {
        if ($this->module->isOrderAssociated((int)$order_id)) {
            if ('wc_trans_id' === $key) {
                $detail = $this->filterTransactionIdRowColumnValue($detail, $value, $key, $order_id);
            }

            $detail = $this->filterOrderlistDetailValue($detail, $value, $key, $order_id);
        }

        return $detail;
    }

    /**
     * Filters order list transaction ID row column value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $detail
     * @param string $value
     * @param string $key
     * @param int    $order_id
     * @return string
     */
    protected function filterTransactionIdRowColumnValue($detail, $value, $key, $order_id) {
        return $detail;
    }

    /**
     * Filters order list row column value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $detail
     * @param string $value
     * @param string $key
     * @param int    $order_id
     * @return string
     */
    protected function filterOrderlistDetailValue($detail, $value, $key, $order_id) {
        return $detail;
    }

    /**
     * Returns order list transaction ID row column value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param TransactionState $state
     * @return string
     */
    public function getTransactionIdRowColumnHtml(TransactionState $state) {
        ob_start();
        ?>
        <td>
            <?php echo $state->getTransactionId(); ?>
            <span class="acting-status <?php echo $state->getCssClass(); ?>">
                <?php echo $state->getDisplayText(); ?>
            </span>
        </td>
        <?php
        $html = ob_get_clean();

        return $html;
    }
}
