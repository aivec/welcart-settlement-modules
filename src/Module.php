<?php

namespace Aivec\Welcart\SettlementModules;

use Aivec\CptmClient\Client;
use InvalidArgumentException;

/**
 * Settlement Module registration
 */
class Module
{
    const SETTINGS_KEY = 'acting_settings';
    const ACTING_FLAG_ORDER_META_KEY = 'acting_flag';

    /**
     * Front facing default name of the `Module`
     *
     * @var string
     */
    private $payment_name;

    /**
     * Acting string for the `Module`
     *
     * @var string
     */
    private $acting;

    /**
     * Acting flag string for the `Module`
     *
     * @var string
     */
    private $acting_flag;

    /**
     * Array of valid divisions and payment types for the `Module`
     *
     * Divisions:
     * - 'shipped' => 物販
     * - 'data' => コンテンツファイル
     * - 'service' => サービス
     *
     * Charge types:
     * - 'once' => 通常課金
     * - 'continue' => 継続課金
     * - 'regular' => 通常購入（WCEX Auto Delivery）
     *
     * simply omit any keys that represent a division or charge type that is not supported
     * by the `Module`.
     *
     * Example:
     * ```
     * [
     *      'shipped' => [
     *          'once',
     *          'continue',
     *          'regular',
     *      ],
     *      'data' => [
     *          'once',
     *          'continue',
     *          'regular',
     *      ],
     *      'service' => [
     *          'once',
     *          'continue',
     *          'regular',
     *      ],
     * ]
     * ```
     *
     * @var array
     */
    private $valid_divisions;

    /**
     * Currency object
     *
     * Contains convenience methods for currency validation, etc.
     *
     * @var Currency
     */
    private $currency;

    /**
     * True if `Module` provides support for `wcex_multi_shipping`, false otherwise
     *
     * @var boolean
     */
    private $multi_shipping_support;

    /**
     * Commercial Plugin/Theme Manager `Client` instance or `null` if not required
     *
     * @var Client|null
     */
    private $cptmc;

    /**
     * If true, displays option on settlement settings page for determining
     * payment capture type (処理区分).
     *
     * - 'after_purchase' => 与信
     * - 'on_purchase' => 与信売上計上
     *
     * @var boolean
     */
    private $capture_payment_opt_support;

    /**
     * Initializes a settlement module.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string      $payment_name Front facing default name of the `Module`
     * @param string      $acting Acting string for the `Module`
     * @param string      $acting_flag Acting flag string for the `Module`
     * @param array       $valid_divisions Array of valid divisions and payment types for the `Module`. Refer
     *                                     to the member var doc comment for more details
     * @param array       $valid_currencies array of valid currencies in ISO 4217 format. Leave the array empty
     *                                      if you want to support all currencies. Default: empty array
     * @param boolean     $multi_shipping_support `true` if `Module` provides support for `wcex_multi_shipping`,
     *                                            `false` otherwise. Default: `false`
     * @param Client|null $cptmc Commercial Plugin/Theme Manager `Client` instance or `null` if not required
     * @param boolean     $capture_payment_opt_support If true, displays option on settlement settings
     *                                                 page for determining payment capture type (処理区分)
     * @return void
     */
    public function __construct(
        $payment_name,
        $acting,
        $acting_flag,
        array $valid_divisions = [
            'shipped' => ['once', 'regular'],
            'service' => ['once', 'regular'],
        ],
        array $valid_currencies = [],
        $multi_shipping_support = false,
        Client $cptmc = null,
        $capture_payment_opt_support = false
    ) {
        $mopath = __DIR__ . '/languages/smodule-' . get_locale() . '.mo';
        if (file_exists($mopath)) {
            load_textdomain('smodule', $mopath);
        } else {
            load_textdomain('smodule', __DIR__ . '/languages/smodule-en.mo');
        }

        $this->validateDivisions($valid_divisions);
        $this->payment_name = $payment_name;
        $this->acting = $acting;
        $this->acting_flag = $acting_flag;
        $this->valid_divisions = $valid_divisions;
        $this->multi_shipping_support = $multi_shipping_support;
        $this->cptmc = $cptmc;
        $this->capture_payment_opt_support = $capture_payment_opt_support;
        $this->currency = new Currency($this, $valid_currencies);
        $this->currency->init();
    }

