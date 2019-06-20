<?php
namespace Aivec\Welcart\SettlementModules;

use Exception;
use Aivec\Welcart\Generic;

/**
 * Delivery and confirm page views for when the settlement module is not activated/authenticated
 */
class ConfirmPage {

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Register usces_filter_confirm_inform hook
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @throws Exception Thrown if module is not an instance of Aivec\Welcart\SettlementModules\Module.
     * @return void
     */
    public function __construct($module) {
        if (!($module instanceof Module)) {
            throw new Exception('the provided module is not an instance of Aivec\Welcart\SettlementModules\Module');
        }

        $this->module = $module;
        add_filter('usces_filter_confirm_inform', array($this, 'confirmPagePayButtonHook'), 8, 5);
    }

    /**
     * Filter for confirm page payment button
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $html
     * @param array  $payments
     * @param string $acting_flag
     * @param int    $rand
     * @param string $purchase_disabled
     * @return string
     */
    public function confirmPagePayButtonHook($html, $payments, $acting_flag, $rand, $purchase_disabled) {
        if ($this->module->getActingFlag() !== $acting_flag) {
            return $html;
        }

        return $this->filterConfirmPagePayButton($html, $payments, $acting_flag, $rand, $purchase_disabled);
    }

    /**
     * Filter override for confirm page payment button
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $html
     * @param array  $payments
     * @param string $acting_flag
     * @param int    $rand
     * @param string $purchase_disabled
     * @return string
     */
    protected function filterConfirmPagePayButton($html, $payments, $acting_flag, $rand, $purchase_disabled) {
        return $html;
    }

    /**
     * Conditional view loader
     *
     * Returns true if confirm page assets should be loaded for this page. false otherwise
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @return boolean
     */
    public function loadConfirmPage() {
        global $usces;

        $load = false;
        if (Generic\WelcartUtils::isConfirmPage() === true) {
            if (isset($_SESSION['usces_entry']['order']['payment_name'])) {
                $payments = $usces->getPayments($_SESSION['usces_entry']['order']['payment_name']);
                $acting_flg = 'acting' === $payments['settlement'] ? $payments['module'] : $payments['settlement'];
                if ($acting_flg === $this->module->getActingFlag()) {
                    $load = true;
                }
            }
        }

        return $load;
    }
}
