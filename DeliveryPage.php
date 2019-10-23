<?php
namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;

/**
 * Delivery page payment method select hooks
 */
class DeliveryPage {

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
     * @throws InvalidArgumentException Thrown if module is not an instance of \Aivec\Welcart\SettlementModules\Module.
     * @return void
     */
    public function __construct($module) {
        if (!($module instanceof Module)) {
            throw new InvalidArgumentException(
                'the provided module is not an instance of \Aivec\Welcart\SettlementModules\Module'
            );
        }

        $this->module = $module;
        add_filter('usces_fiter_the_payment_method', array($this, 'filterPaymentMethods'), 10, 2);
        add_filter('usces_filter_the_continue_payment_method', array($this, 'filterContinueChargePaymentMethods'), 10, 1);
        add_filter('usces_filter_the_payment_method_choices', array($this, 'disablePaymentOption'), 10, 2);
        add_filter('usces_filter_delivery_check', array($this, 'multiShippingCheck'), 15, 3);
    }

    /**
     * Remove settlement module from radio button list of payment methods if the
     * cart contains an item that this Module cannot process
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array  $payments
     * @param string $value
     * @return array
     */
    public function filterPaymentMethods($payments, $value) {
        foreach ((array)$payments as $id => $payment) {
            if (isset($payment['settlement'])) {
                if ($payment['settlement'] === $this->module->getActingFlag()) {
                    if ($this->module->canProcessCart() === false) {
                        array_splice($payments, $id, 1);
                    }
                }
            }
        }

        return $payments;
    }

    /**
     * If applicable, adds Module to array of valid settlement modules for a cart that contains
     * a 'continue' charge type item.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $valid_settlements
     * @return array
     */
    public function filterContinueChargePaymentMethods($valid_settlements) {
        if ($this->module->canProcessCart() === true) {
            $valid_settlements[] = $this->module->getActingFlag();
        }

        return $valid_settlements;
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

    /**
     * Returns error message for Module if multi_shipping is selected but
     * not supported
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $mes
     * @return string
     */
    public function multiShippingCheck($mes) {
        global $usces;
    
        if (isset($_REQUEST['offer']['payment_name'])) {
            $payments = $usces->getPayments(wp_unslash($_REQUEST['offer']['payment_name']));
            if ($this->module->getActingFlag() === $payments['settlement']) {
                if (isset($_SESSION['msa_cart'])) {
                    if (count($_SESSION['msa_cart']) > 0 &&
                        isset($_REQUEST['delivery']['delivery_flag']) &&
                        (int)wp_unslash($_REQUEST['delivery']['delivery_flag']) === 2 &&
                        $this->module->getMultiShippingSupport() === false
                    ) {
                        $mes .= sprintf(
                            // translators: settlement module name
                            __('%s cannot be used when multiple shipping addresses are specified.', 'smodule'),
                            $this->module->getPaymentName()
                        ) . '<br/>';
                    }
                }
            }
        }
    
        return $mes;
    }
}
