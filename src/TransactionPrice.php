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
            'rawAmount' => $this->getRawAmount(),
            'currencyCode' => $this->currencyCode,
        ];

        if ($this->serializeTargetIsDisplay()) {
            $json['amount'] = $this->getFormattedAmountWithSymbol();
        }

        return $json;
    }

    /**
     * Returns the formatted price
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see getFormattedAmountWithSymbolByCurrencyCode()
     * @return string
     */
    public function getFormattedAmountWithSymbol() {
        return self::getFormattedAmountWithSymbolByCurrencyCode($this->currencyCode, $this->amount);
    }

    /**
     * Returns the formatted price
     *
     * The amount is formatted with a decimal point and thousands separator
     * depending on whether the currency code requires it or not.
     *
     * Currency symbol display is determined based on the following:
     * - If the currently selected country in Welcart corresponds to the
     * currency code, the currency symbol of that country will be appended/prepended
     * to the formatted amount
     * - If the currently selected country in Welcart **does not** correspond
     * to the currency code, the three letter currency code will be appended/prepended
     * to the formatted amount
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global array $usces_settings
     * @global \usc_e_shop $usces
     * @param string    $currencyCode
     * @param int|float $amount
     * @return string
     */
    public static function getFormattedAmountWithSymbolByCurrencyCode($currencyCode, $amount) {
        global $usces_settings, $usces;

        $cc = $usces->options['system']['currency'];
        $code = isset($usces_settings['currency'][$cc][0]) ? $usces_settings['currency'][$cc][0] : null;
        if ($code !== $currencyCode) {
            return $currencyCode . self::getFormattedAmountByCurrencyCode($currencyCode, $amount);
        }

        return usces_crform($amount, false, true, 'return', true);
    }

    /**
     * Returns the formatted amount based on the currency code
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see getFormattedAmountByCurrencyCode()
     * @return string
     */
    public function getFormattedAmount() {
        return self::getFormattedAmountByCurrencyCode($this->currencyCode, $this->amount);
    }

    /**
     * Returns the formatted amount based on the currency code
     *
     * The amount is formatted with a decimal point and thousands separator
     * depending on whether the currency code requires it or not.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string    $currencyCode
     * @param int|float $amount
     * @return string
     */
    public static function getFormattedAmountByCurrencyCode($currencyCode, $amount) {
        list($decimal, $point, $seperator) = Currency::ISO_4217_DISPLAY_META_MAP[$currencyCode];
        return number_format((double)$amount, $decimal, $point, $seperator);
    }

    /**
     * Returns the raw amount
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see getRawAmountByCurrencyCode()
     * @return string
     */
    public function getRawAmount() {
        return self::getRawAmountByCurrencyCode($this->currencyCode, $this->amount);
    }

    /**
     * Returns the raw amount
     *
     * Depending on the currency code, the raw amount may or may not contain
     * decimal digits but will not contain a thousands separator.
     *
     * If the amount passed to this method is not in the proper format for the
     * currency code, this method will append/strip decimal digits accordingly and
     * return the result.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string    $currencyCode
     * @param int|float $amount
     * @return string
     */
    public static function getRawAmountByCurrencyCode($currencyCode, $amount) {
        list($decimal, $point) = Currency::ISO_4217_DISPLAY_META_MAP[$currencyCode];
        return number_format((double)$amount, $decimal, $point, '');
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
}
