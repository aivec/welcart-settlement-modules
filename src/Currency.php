<?php
namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;

/**
 * Settlement module currency validation model
 */
class Currency {

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
        'EUR',
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
        'EUR',
        'CZK',
        'DKK',
        'DJF',
        'DOP',
        'USD',
        'EUR',
        'ETB',
        'EUR',
        'USD',
        'EGP',
        'FJD',
        'EUR',
        'XAF',
        'EUR',
        'GHC',
        'EUR',
        'GTQ',
        'HNL',
        'HKD',
        'HUF',
        'ISK',
        'INR',
        'IDR',
        'EUR',
        'IQD',
        'IRR',
        'ILS',
        'EUR',
        'JPY',
        'JMD',
        'JOD',
        'KES',
        'KWD',
        'KAZ',
        'EUR',
        'CHF',
        'LAK',
        'EUR',
        'EUR',
        'MOP',
        'MKD',
        'MGA',
        'MYR',
        'EUR',
        'MUR',
        'MXN',
        'MVR',
        'EUR',
        'MNT',
        'MAD',
        'MMK',
        'EUR',
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
        'EUR',
        'USD',
        'QAR',
        'ROL',
        'RUB',
        'RWF',
        'SAR',
        'XOF',
        'SRB',
        'SGD',
        'EUR',
        'EUR',
        'SBD',
        'ZAR',
        'KRW',
        'SSP',
        'EUR',
        'LKR',
        'SDG',
        'SYP',
        'SEK',
        'CHF',
        'TWD',
        'TZS',
        'THB',
        'XOF',
        'TTD',
        'TND',
        'TRL',
        'UGX',
        'UAH',
        'AED',
        'GBP',
        'USD',
        'UYU',
        'VEB',
        'VND',
        'ZWD',
        'USD',
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
