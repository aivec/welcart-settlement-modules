<?php

namespace Aivec\Welcart\SettlementModules\Interfaces;

interface TransactionState
{
    /**
     * Returns human-readable text representation of current state
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getDisplayText();

    /**
     * Returns CSS class for the current state
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getCssClass();

    /**
     * Getter for `state`
     *
     * Example: `captured`, `canceled`, etc.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getState();

    /**
     * Getter for `transactionId`
     *
     * Returns the ID of a settlement module transaction
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string|int
     */
    public function getTransactionId();
}
