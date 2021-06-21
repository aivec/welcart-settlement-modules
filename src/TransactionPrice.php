<?php

namespace Aivec\Welcart\SettlementModules;

use JsonSerializable;

/**
 * Model for transaction price
 */
class TransactionPrice implements JsonSerializable
{
    /**
     * Transaction amount
     *
     * @var string
     */
    private $amount;

    /**
     * Transaction currency code in ISO 4217 format. Example: USD
     *
     * @var string
     */
    private $currencyCode;

    /**
     * Creates `price` type
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int|string $amount
     * @param string     $currencyCode
     * @return void
     */
    public function __construct($amount, $currencyCode) {
        $this->amount = $amount;
        $this->currencyCode = (string)$currencyCode;
    }

    /**
     * Returns array for `json_encode`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function jsonSerialize() {
        return [
            'rawAmount' => $this->amount,
            'amount' => usces_crform($this->amount, false, true, 'return', true),
            'currencyCode' => $this->currencyCode,
            'currencySymbol' => $this->getCurrencySymbol(),
        ];
    }

    /**
     * Getter for `amount`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * Getter for `currencyCode`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getCurrencyCode() {
        return $this->currencyCode;
    }

    /**
     * Returns human-readable currency symbol for the `currencyCode`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getCurrencySymbol() {
        return __($this->currencyCode, 'usces');
    }
}