    /**
     * Validates form of divisions array
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @throws InvalidArgumentException Thrown if `$valid_divisions` is malformed.
     * @param array $valid_divisions
     * @return void
     */
    private function validateDivisions(array $valid_divisions) {
        $nodivisions = true;
        foreach ($valid_divisions as $division => $charge_types) {
            if ($division === 'shipped' || $division === 'data' || $division === 'service') {
                $nodivisions = false;
                $nochargetypes = true;
                if (!is_array($charge_types)) {
                    throw new InvalidArgumentException('charge types must be an array');
                }
                foreach ($charge_types as $pt) {
                    if ($pt === 'once' || $pt === 'continue' || $pt === 'regular') {
                        $nochargetypes = false;
                    }
                }
                if ($nochargetypes === true) {
                    throw new InvalidArgumentException(
                        "division '{$division}' does not contain any valid charge types"
                    );
                }
            }
        }
        if ($nodivisions === true) {
            throw new InvalidArgumentException(
                "valid_divisions must contain at least one of 'shipped', 'service', or 'data'"
            );
        }
    }

    /**
     * Returns true if settlement module can be used
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return boolean
     */
    public function ready() {
        $ready = $this->currency->isCurrencySupported();

        return $ready;
    }

    /**
     * Returns acting options for the settlement module
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @throws InvalidArgumentException Thrown if $payment_capture_type is malformed.
     * @global \usc_e_shop $usces
     * @return array
     */
    public function getActingOpts() {
        global $usces;

        $acting_opts
            = isset($usces->options[self::SETTINGS_KEY][$this->acting])
            ? $usces->options[self::SETTINGS_KEY][$this->acting]
            : [];

        if ($this->capture_payment_opt_support === true) {
            $type = $this->filterDefaultPaymentCaptureType('on_purchase');
            if ($type !== 'on_purchase' && $type !== 'after_purchase') {
                $type = 'on_purchase';
            }
            $acting_opts['payment_capture_type'] = isset($acting_opts['payment_capture_type']) ? $acting_opts['payment_capture_type'] : $type;
        }
        $type = $this->filterDefaultRecurringPaymentCaptureType('on_purchase');
        if ($type !== 'on_purchase' && $type !== 'after_purchase') {
            $type = 'on_purchase';
        }
        $acting_opts['recurring_payment_capture_type'] = isset($acting_opts['recurring_payment_capture_type']) ? $acting_opts['recurring_payment_capture_type'] : $type;
        $acting_opts['auto_settlement_mail'] = isset($acting_opts['auto_settlement_mail']) ? $acting_opts['auto_settlement_mail'] : 'on';
        $acting_opts['activate'] = isset($acting_opts['activate']) ? $acting_opts['activate'] : 'off';
        $acting_opts['sandbox'] = isset($acting_opts['sandbox']) ? $acting_opts['sandbox'] : true;
        $acting_opts = $this->filterActingOpts($acting_opts);

        return $acting_opts;
    }

    /**
     * Returns `true` if `Module` is set to sandbox mode, `false` otherwise
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function isSandboxMode() {
        return $this->getActingOpts()['sandbox'];
    }

    /**
     * Returns `true` if `payment_capture_type` is `on_purchase`. Returns
     * `true` by default if capture settings aren't available
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function captureOnPurchase() {
        if ($this->capture_payment_opt_support === false) {
            return true;
        }

        return $this->getActingOpts()['payment_capture_type'] === 'on_purchase';
    }

    /**
     * Returns `true` if `payment_capture_type` is `after_purchase`. Returns
     * `true` by default if capture settings aren't available
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function captureAfterPurchase() {
        if ($this->capture_payment_opt_support === false) {
            return true;
        }

        return $this->getActingOpts()['payment_capture_type'] === 'after_purchase';
    }

    /**
     * Returns `true` if `recurring_payment_capture_type` is `on_purchase`.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function recurringPaymentCaptureOnCharge() {
        return $this->getActingOpts()['recurring_payment_capture_type'] === 'on_purchase';
    }

    /**
     * Returns `true` if `recurring_payment_capture_type` is `after_purchase`.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function recurringPaymentCaptureAfterCharge() {
        return $this->getActingOpts()['recurring_payment_capture_type'] === 'after_purchase';
    }

    /**
     * Updates settlement module options
     *
     * This is a convenience method for updating values in the options array returned
     * by {@see Factory::settlementUpdate()}.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $opts
     * @return void
     */
    public function updateActingOpts(array $opts) {
        $options = get_option('usces');
        $options[self::SETTINGS_KEY][$this->acting] = $opts;
        update_option('usces', $options);
    }

