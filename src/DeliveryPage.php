<?php

namespace Aivec\Welcart\SettlementModules;

/**
 * Delivery page payment method select hooks
 */
class DeliveryPage
{
    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Sets member vars
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @return void
     */
    public function __construct(Module $module) {
        $this->module = $module;
    }

    /**
     * Adds mandatory actions/filters
     *
     * Returns current instance for optional chaining
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return DeliveryPage
     */
    public function init() {
        add_filter('usces_filter_the_payment_method_row', [$this, 'filterPaymentMethodRow'], 10, 7);
        add_filter('usces_fiter_the_payment_method', [$this, 'filterPaymentMethods'], 10, 2);
        add_filter('usces_filter_the_continue_payment_method', [$this, 'filterContinueChargePaymentMethods'], 10, 1);
        add_filter('usces_filter_delivery_check', [$this, 'multiShippingCheck'], 15, 3);
        return $this;
    }

    /**
     * Remove settlement module from radio button list of payment methods if the
     * cart contains an item that this `Module` cannot process
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
     * If applicable, adds `Module` to array of valid settlement modules for a cart that contains
     * a `continue` charge type item.
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
     * Filters the payment method row.
     *
     * Disables the radio button and displays a message if the `Module` cannot be used.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $paymentRow
     * @param int    $id
     * @param array  $payment
     * @param string $checked
     * @param string $module
     * @param string $value
     * @param string $explanation
     * @return string
     */
    public function filterPaymentMethodRow($paymentRow, $id, $payment, $checked, $module, $value, $explanation) {
        if ($payment['settlement'] === $this->module->getActingFlag()) {
            if ($this->module->ready() === false) {
                $paymentRow = "\t" . '<dt class="payment_' . $id . '"><label for="payment_name_' . $id . '"><input name="offer[payment_name]" id="payment_name_' . $id . '" type="radio" value="' . esc_attr($payment['name']) . '"' . $checked . ' disabled onKeyDown="if (event.keyCode == 13) {return false;}" />' . esc_attr($payment['name']) . '</label> <b> (' . sprintf(
                    /* translators: name of settlement module */
                    esc_html__('%s cannot be used', 'smodule'),
                    $this->module->getPaymentName()
                ) . ") </b></dt>\n";
            }
        }

        return $paymentRow;
    }

    /**
     * Returns error message for `Module` if multi_shipping is selected but
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
                    if (
                        count($_SESSION['msa_cart']) > 0 &&
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
