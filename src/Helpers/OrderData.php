<?php

namespace Aivec\Welcart\SettlementModules\Helpers;

use Aivec\Welcart\SettlementModules\Module;

/**
 * Utility methods for settlement modules
 */
class OrderData
{
    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Injects `Module` instance
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @return void
     */
    public function __construct(Module $module) {
        $this->module = $module;
    }

    /**
     * Returns order details page URL for a subscription order
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $order_id
     * @param int $member_id
     * @return string
     */
    public function getSubscriptionOrderDetailsPageLink($order_id, $member_id) {
        $queryvars = [
            'page' => 'usces_continue',
            'continue_action' => 'settlement_' . $this->module->getActingFlag(),
            'member_id' => $member_id,
            'order_id' => $order_id,
        ];
        return add_query_arg($queryvars, admin_url('admin.php'));
    }

    /**
     * Updates order_status column be replacing comma separated statuses.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global wpdb $wpdb
     * @param int    $order_id
     * @param string $flag  the new order status
     * @return boolean
     */
    public static function updateOrderReceipt($order_id, $flag) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'usces_order';

        if ('receipted' === $flag) {
            $mquery = $wpdb->prepare(
                "UPDATE $table_name SET order_status = 
                CASE 
                    WHEN LOCATE('noreceipt', order_status) > 0 THEN REPLACE(order_status, 'noreceipt', 'receipted') 
                    WHEN LOCATE('pending', order_status) > 0 THEN REPLACE(order_status, 'pending', 'receipted') 
                    WHEN LOCATE('receipted', order_status) > 0 THEN order_status 
                    ELSE CONCAT('receipted,', order_status) 
                END 
                WHERE ID = %d",
                $order_id
            );
            $res = $wpdb->query($mquery);
        } elseif ('noreceipt' === $flag) {
            $mquery = $wpdb->prepare(
                "UPDATE $table_name SET order_status = 
                CASE 
                    WHEN LOCATE('receipted', order_status) > 0 THEN REPLACE(order_status, 'receipted', 'noreceipt') 
                    WHEN LOCATE('pending', order_status) > 0 THEN REPLACE(order_status, 'pending', 'noreceipt') 
                    WHEN LOCATE('noreceipt', order_status) > 0 THEN order_status 
                    ELSE CONCAT('noreceipt,', order_status) 
                END 
                WHERE ID = %d",
                $order_id
            );
            $res = $wpdb->query($mquery);
        }
        return $res;
    }
}
