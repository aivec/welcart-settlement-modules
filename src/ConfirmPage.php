<?php
namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;
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
     * Back button form
     *
     * @var string
     */
    public $backbutton;

    /**
     * Generic error html
     *
     * @var string
     */
    public $errorhtml;

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Register usces_filter_confirm_inform hook
     *
     * We use dependency injection here so that any instance of `Module` can use
     * this class as a confirm page wrapper
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @throws InvalidArgumentException Thrown if module is not an instance of `Module`.
     * @return void
     */
    public function __construct(Module $module) {
        ob_start();
        ?>
            <div
                class="invalid-settings error_message"
                style="font-size: 16px; margin-top: 20px; margin-bottom: 20px; text-align: center;"
            >
                <?php // translators: name of settlement module ?>
                <?php echo sprintf(esc_html__('%s cannot be used', 'smodule'), $module->getPaymentName()) ?>
            </div>
        <?php
        $errorhtml = ob_get_contents();
        ob_end_clean();

        $this->errorhtml = $errorhtml;
        $this->module = $module;
        add_filter('usces_filter_confirm_before_backbutton', [$this, 'filterBeforeBackButtonDI'], 10, 4);
        add_filter('usces_filter_confirm_inform', [$this, 'confirmPagePayButtonHook'], 10, 5);
        add_action('usces_filter_template_redirect', [$this, 'setFees'], 1, 1);
    }

    /**
     * Sets cart fees (populates `usces_entries` global)
     *
     * We use the usces template redirect hook because scripts may rely on `usces_entries`
     * variables such as `total_full_price`. The wp_enqueue_scripts hook is called **BEFORE**
     * the `usces_entries` global variable is updated, which means our injected JS variables
     * will not accurately reflect the total price on initial page load, point usage, and
     * coupon usage.
     *
     * Therefore, the `onFeesSet` method called by this class should be extended by any `Module`
     * that has scripts that rely on `usces_*` global variables.
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

        if ($this->loadConfirmPage() === true &&
            $this->module->canProcessCart() === true &&
            $this->module->ready() === true &&
            $this->module->isModuleActivated() === true
        ) {
            usces_get_members();
            usces_get_entries();
            $usces->set_cart_fees($usces_members, $usces_entries);

            $this->onFeesSet();
        }

        return $bool;
    }

    /**
     * Filter for confirm page payment button
     *
     * Returns html as-is if the passed in `acting_flag` is not the same as this classes injected
     * `Module` instance. Displays an error message if any of the following are true:
     *
     * 1. The cart contains an item with a division or charge type that cannot be processed
     * by the selected settlement module.
     * 2. The selected module is activated as a payment method but turned off on the settlement
     * settings page.
     * 3. The settlement module requires aauth authentication but is not authenticated
     *
     * Calls filter method if `acting_flag` is the same as our `Module` instance's and all tests
     * listed above pass.
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

        ob_start();
        ?>
        <form
            id="purchase_form"
            action="<?php echo USCES_CART_URL ?>"
            method="post"
            onKeyDown="if (event.keyCode == 13) {return false;}"
        >
            <div class="send">
                <input
                    name="backDelivery"
                    type="submit"
                    id="back_button"
                    class="back_to_delivery_button"
                    value="<?php echo apply_filters('usces_filter_confirm_prebutton_value', __('Back to payment method page.', 'usces')); ?>"
                />
            </div>
        </form>
        <?php
        $backbutton = ob_get_contents();
        ob_end_clean();

        $this->backbutton = $backbutton;

        if ($this->module->canProcessCart() === false ||
            $this->module->ready() === false ||
            $this->module->isModuleActivated() === false
        ) {
            return $this->backbutton . $this->errorhtml;
        }

        return $this->filterConfirmPagePayButton($html, $payments, $acting_flag, $rand, $purchase_disabled);
    }

    /**
     * Conditional view loader
     *
     * Returns true if the current page is the confirm page and the settlement module
     * is the same as our injected `Module`. false otherwise
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

    /**
     * Filters HTML before back button.
     *
     * This method will **automatically** add a nonce field to the confirm page purchase
     * form if the current `Module` is the same as our injected `Module`. Note that
     * validation of the nonce is done automatically by acting processing methods
     * in the `CheckoutHooks` class.
     *
     * Note also that this filter will have no effect if, for whatever reason, the cart
     * cannot be processed by the `Module` instance.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param null   $null
     * @param array  $payments
     * @param string $acting_flag
     * @param string $rand
     * @return null|string
     */
    public function filterBeforeBackButtonDI($null, $payments, $acting_flag, $rand) {
        if ($this->module->getActingFlag() !== $acting_flag) {
            return $null;
        }

        $noncefield = wp_nonce_field($this->module->getActing(), CheckoutHooks::PURCHASE_NONCE_NAME, true, false);

        return $noncefield . $this->filterBeforeBackButton($null, $payments, $acting_flag, $rand);
    }

    /**
     * Filters HTML before back button.
     *
     * Note that this filter will have no effect if, for whatever reason, the cart
     * cannot be processed by the `Module` instance
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param null   $null
     * @param array  $payments
     * @param string $acting_flag
     * @param string $rand
     * @return string
     */
    protected function filterBeforeBackButton($null, $payments, $acting_flag, $rand) {
        return '';
    }

    /**
     * Called if our injected `Module` instance is the selected payment method
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
}