<?php

namespace Aivec\Welcart\SettlementModules\Logging;

use Aivec\Welcart\SettlementModules\Module;

/**
 * Methods for adding and retrieving logs
 */
class TransactionLogger
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const DEFAULT_TIME_ZONE = 'Asia/Tokyo';

    /**
     * Settlement module object
     *
     * @var Module
     */
    public $module;

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
     * 決済履歴ログ登録
     *
     * Inserts record containing meta data of a settlement module API response
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param TransactionLog $log
     * @param string|null    $logType Defaults to `$this->module->getTransactionHistoryLogType()` value
     * @return int|false The number of rows inserted, or false on error.
     */
    public function addLog(TransactionLog $log, $logType = null) {
        return self::addLogByType($log, !empty($logType) ? $logType : $this->module->getTransactionHistoryLogType());
    }

    /**
     * 決済履歴ログ登録
     *
     * Inserts record containing meta data of a settlement module API response
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \wpdb $wpdb
     * @param TransactionLog $log
     * @param string         $logType
     * @return int|false The number of rows inserted, or false on error.
     */
    public static function addLogByType(TransactionLog $log, $logType) {
        global $wpdb;

        $log->setSerializeTargetToDb();

        $log_table = $wpdb->prefix . 'usces_log';
        $res = $wpdb->insert(
            $log_table,
            [
                'datetime' => $log->getLocalDateTime(),
                'log' => json_encode($log),
                'log_type' => $logType,
                'log_key' => $log->getTrackingId(),
            ],
            ['%s', '%s', '%s', '%s']
        );

        return $res;
    }

    /**
     * 決済履歴ログ取得
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string      $log_key
     * @param string|null $logType Defaults to `$this->module->getTransactionHistoryLogType()` value
     * @return array
     */
    public function getSettlementHistoryLog($log_key, $logType = null) {
        return self::getHistoryLogByKeyAndType($log_key, !empty($logType) ? $logType : $this->module->getTransactionHistoryLogType());
    }

    /**
     * 決済履歴ログ取得
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \wpdb $wpdb
     * @param string $log_key
     * @param string $logType
     * @return array
     */
    public static function getHistoryLogByKeyAndType($log_key, $logType) {
        global $wpdb;

        $log_table = $wpdb->prefix . 'usces_log';
        $query = $wpdb->prepare(
            "SELECT *
            FROM {$log_table}
            WHERE `log_type` = %s
            AND `log_key` = %s
            ORDER BY datetime DESC",
            $logType,
            $log_key
        );
        $log_data = $wpdb->get_results($query, ARRAY_A);

        return !empty($log_data) ? $log_data : [];
    }
}
