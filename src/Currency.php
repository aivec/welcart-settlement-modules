<?php

namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;

/**
 * Settlement module currency validation model
 */
class Currency
{
    /**
     * All ISO 4217 currency codes supported by Welcart
     */
    const ISO_4217_CURRENCY_CODE_LIST = [
        'DZD',
        'ARS',
        'AUD',
        'EUR',
        'AZN',
        'BHD',
        'BBD',
        'BDT',
        'BYR',
        'BTN',
        'BWP',
        'BND',
        'BRL',
        'BGN',
        'KHR',
        'CAD',
        'CLP',
        'CNY',
        'COP',
        'CRC',
        'XOF',
        'HRK',
        'CUC',
        'CZK',
        'DKK',
        'DJF',
        'DOP',
        'ETB',
        'EGP',
        'FJD',
        'XAF',
        'GHC',
        'GTQ',
        'HNL',
        'HKD',
        'HUF',
        'ISK',
        'INR',
        'IDR',
        'IQD',
        'IRR',
        'ILS',
        'JPY',
        'JMD',
        'JOD',
        'KES',
        'KWD',
        'KAZ',
        'CHF',
        'LAK',
        'MOP',
        'MKD',
        'MGA',
        'MYR',
        'MUR',
        'MXN',
        'MVR',
        'MNT',
        'MAD',
        'MMK',
        'NPR',
        'NZD',
        'NGN',
        'NOK',
        'CFP',
        'OMR',
        'PAB',
        'PKR',
        'PKG',
        'PYG',
        'PEN',
        'PHP',
        'PLN',
        'QAR',
        'ROL',
        'RUB',
        'RWF',
        'SAR',
        'SRB',
        'SGD',
        'SBD',
        'ZAR',
        'KRW',
        'SSP',
        'LKR',
        'SDG',
        'SYP',
        'SEK',
        'TWD',
        'TZS',
        'THB',
        'TTD',
        'TND',
        'TRL',
        'UGX',
        'UAH',
        'AED',
        'GBP',
        'UYU',
        'VEB',
        'VND',
        'ZWD',
        'USD',
    ];

    /**
     * Map of ISO 4217 currency codes to money display info.
     *
     * The info list contains, in the following order,
     * - number of decimal digits
     * - decimal point symbol
     * - thousands separator symbol
     */
    const ISO_4217_DISPLAY_META_MAP = [
        'DZD' => [2, '.', ','],
        'ARS' => [2, '.', ','],
        'AUD' => [2, '.', ','],
        'EUR' => [2, '.', ','],
        'AZN' => [2, '.', ','],
        'BHD' => [2, '.', ','],
        'BBD' => [2, '.', ','],
        'BDT' => [2, '.', ','],
        'BYR' => [2, '.', ','],
        'BTN' => [2, '.', ','],
        'BWP' => [2, '.', ','],
        'BND' => [2, '.', ','],
        'BRL' => [2, '.', ','],
        'BGN' => [2, '.', ','],
        'KHR' => [2, '.', ','],
        'CAD' => [2, '.', ','],
        'CLP' => [2, '.', ','],
        'CNY' => [2, '.', ','],
        'COP' => [2, '.', ','],
        'CRC' => [2, '.', ','],
        'XOF' => [2, '.', ','],
        'HRK' => [2, '.', ','],
        'CUC' => [2, '.', ','],
        'CZK' => [2, '.', ','],
        'DKK' => [2, '.', ','],
        'DJF' => [2, '.', ','],
        'DOP' => [2, '.', ','],
        'ETB' => [2, '.', ','],
        'EGP' => [2, '.', ','],
        'FJD' => [2, '.', ','],
        'XAF' => [2, '.', ','],
        'GHC' => [2, '.', ','],
        'GTQ' => [2, '.', ','],
        'HNL' => [2, '.', ','],
        'HKD' => [2, '.', ','],
        'HUF' => [2, '.', ','],
        'ISK' => [2, '.', ','],
        'INR' => [2, '.', ','],
        'IDR' => [2, '.', ','],
        'IQD' => [2, '.', ','],
        'IRR' => [2, '.', ','],
        'ILS' => [2, '.', ','],
        'JPY' => [0, '.', ','],
        'JMD' => [2, '.', ','],
        'JOD' => [2, '.', ','],
        'KES' => [2, '.', ','],
        'KWD' => [2, '.', ','],
        'KAZ' => [2, '.', ','],
        'CHF' => [2, '.', ','],
        'LAK' => [2, '.', ','],
        'MOP' => [2, '.', ','],
        'MKD' => [2, '.', ','],
        'MGA' => [2, '.', ','],
        'MYR' => [2, '.', ','],
        'MUR' => [2, '.', ','],
        'MXN' => [2, '.', ','],
        'MVR' => [2, '.', ','],
        'MNT' => [2, '.', ','],
        'MAD' => [2, '.', ','],
        'MMK' => [2, '.', ','],
        'NPR' => [2, '.', ','],
        'NZD' => [2, '.', ','],
        'NGN' => [2, '.', ','],
        'NOK' => [2, '.', ','],
        'CFP' => [2, '.', ','],
        'OMR' => [2, '.', ','],
        'PAB' => [2, '.', ','],
        'PKR' => [2, '.', ','],
        'PKG' => [2, '.', ','],
        'PYG' => [2, '.', ','],
        'PEN' => [2, '.', ','],
        'PHP' => [2, '.', ','],
        'PLN' => [2, '.', ','],
        'QAR' => [2, '.', ','],
        'ROL' => [2, '.', ','],
        'RUB' => [2, '.', ','],
        'RWF' => [2, '.', ','],
        'SAR' => [2, '.', ','],
        'SRB' => [2, '.', ','],
        'SGD' => [2, '.', ','],
        'SBD' => [2, '.', ','],
        'ZAR' => [2, '.', ','],
        'KRW' => [0, '.', ','],
        'SSP' => [0, '.', ','],
        'LKR' => [2, '.', ','],
        'SDG' => [2, '.', ','],
        'SYP' => [2, '.', ','],
        'SEK' => [2, '.', ','],
        'TWD' => [0, '.', ','],
        'TZS' => [2, '.', ','],
        'THB' => [2, '.', ','],
        'TTD' => [2, '.', ','],
        'TND' => [2, '.', ','],
        'TRL' => [2, '.', ','],
        'UGX' => [2, '.', ','],
        'UAH' => [2, '.', ','],
        'AED' => [2, '.', ','],
        'GBP' => [2, '.', ','],
        'UYU' => [2, '.', ','],
        'VEB' => [2, '.', ','],
        'VND' => [2, '.', ','],
        'ZWD' => [2, '.', ','],
        'USD' => [2, '.', ','],
    ];

