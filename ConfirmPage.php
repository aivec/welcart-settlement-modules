<?php
namespace Aivec\Welcart\SettlementModules;

use Exception;
use Aivec\Welcart\Generic;

/**
 * Wrapper for the purchase button on the confirm page.
 *
 * This class should be extended by any Module instance that requires filtering of
 * the purchase button. Checks are done in this class, such as whether the injected
 * Module instance is the currently selected payment method, and so forth.
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
     * We use dependency injection here so that any instance of Module can use
     * this class as a confirm page wrapper
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
        add_action('usces_filter_template_redirect', array($this, 'setFees'), 1, 1);
    }

    /**
     * Sets cart fees (populates usces_entries global)
     *
     * We use the usces template redirect hook because scripts may rely on usces_entries
     * variables such as 'total_full_price'. The wp_enqueue_scripts hook is called BEFORE
     * the usces_entries global variable is updated, which means our injected JS variables
     * will not accurately reflect the total price on initial page load, point usage, and
     * coupon usage.
     *
     * Therefore, the onFeesSet method called by this class should be extended by any Module
     * that has scripts that rely on usces_* global variables.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @global array $usces_members
     * @global array $usces_entries
     * @param boolean $bool
     * @return boolean
     */
    public function setFees($bool) {
        global $usces, $usces_members, $usces_entries;

        usces_get_members();
        usces_get_entries();
        $usces->set_cart_fees($usces_members, $usces_entries);

        $this->onFeesSet();

        return $bool;
    }

    /**
     * Called when fees are set
     *
     * Should be extended in child class to enqueue scripts that depend on usces_*
     * global variables
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function onFeesSet() {
    }

    /**
     * Filter for confirm page payment button
     *
     * Returns html as-is if the passed in acting_flag is not the same as this classes injected
     * Module instance. Calls filter method if acting_flag is the same as our Module instance's.
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
     * Called if our injected Module instance is the selected payment method
     * on the confirm page.
     *
     * Should be extended for customizing the purchase button.
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
     * Returns true if the current page is the confirm page. false otherwise
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