    /**
     * Checks if settlement module is activated
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return boolean
     */
    public function isModuleActivated() {
        $payment_methods = usces_get_system_option('usces_payment_method', 'sort');
        $module = [];
        foreach ($payment_methods as $index => $values) {
            if ($values['settlement'] === $this->acting_flag) {
                $module = $values;
            }
        }
        $options = get_option('usces');
        if (isset($options[self::SETTINGS_KEY][$this->acting]) && !empty($module)) {
            if (
                $options[self::SETTINGS_KEY][$this->acting]['activate'] === 'on'
                && $module['use'] === 'activate'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the settlement module can process all items in the cart.
     *
     * If even *ONE* item contains a division or charge type that is not valid for this
     * module, `false` will be returned, otherwise `true` is returned.
     *
     * **WARNING**: if the module supports recurring payments and the cart contains a
     * subscription item but `wcex_dlseller` is not activated, `false` will be returned.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @return bool
     */
    public function canProcessCart() {
        global $usces;

        if (!usces_is_cart()) {
            return false;
        }
        if (!$this->isModuleActivated()) {
            return false;
        }

        $cart = $usces->cart->get_cart();
        if (is_array($cart)) {
            foreach ($cart as $item) {
                // check if division of item is supported by this Module
                $item_division = 'shipped';
                if (version_compare(USCES_VERSION, '2.7-beta', '>=')) {
                    $product = wel_get_product($item['post_id']);
                    $item_division = $product['item_division'];
                } else {
                    $item_division = get_post_meta($item['post_id'], '_item_division', true);
                }
                $division = empty($item_division) ? 'shipped' : $item_division;
                if (!array_key_exists($division, $this->valid_divisions)) {
                    return false;
                }

                // check if charge type of item is supported by this Module
                $item_charge_type = $usces->getItemChargingType($item['post_id']);
                if (!defined('WCEX_DLSELLER') && $item_charge_type === 'continue') {
                    return false;
                }
                $charge_type = empty($item_charge_type) ? 'once' : $item_charge_type;
                if (!in_array($charge_type, $this->valid_divisions[$division], true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns `true` if the module can process a dlseller subscription order, `false` otherwise
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function canHandleSubscriptionOrders() {
        foreach ($this->valid_divisions as $division => $charge_types) {
            if (in_array('continue', $charge_types, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the payment method name which was entered in by the store admin
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getPaymentNameByActingFlag() {
        $payments = usces_get_system_option('usces_payment_method', 'sort');
        foreach ($payments as $pmethod) {
            $acting_flag = 'acting' === $pmethod['settlement'] ? $pmethod['module'] : $pmethod['settlement'];
            if ($acting_flag === $this->getActingFlag()) {
                return $pmethod['name'];
            }
        }

        return $this->getPaymentName();
    }

    /**
     * Returns `true` if the given order_id is associated with the settlement module
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @param int $order_id
     * @return bool
     */
    public function isOrderAssociated($order_id) {
        global $usces;

        $order_acting_flag = $usces->get_order_meta_value(self::ACTING_FLAG_ORDER_META_KEY, $order_id);
        if (!empty($order_acting_flag)) {
            if ($order_acting_flag === $this->getActingFlag()) {
                return true;
            }

            return false;
        }
        // fallback for old modules that don't save the acting_flag
        $order_data = $usces->get_order_data($order_id, 'direct');
        if (empty($order_data)) {
            return false;
        }
        $payment = usces_get_payments_by_name($order_data['order_payment_name']);
        if (!isset($payment['settlement'])) {
            return false;
        }
        if ($payment['settlement'] === $this->getActingFlag()) {
            return true;
        }

        return false;
    }

    /**
     * Filter the default payment capture type (処理区分)
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $type
     * @return string
     */
    protected function filterDefaultPaymentCaptureType($type) {
        return $type;
    }

    /**
     * Filter the default payment capture type for recurring payments (処理区分)
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $type
     * @return string
     */
    protected function filterDefaultRecurringPaymentCaptureType($type) {
        return $type;
    }

    /**
     * Filter for acting opts array. Should be extended if additional options need
     * to be added
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $acting_opts
     * @return array
     */
    protected function filterActingOpts($acting_opts) {
        return $acting_opts;
    }

    /**
     * Getter for payment_name
     *
     * @return string
     */
    public function getPaymentName() {
        return $this->payment_name;
    }

    /**
     * Returns `log_type` for transaction history log rows in `*_usces_log` table
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getTransactionHistoryLogType() {
        return "{$this->acting}_transaction_history";
    }

    /**
     * Getter for acting
     *
     * @return string
     */
    public function getActing() {
        return $this->acting;
    }

    /**
     * Getter for acting_flag
     *
     * @return string
     */
    public function getActingFlag() {
        return $this->acting_flag;
    }

    /**
     * Getter for valid_divisions
     *
     * @return array
     */
    public function getValidDivisions() {
        return $this->valid_divisions;
    }

    /**
     * Getter for currency model
     *
     * @return array
     */
    public function getCurrencyModel() {
        return $this->currency;
    }

    /**
     * Getter for multi_shipping_support
     *
     * @return boolean
     */
    public function getMultiShippingSupport() {
        return $this->multi_shipping_support;
    }

    /**
     * Getter for cptmc object
     *
     * @return Client|null
     */
    public function getCptmClient() {
        return $this->cptmc;
    }

    /**
     * Getter for capture_payment_opt_support
     *
     * @return boolean
     */
    public function getCapturePaymentOptSupport() {
        return $this->capture_payment_opt_support;
    }
}
