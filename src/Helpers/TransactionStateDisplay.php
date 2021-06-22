<?php

namespace Aivec\Welcart\SettlementModules\Helpers;

use Aivec\Welcart\Generic\Helpers\OrderData as GenericHelper;
use Aivec\Welcart\SettlementModules\Interfaces\TransactionState;
use Aivec\Welcart\SettlementModules\Module;

/**
 * Helper methods for displaying transaction state
 */
class TransactionStateDisplay
{
    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Injects `Module` instance
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @return void
     */
    public function __construct(Module $module) {
        $this->module = $module;
    }

    /**
     * Loads transaction states CSS
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public static function loadTransactionStatesCss() {
        $url = plugin_dir_url(__FILE__);
        wp_enqueue_style('welcart-transaction-states', $url . '../Styles/transactionStates.css', [], '1.0.0');
    }

    /**
     * Returns order list transaction ID row column HTML for a dlseller subscription order
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param TransactionState $state
     * @param int              $order_id
     * @param int              $member_id
     * @return string
     */
    public function getSubscriptionOrderTransactionIdRowColumnHtml(TransactionState $state, $order_id, $member_id) {
        $link = (new OrderData($this->module))->getSubscriptionOrderDetailsPageLink($order_id, $member_id);
        ob_start();
        ?>
        <td>
            <?php echo $state->getTransactionId(); ?>
            <span class="acting-status subscription-order">
                <?php _e('Continuation', 'usces'); ?>
            </span>
            <span style="display: inline-block">
                <a href="<?php echo $link; ?>"><?php _e('Detail', 'usces'); ?></a>
            </span>
        </td>
        <?php
        $html = (string)ob_get_clean();

        return $html;
    }

    /**
     * Returns order list transaction ID row column value
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param TransactionState $state
     * @param bool             $showTransactionId
     * @return string
     */
    public static function getOrderListTransactionIdRowColumnHtml(TransactionState $state, $showTransactionId = true) {
        ob_start();
        ?>
        <td>
            <?php if ($showTransactionId === true) : ?>
                <?php echo $state->getTransactionId(); ?>
            <?php endif; ?>
            <span class="acting-status <?php echo $state->getCssClass(); ?>">
                <?php echo $state->getDisplayText(); ?>
            </span>
        </td>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    /**
     * Displays transaction state as a row within an HTML table
     *
     * This method can be used in various places where the transaction state needs to be displayed,
     *
     * For example, you can call this method from within an overridden `orderEditFormStatusBlockMiddle`
     * method in the `OrderEditPage` class. Doing so will display the transaction state under `ステータス`
     * on the left hand side of the order edit page.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param TransactionState $state
     * @param string|null      $id span id. Defaults to `$this->module->getActing() . '-acting-status'`
     * @return void
     */
    public function displayTransactionState(TransactionState $state, $id = null) {
        if ($id === null) {
            $id = $this->module->getActing() . '-acting-status';
        }
        ?>
        <tr>
            <td class="label status"><?php _e('Settlement status', 'usces'); ?></td>
            <td class="col1 status">
                <span class="settlement-status">
                    <span id="<?php echo esc_attr($id); ?>" class="acting-status <?php echo $state->getCssClass(); ?>">
                        <?php echo $state->getDisplayText(); ?>
                    </span>
                </span>
            </td>
        </tr>
        <?php
    }

    /**
     * Displays subscription state as a row within an HTML table
     *
     * This method can be used in various places where the subscription state of a dlseller
     * subscription order needs to be displayed.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $order_id
     * @return void
     */
    public static function displaySubscriptionStateTr($order_id) {
        $sorder = GenericHelper::getSubscriptionOrderData($order_id);
        if (empty($sorder)) {
            return;
        }

        ?>
        <tr>
            <td class="label status"><?php _e('Status', 'dlseller'); ?></td>
            <td class="col1 status">
                <div class="subscription-status">
                    <?php if (strtolower($sorder['status']) === 'continuation') : ?>
                        <div class="continuation">
                            <?php _e('continuation', 'dlseller'); ?>
                        </div>
                    <?php else : ?>
                        <div class="cancellation">
                            <?php _e('cancellation', 'dlseller'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }
}
