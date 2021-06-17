<?php

namespace Aivec\Welcart\SettlementModules\Logging;

/**
 * Represents a row from Welcart's `*_usces_log` table for a `TransactionLog`
 */
class TransactionLogUscesRow
{
    /**
     * Log row primary key ID
     *
     * @var int
     */
    private $id;

    /**
     * MySQL DATETIME string (Y-m-d H:i:s) for log row
     *
     * @var string
     */
    private $datetime;

    /**
     * Transaction log instance
     *
     * @var TransactionLog
     */
    private $log;

    /**
     * Key for grouping logs of the same order
     *
     * @var int|string
     */
    private $log_key;

    /**
     * The type of log.
     *
     * Eg. `acting_amazonpay_transaction_history`
     *
     * @var string
     */
    private $log_type;

    /**
     * Constructs a transaction log row
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int            $id
     * @param string         $datetime
     * @param TransactionLog $log
     * @param int|string     $log_key
     * @param string         $log_type
     * @return void
     */
    public function __construct($id, $datetime, TransactionLog $log, $log_key, $log_type) {
        $this->id = (int)$id;
        $this->datetime = (string)$datetime;
        $this->log = $log;
        $this->log_key = $log_key;
        $this->log_type = (string)$log_type;
    }

    /**
     * Getter for `$this->id`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Getter for `$this->datetime`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getDatetime() {
        return $this->datetime;
    }

    /**
     * Getter for `$this->log`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return TransactionLog
     */
    public function getLog() {
        return $this->log;
    }

    /**
     * Getter for `$this->log_key`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return int|string
     */
    public function getLogKey() {
        return $this->log_key;
    }

    /**
     * Getter for `$this->log_type`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getLogType() {
        return $this->log_type;
    }
}
