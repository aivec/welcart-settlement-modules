<?php

namespace Aivec\Welcart\SettlementModules;

/**
 * Utility methods for settlement modules
 */
class Utils
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const DEFAULT_TIME_ZONE = 'Asia/Tokyo';

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

    /**
     * 継続課金会員データ取得
     *
     * @global \wpdb $wpdb
     * @param int $order_id
     * @param int $member_id
     * @return array
     */
    public static function getSubscriptionOrderData($order_id, $member_id) {
        global $wpdb;

        $continuation_table_name = $wpdb->prefix . 'usces_continuation';
        $query = $wpdb->prepare(
            "SELECT 
			`con_acting` AS `acting`, 
			`con_order_price` AS `order_price`, 
			`con_price` AS `price`, 
			`con_next_charging` AS `chargedday`, 
			`con_next_contracting` AS `contractedday`, 
			`con_startdate` AS `startdate`, 
			`con_status` AS `status` 
			FROM {$continuation_table_name} 
			WHERE con_order_id = %d AND con_member_id = %d",
            $order_id,
            $member_id
        );
        $data = $wpdb->get_row($query, ARRAY_A);
        return $data;
    }

    /**
     * Returns a `Y-m-d H:i:s` formatted local date time string given a UNIX timestamp
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $unixt
     * @return string
     */
    public static function getLocalDateTimeFromUnixTimestamp($unixt) {
        $timezone = new \DateTimeZone(self::DEFAULT_TIME_ZONE);
        if (function_exists('wp_timezone')) {
            $timezone = wp_timezone();
        }

        return (new \DateTime('@' . $unixt))->setTimezone($timezone)->format(self::DATETIME_FORMAT);
    }
}
