<?php
namespace Aivec\Welcart\SettlementModules;

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
     * @param \Aivec\Welcart\ProprietaryAuthentication\Auth|null $aauth
     */
    public function __construct(
        $payment_name,
        $acting,
        $acting_flag,
        $hook_suffix,
        $aauth = null
    ) {
        $this->payment_name = $payment_name;
        $this->acting = $acting;
        $this->acting_flag = $acting_flag;
        $this->hook_suffix = $hook_suffix;
        $this->aauth = $aauth;

        load_textdomain('smodule', __DIR__ . '/languages/smodule-ja.mo');
        load_textdomain('smodule', __DIR__ . '/languages/smodule-en.mo');
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
     * Getter for aauth object
     *
     * @return \Aivec\Welcart\ProprietaryAuthentication\Auth|null
     */
    public function getAauth() {
        return $this->aauth;
    }
}