    /**
     * The settlement `Module` instance
     *
     * @var Module
     */
    private $module;

    /**
     * Array of valid currencies in ISO 4217 format. If empty, currency checks are
     * not performed.
     *
     * @var array
     */
    private $valid_currencies;

    /**
     * Instantiates a currency object for a `Module`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module the settlement module instance
     * @param array  $valid_currencies array of valid currencies in ISO 4217 format. Should be left empty
     *                                 if you want to support all currencies.
     * @throws InvalidArgumentException Thrown if any currency isn't valid.
     * @return void
     */
    public function __construct(Module $module, array $valid_currencies) {
        $this->module = $module;
        $this->valid_currencies = $valid_currencies;
        if (!empty($valid_currencies)) {
            $this->validateCurrenciesFormat($valid_currencies);
        }
    }

    /**
     * Registers `admin_notices` for displaying an error message in case an invalid
     * currency is selected
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function init() {
        if (!empty($this->valid_currencies) && defined('USCES_VERSION')) {
            add_action('admin_notices', [$this, 'invalidCurrencyAdminErrorMessage']);
        }
    }

    /**
     * Displays admin error message if the selected currency code is not
     * supported by this `Module`
     *
     * The only reason we check the currency by hand instead of just calling
     * `usces_crcode()` is because the code returned by `usces_crcode()` is updated
     * after the `admin_notices` hook fires, so even if the user picks an invalid
     * currency code, they have to refresh the page again before the `admin_notices`
     * error message will appear.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function invalidCurrencyAdminErrorMessage() {
        global $usces_settings;

        $active_currency_code = usces_crcode('return');
        if (isset($_POST['usces_locale_option_update'])) {
            $cr = isset($_POST['currency']) && 'others' !== strtolower($_POST['currency']) ? $_POST['currency'] : usces_get_base_country();
            list($code) = $usces_settings['currency'][$cr];
            $active_currency_code = $code;
        }

        if (in_array($active_currency_code, $this->valid_currencies, true)) {
            return;
        }
        ?>
        <div class="message error">
            <p>
                <?php echo sprintf(
                    // translators: 1. the invalid currency code, 2. settlement module name
                    __('The currency code %1$s is not supported by %2$s.', 'smodule'),
                    $active_currency_code,
                    $this->module->getPaymentName()
                ) ?>
            </p>
        </div>
        <?php
    }

    /**
     * Validates form of currency codes in `$valid_currencies` array
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $valid_currencies
     * @return void
     * @throws InvalidArgumentException Thrown if any currency isn't valid.
     */
    private function validateCurrenciesFormat(array $valid_currencies) {
        foreach ($valid_currencies as $currency) {
            if (!in_array($currency, self::ISO_4217_CURRENCY_CODE_LIST, true)) {
                throw new InvalidArgumentException(
                    "{$currency} is not a valid currency. The format is not ISO 4217, or Welcart doesn't support it"
                );
            }
        }
    }

    /**
     * Returns `true` if currency is valid for the settlement module, `false` otherwise
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return boolean
     */
    public function isCurrencySupported() {
        if (empty($this->valid_currencies)) {
            return true;
        }

        return in_array(usces_crcode('return'), $this->valid_currencies, true);
    }

    /**
     * Getter for valid_currencies
     *
     * @return array
     */
    public function getValidCurrencies() {
        return $this->valid_currencies;
    }
}
