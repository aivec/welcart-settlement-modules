<?php
namespace Aivec\Welcart\SettlementModules;

use Exception;

/**
 * Delivery and confirm page views for when the settlement module is not activated/authenticated
 */
class DisabledViews {

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Register hooks
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
        add_filter('usces_filter_confirm_inform', array($this, 'disabledButton'), 12, 5);
        add_filter('usces_filter_the_payment_method_choices', array($this, 'disablePaymentOption'), 10, 2);
    }

    /**
     * Displays if settlement module is the selected payment method but is not activated or authenticated.
     *
     * Serves as a backup for if disablePaymentOption() fails for some reason
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $html
     * @param string $payments
     * @param string $acting_flag
     * @param int    $rand
     * @param string $purchase_disabled
     * @return string
     */
    public function disabledButton($html, $payments, $acting_flag, $rand, $purchase_disabled) {
        if ($this->module->getActingFlag() !== $acting_flag) {
            return $html;
        }
        
        if ($this->module->ready() === false || $this->module->isModuleActivated() === false) {
            $html = '
                <div 
                    class="invalid-settings error_message"
                    style="font-size: 16px; margin-top: 20px; margin-bottom: 20px; text-align: center;"
                >
                    ' . sprintf(esc_html__('%s cannot be used', 'smodule'), $this->module->getPaymentName()) . '
                </div>';
        }

        return $html;
    }

    /**
     * Disable Settlement radio button on delivery page. Add descriptive error message.
     *
     * The way Welcart's settlement module system is designed, even if a module is explicitly disabled on the
     * settlement module settings page, it will still appear as a payment option if it has already been registered
     * as a payment option on the 基本設定 page. In other words, the 'activate' ('on' or 'off') key of a settlement
     * module has no effect on the 'use' ('activate' or 'deactivate') key set automatically by registering a module
     * as a payment option.
     *
     * Steps to reproduce this 'bug' are as follows:
     * => register and activate settlement module from module settings page
     * => register activated module as payment option from 基本設定 page
     * => deactivate settlement module from module settings page
     * RESULT => payment option still appears and is enabled for selection on delivery info page
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @global array $usces_entries
     * @param string $html
     * @param array  $payments
     * @return string
     */
    public function disablePaymentOption($html, $payments) {
        global $usces, $usces_entries;
        if ($this->module->ready() === false || $this->module->isModuleActivated() === false) {
            $value = isset($usces_entries['order']['payment_name']) ? $usces_entries['order']['payment_name'] : '';
            $payments = usces_get_system_option('usces_payment_method', 'sort');
            $payments = apply_filters('usces_fiter_the_payment_method', $payments, $value);
        
            if (defined('WCEX_DLSELLER_VERSION') and version_compare(WCEX_DLSELLER_VERSION, '2.2-beta', '<=')) {
                $cart = $usces->cart->get_cart();
                $have_continue_charge = usces_have_continue_charge($cart);
                $continue_payment_method = apply_filters('usces_filter_the_continue_payment_method', array( 'acting_remise_card', 'acting_paypal_ec' ));
            }

            $html = "<dl>\n";
            $list = '';
            $payment_ct = count($payments);
            foreach ((array)$payments as $id => $payment) {
                if (defined('WCEX_DLSELLER_VERSION') and version_compare(WCEX_DLSELLER_VERSION, '2.2-beta', '<=')) {
                    if ($have_continue_charge) {
                        if (!in_array($payment['settlement'], $continue_payment_method)) {
                            $payment_ct--;
                            continue;
                        }
                        if (isset($usces->options['acting_settings']['remise']['continuation']) && 'on' !== $usces->options['acting_settings']['remise']['continuation'] && 'acting_remise_card' == $payment['settlement']) {
                            $payment_ct--;
                            continue;
                        } elseif (isset($usces->options['acting_settings']['paypal']['continuation']) && 'on' !== $usces->options['acting_settings']['paypal']['continuation'] && 'acting_paypal_ec' == $payment['settlement']) {
                            $payment_ct--;
                            continue;
                        }
                    }
                }
                if ($payment['name'] != '' and $payment['use'] != 'deactivate') {
                    $module = trim($payment['module']);
                    if (!\WCUtils::is_blank($value)) {
                        $checked = ($payment['name'] == $value) ? ' checked' : '';
                    } elseif (1 == $payment_ct) {
                        $checked = ' checked';
                    } else {
                        $checked = '';
                    }
                    $checked = apply_filters('usces_fiter_the_payment_method_checked', $checked, $payment, $value);
                    $explanation = apply_filters('usces_fiter_the_payment_method_explanation', $payment['explanation'], $payment, $value);
                    if ((empty($module) || !file_exists($usces->options['settlement_path'] . $module)) && $payment['settlement'] == 'acting') {
                        $checked = '';
                        $list .= "\t".'<dt class="payment_'.$id.'"><label for="payment_name_' . $id . '"><input name="offer[payment_name]" id="payment_name_' . $id . '" type="radio" value="'.esc_attr($payment['name']).'"' . $checked . ' disabled onKeyDown="if (event.keyCode == 13) {return false;}" />'.esc_attr($payment['name']).'</label> <b> (' . __('cannot use this payment method now.', 'usces') . ") </b></dt>\n";
                    } else {
                        if ($payment['settlement'] === $this->module->getActingFlag()) {
                            if ($this->module->ready() === false) {
                                $list .= "\t".'<dt class="payment_'.$id.'"><label for="payment_name_' . $id . '"><input name="offer[payment_name]" id="payment_name_' . $id . '" type="radio" value="'.esc_attr($payment['name']).'"' . $checked . ' disabled onKeyDown="if (event.keyCode == 13) {return false;}" />'.esc_attr($payment['name']).'</label> <b> (' . sprintf(
                                    /* translators: %s: formatted module name. */
                                    __('Please validate your %1$s credentials to use %2$s', 'smodule'),
                                    $this->module->getAauth()->getProvider(),
                                    $this->module->getPaymentName()
                                ) . ") </b></dt>\n";
                            } elseif ($this->module->isModuleActivated() === false) {
                                $list .= "\t".'<dt class="payment_'.$id.'"><label for="payment_name_' . $id . '"><input name="offer[payment_name]" id="payment_name_' . $id . '" type="radio" value="'.esc_attr($payment['name']).'"' . $checked . ' disabled onKeyDown="if (event.keyCode == 13) {return false;}" />'.esc_attr($payment['name']).'</label> <b> (' . sprintf(
                                    /* translators: %s: formatted module name. */
                                    __('%s is deactivated on the settlement module settings page.', 'smodule'),
                                    $this->module->getPaymentName()
                                ) . ") </b></dt>\n";
                            }
                        } else {
                            $list .= "\t".'<dt class="payment_'.$id.'"><label for="payment_name_' . $id . '"><input name="offer[payment_name]" id="payment_name_' . $id . '" type="radio" value="'.esc_attr($payment['name']).'"' . $checked . ' onKeyDown="if (event.keyCode == 13) {return false;}" />'.esc_attr($payment['name'])."</label></dt>\n";
                        }
                    }
                    $list .= "\t".'<dd class="payment_'.$id.'">'.$explanation.'</dd>'."\n";
                }
            }

            $html .= $list . "</dl>\n";
        
            if (empty($list)) {
                $html = __('Not yet ready for the payment method. Please refer to a manager.', 'usces')."\n";
            }
        }
        
        return $html;
    }
}
