<?php

namespace Aivec\Welcart\SettlementModules\Logging;

use JsonSerializable;
use Aivec\Welcart\SettlementModules\Utils;
use Aivec\ResponseHandler\GenericError;
use Aivec\Welcart\SettlementModules\Interfaces\TransactionState;

/**
 * Represents a transaction log to insert into `usces_log` table
 */
class TransactionLog implements JsonSerializable
{
    /**
     * An immutable tracking ID.
     *
     * This ID is used for grouping logs together for a particular order.
     *
     * @var string|int
     */
    protected $trackingId;

    /**
     * The transaction state of the order **after** a transaction was made
     *
     * @var TransactionState|null
     */
    protected $transactionState;

    /**
     * 取引ID
     *
     * @var string|int|null
     */
    protected $transactionId;

    /**
     * UNIX timestamp
     *
     * @var int
     */
    protected $timestamp;

    /**
     * 処理区分
     *
     * @var string
     */
    protected $actionType;

    /**
     * Only set if an error occured.
     *
     * @var GenericError|null
     */
    protected $error;

    /**
     * 処理結果
     *
     * @var string|int
     */
    protected $responseCode;

    /**
     * Amount for the transaction
     *
     * Not all transactions require amount to be set
     *
     * @var int|null
     */
    protected $amount;

    /**
     * Creates a `TransactionLog` instance for passing to `TransactionLogger`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string|int        $trackingId
     * @param string            $actionType
     * @param string|int        $responseCode
     * @param string|int|null   $transactionId
     * @param int|null          $amount
     * @param GenericError|null $error
     * @param int|null          $timestamp
     * @return void
     */
    public function __construct(
        $trackingId,
        $actionType,
        $responseCode,
        TransactionState $transactionState = null,
        $transactionId = null,
        $amount = null,
        GenericError $error = null,
        $timestamp = null
    ) {
        $this->trackingId = $trackingId;
        $this->actionType = $actionType;
        $this->responseCode = $responseCode;
        $this->transactionState = $transactionState;
        $this->transactionId = $transactionId;
        $this->amount = $amount;
        $this->error = $error;
        $this->timestamp = !empty($timestamp) ? $timestamp : time();
    }

    /**
     * Object shape for `json_encode`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function jsonSerialize() {
        return [
            'trackingId' => $this->trackingId,
            'actionType' => $this->actionType,
            'responseCode' => $this->responseCode,
            'transactionId' => $this->transactionId,
            'transactionState' => $this->transactionState,
            'amount' => $this->amount,
            'error' => $this->error,
            'timestamp' => $this->timestamp,
            'datetime' => $this->getLocalDateTime(),
        ];
    }

    /**
     * Returns a `Y-m-d H:i:s` formatted local date time string for the logs UNIX timestamp
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getLocalDateTime() {
        return Utils::getLocalDateTimeFromUnixTimestamp($this->timestamp);
    }

    /**
     * Returns action type text translation for a given action key
     *
     * Defaults to `$this->actionType`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getActionTypeText() {
        return $this->actionType;
    }

    /**
     * Sets transactionId value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string|int $transactionId
     * @return void
     */
    public function setTransactionId($transactionId) {
        $this->transactionId = $transactionId;
    }

    /**
     * Sets amount value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $amount
     * @return void
     */
    public function setAmount($amount) {
        $this->amount = $amount;
    }

    /**
     * Getter for `$trackingId`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string|int
     */
    public function getTrackingId() {
        return $this->trackingId;
    }

    /**
     * Getter for `$transactionId`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string|int|null
     */
    public function getTransactionId() {
        return $this->transactionId;
    }

    /**
     * Getter for `$timestamp`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return int
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Getter for `$actionType`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getActionType() {
        return $this->actionType;
    }

    /**
     * Getter for `$transactionState`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return TransactionState|null
     */
    public function getTransactionState() {
        return $this->transactionState;
    }

    /**
     * Getter for `$error`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return GenericError|null
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Getter for `$responseCode`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string|int
     */
    public function getResponseCode() {
        return $this->responseCode;
    }

    /**
     * Getter for `$amount`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return int|null
     */
    public function getAmount() {
        return $this->amount;
    }
}
