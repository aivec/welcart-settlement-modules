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
            $json['amount'] = $this->getFormattedAmountWithSymbol();
        }

        return $json;
    }

    /**
     * Returns the formatted price
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global array $usces_settings
     * @global \usc_e_shop $usces
     * @return string
     */
    public function getFormattedAmountWithSymbol() {
        global $usces_settings, $usces;

        $ccToCmap = $usces_settings['currency'];

        $cc = null;
        if ($this->currencyCode !== 'JPY') {
            foreach ($ccToCmap as $countryCode => $list) {
                list($code) = $list;
                if ($this->currencyCode === $code) {
                    $cc = $countryCode;
                    break;
                }
            }
        } else {
            $cc = 'JP';
        }

        if ($cc === null) {
            return $this->amount . ' ' . $this->currencyCode;
        }

        $curCc = $usces->options['system']['currency'];
        // temporarily spoof
        $usces->options['system']['currency'] = $cc;
        $formatted = usces_crform($this->amount, false, true, 'return', true);
        // revert
        $usces->options['system']['currency'] = $curCc;

        return $formatted;
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
