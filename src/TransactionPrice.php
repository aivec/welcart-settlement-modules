<?php

namespace Aivec\Welcart\SettlementModules;

use JsonSerializable;
use Aivec\Welcart\SettlementModules\Interfaces\SerializeTargetSettable;
use Aivec\Welcart\SettlementModules\Helpers\SerializeTargetSetter;

/**
 * Model for transaction price
 */
class TransactionPrice implements JsonSerializable, SerializeTargetSettable
{
    use SerializeTargetSetter;

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
        $json = [
            'rawAmount' => $this->amount,
            'currencyCode' => $this->currencyCode,
        ];

        if ($this->serializeTargetIsDisplay()) {
            $json['amount'] = usces_crform($this->amount, false, true, 'return', true);
            $json['currencySymbol'] = $this->getCurrencySymbol();
        }

        return $json;
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
        return __($this->currencyCode, 'usces'); // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralText
    }
}
