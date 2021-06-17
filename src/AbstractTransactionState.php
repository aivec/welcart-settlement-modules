<?php

namespace Aivec\Welcart\SettlementModules;

abstract class AbstractTransactionState implements Interfaces\TransactionState
{
    /**
     * Current state
     *
     * @var string
     */
    private $state;

    /**
     * Transaction ID
     *
     * @var string|int|null
     */
    private $transactionId;

    /**
     * Constructs a `TransactionState` instance
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string          $state
     * @param string|int|null $transactionId
     * @return void
     */
    public function __construct($state, $transactionId = null) {
        $this->state = (string)$state;
        $this->transactionId = $transactionId;
    }

    /**
     * Returns JSON array when `json_encode` is invoked on an instance of this class
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function jsonSerialize() {
        return [
            'state' => $this->state,
            'transactionId' => $this->getTransactionId(),
            'displayText' => $this->getDisplayText(),
            'cssClass' => $this->getCssClass(),
        ];
    }

    /**
     * Getter for `state`
     *
     * Example: `captured`, `canceled`, etc.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Getter for `transactionId`
     *
     * Returns the ID of a settlement module transaction. Not always applicable.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string|int|null
     */
    public function getTransactionId() {
        return $this->transactionId;
    }

    /**
     * Returns human-readable text representation of current state
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    abstract public function getDisplayText();

    /**
     * Returns CSS class for the current state
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    abstract public function getCssClass();
}
