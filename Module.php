<?php
namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;

/**
 * Settlement Module registration
 */
class Module {

    const SETTINGS_KEY = 'acting_settings';

    /**
     * Front facing name of the settlement module
     *
     * @var string
     */
    private $payment_name;

    /**
     * Acting string for the settlement module.
     *
     * @var string
     */
    private $acting;

    /**
     * Acting flag string for the settlement module.
     *
     * @var string
     */
    private $acting_flag;

    /**
     * Suffix string for all hooks and filters unique to an instance
     * of this class
     *
     * @var string
     */
    private $hook_suffix;

    /**
     * Array of valid divisions and payment types for the Module
     *
     * Divisions:
     * 'shipped' -> 物販
     * 'data' -> コンテンツファイル
     * 'service' -> サービス
     *
     * Charge types:
     * 'once' -> 通常課金
     * 'continue' -> 継続課金
     * 'regular' -> ?
     *
     * simply omit any keys that represent a division or charge type that is not supported
     * by the Module.
     *
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
     *
     * @var array
     */
    private $valid_divisions;

    /**
     * Aivec proprietary authentication instance or null if not required
     *
     * @var \Aivec\Welcart\ProprietaryAuthentication\Auth|null
     */
    private $aauth;

    /**
     * Initializes a settlement module.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string                                             $payment_name
     * @param string                                             $acting
     * @param string                                             $acting_flag
     * @param string                                             $hook_suffix
     * @param array                                              $valid_divisions
     * @param \Aivec\Welcart\ProprietaryAuthentication\Auth|null $aauth
     */
    public function __construct(
        $payment_name,
        $acting,
        $acting_flag,
        $hook_suffix,
        $valid_divisions = [
            'shipped' => ['once'],
            'service' => ['once'],
        ],
        $aauth = null
    ) {
        $this->validateDivisions($valid_divisions);
        $this->payment_name = $payment_name;
        $this->acting = $acting;
        $this->acting_flag = $acting_flag;
        $this->hook_suffix = $hook_suffix;
        $this->valid_divisions = $valid_divisions;
        $this->aauth = $aauth;

        load_textdomain('smodule', __DIR__ . '/languages/smodule-ja.mo');
        load_textdomain('smodule', __DIR__ . '/languages/smodule-en.mo');
    }

    /**
     * Validates form of divisions array
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @throws InvalidArgumentException Thrown if $valid_divisions is malformed.
     * @param array $valid_divisions
     * @return void
     */
    private function validateDivisions($valid_divisions) {
        if (!is_array($valid_divisions)) {
            throw new InvalidArgumentException('valid_divisions must be an array');
        }
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
                        'division \''.$division.'\' does not contain any valid charge types'
                    );
                }
            }
        }
        if ($nodivisions === true) {
            throw new InvalidArgumentException(
                'valid_divisions must contain at least one of \'shipped\', \'service\', or \'data\''
            );
        }
    }

    /**
     * Returns true if settlement module can be used
     *
     * Always returns true if authentication is not required
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return boolean
     */
    public function ready() {
        $ready = false;
        if ($this->aauth !== null) {
            if (method_exists($this->aauth, 'authenticated')) {
                if ($this->aauth->authenticated()) {
                    $ready = true;
                }
            }
        } else {
            $ready = true;
        }

        return $ready;
    }

    /**
     * Returns acting options for the settlement module
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @return array
     */
    public function getActingOpts() {
        global $usces;

        $acting_opts
            = isset($usces->options[self::SETTINGS_KEY][$this->acting])
            ? $usces->options[self::SETTINGS_KEY][$this->acting]
            : array();
      
        $acting_opts['activate'] = isset($acting_opts['activate']) ? $acting_opts['activate'] : 'off';
        $acting_opts['sandbox'] = isset($acting_opts['sandbox']) ? $acting_opts['sandbox'] : true;
        $acting_opts = $this->filterActingOpts($acting_opts);

        return $acting_opts;
    }

    /**
     * Checks if settlement module is activated
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return boolean
     */
    public function isModuleActivated() {
        $payment_methods = usces_get_system_option('usces_payment_method', 'sort');
        $module = array();
        foreach ($payment_methods as $index => $values) {
            if ($values['settlement'] === $this->acting_flag) {
                $module = $values;
            }
        }
        $options = get_option('usces');
        if (isset($options[self::SETTINGS_KEY][$this->acting]) && !empty($module)) {
            if ($options[self::SETTINGS_KEY][$this->acting]['activate'] === 'on'
                && $module['use'] === 'activate') {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines whether the settlement module can process all items in the cart.
     *
     * If even ONE item contains a division or charge type that is not valid for this
     * module, false will be returned, otherwise true is returned.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @return boolean
     */
    public function canProcessCart() {
        global $usces;

        $cart = $usces->cart->get_cart();
        foreach ($cart as $item) {
            // check if division of item is supported by this Module
            $item_division = get_post_meta($item['post_id'], '_item_division', true);
            $division = empty($item_division) ? 'shipped' : $item_division;
            if (!array_key_exists($division, $this->valid_divisions)) {
                return false;
            }
            
            // check if charge type of item is supported by this Module
            $item_charge_type = $usces->getItemChargingType($item['post_id']);
            $charge_type = empty($item_charge_type) ? 'once' : $item_charge_type;
            if (!in_array($charge_type, $this->valid_divisions[$division], true)) {
                return false;
            }
        }

        return true;
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
     * Getter for hook_suffix
     *
     * @return string
     */
    public function getHookSuffix() {
        return $this->hook_suffix;
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
     * Getter for aauth object
     *
     * @return \Aivec\Welcart\ProprietaryAuthentication\Auth|null
     */
    public function getAauth() {
        return $this->aauth;
    }
}
