<?php

namespace Aivec\Welcart\SettlementModules;

use Aivec\Welcart\SettlementModules\Email\AutoChargeMail;
use Aivec\Welcart\SettlementModules\Interfaces\Initializer;

/**
 * Handler methods for dlseller subscription payments
 */
class RecurringPaymentHandler implements Initializer
{
    use HooksAutoloader;

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Auto charge email object
     *
     * @var AutoChargeMail
     */
    protected $email;

    /**
     * Sets member vars
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @return void
     */
    public function __construct(Module $module) {
        $this->module = $module;
        $this->email = $this->getAutoChargeMailInstance();
    }

    /**
     * Returns an instance of `AutoChargeMail`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return AutoChargeMail
     */
    public function getAutoChargeMailInstance() {
        return new AutoChargeMail($this->module);
    }

    /**
     * Registers hooks
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return $this
     */
    public function init() {
        if (!$this->module->canHandleSubscriptionOrders()) {
            return $this;
        }
        add_action('dlseller_action_do_continuation_charging', [$this, 'handleSubscriptionChargeDI'], 10, 4);
        add_action('dlseller_action_do_continuation', [$this->email, 'sendAdminEmailAfterAutoChargeProcessing'], 10, 2);
        return $this;
    }

    /**
     * 自動継続課金処理 DI
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @param string $today
     * @param int    $member_id
     * @param int    $order_id
     * @param array  $continue_data
     * @return void
     */
    public function handleSubscriptionChargeDI($today, $member_id, $order_id, $continue_data) {
        global $usces;

        if (!usces_is_membersystem_state()) {
            return;
        }

        if (0 >= $continue_data['price']) {
            return;
        }

        if ($continue_data['status'] === 'cancellation') {
            return;
        }

        $order_data = $usces->get_order_data($order_id, 'direct');
        if (!$order_data || $usces->is_status('cancel', $order_data['order_status'])) {
            return;
        }

        if (!$this->module->isOrderAssociated($order_id)) {
            return;
        }

        $this->handleSubscriptionCharge($today, $member_id, $order_id, $continue_data);
    }

    /**
     * 自動継続課金処理
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $today
     * @param int    $member_id
     * @param int    $order_id
     * @param array  $continue_data
     * @return void
     */
    protected function handleSubscriptionCharge($today, $member_id, $order_id, $continue_data) {
    }
}
