<?php

namespace Aivec\Welcart\SettlementModules\Helpers;

/**
 * Utility methods for settlement modules
 */
class OrderData
{
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
