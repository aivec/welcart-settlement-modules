<?php

namespace Aivec\Welcart\SettlementModules;

use Aivec\Welcart\Generic\Helpers\OrderData;
use Aivec\Welcart\SettlementModules\Helpers\TransactionStateDisplay;
use Aivec\Welcart\SettlementModules\Interfaces\Initializer;

/**
 * Dlseller 継続課金会員リスト
 */
abstract class SubscriptionOrderMemberPage implements Initializer
{
    use HooksAutoloader;

    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * Sets member vars
     *
     * We use dependency injection here so that any instance of `Module` can use
     * this class as a 継続課金会員リスト page wrapper
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
     * @return SubscriptionOrderMemberPage
     */
    public function init() {
        if (!is_admin()) {
            return $this;
        }
        add_action('dlseller_action_continue_member_list_page', [$this, 'subscriptionDetailsPageDI'], 10, 1);
        return $this;
    }

    /**
     * Dynamically adds actions/filters.
     *
     * Only hooks implemented by the child class are registered
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    private function addHooks() {
        if (!$this->shouldRegisterHooks()) {
            return;
        }
        $map = [
            new HookMeta(['filterSubscriptionMemberListCondition'], function () {
                add_filter('dlseller_filter_continue_member_list_condition', [$this, 'filterSubscriptionMemberListConditionDI'], 10, 4);
            }),
        ];

        $this->dynamicallyRegisterHooks($map);
    }

    /**
     * Filters 継続課金会員リスト「状態」
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $condition Subscription condition
     * @param int    $member_id
     * @param int    $order_id
     * @param array  $data Subscription data
     * @return string
     */
    public function filterSubscriptionMemberListConditionDI($condition, $member_id, $order_id, $data) {
        $isAssociated = false;
        if (isset($data['acting'])) {
            $isAssociated = $data['acting'] === $this->module->getActingFlag();
        } else {
            $isAssociated = $this->module->isOrderAssociated((int)$order_id);
        }
        if ($isAssociated === true) {
            $detailsUrl = (new Helpers\OrderData($this->module))->getSubscriptionOrderDetailsPageLink($order_id, $member_id);
            $defaultHtml = '<a href="' . $detailsUrl . '">' . __('Detail', 'usces') . '</a>';
            return $this->filterSubscriptionMemberListCondition(
                $condition,
                $member_id,
                $order_id,
                $data,
                $detailsUrl,
                $defaultHtml
            );
        }

        return $condition;
    }

    /**
     * Filters 継続課金会員リスト「状態」
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $condition Subscription condition
     * @param int    $member_id
     * @param int    $order_id
     * @param array  $data Subscription data
     * @param string $detailsUrl
     * @param string $defaultHtml Anchor tag that points to the details page
     * @return string
     */
    protected function filterSubscriptionMemberListCondition(
        $condition,
        $member_id,
        $order_id,
        $data,
        $detailsUrl,
        $defaultHtml
    ) {
        return $condition;
    }

    /**
     * 継続課金会員決済状況ページ表示
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $continue_action
     * @return void
     */
    public function subscriptionDetailsPageDI($continue_action) {
        if ($continue_action === 'settlement_' . $this->module->getActingFlag()) {
            $member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : null;
            $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
            if (!empty($member_id) && !empty($order_id)) {
                if ($this->module->isOrderAssociated($order_id)) {
                    TransactionStateDisplay::loadTransactionStatesCss();
                    $this->subscriptionDetailsPage($member_id, $order_id);
                    exit;
                }
            }
        }
    }

    /**
     * 継続課金会員決済状況ページ表示
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @global \usc_e_shop $usces
     * @param int $member_id
     * @param int $order_id
     * @return void
     */
    protected function subscriptionDetailsPage($member_id, $order_id) {
        global $usces;

        $continue_data = OrderData::getSubscriptionOrderData($order_id);
        $curent_url = esc_url($_SERVER['REQUEST_URI']);
        $navibutton = '<a href="' . esc_url($_SERVER['HTTP_REFERER']) . '" class="back-list"><span class="dashicons dashicons-list-view"></span>' . __('Back to the continue members list', 'dlseller') . '</a>';

        $order_data = $usces->get_order_data($order_id, 'direct');
        if (!$order_data) {
            return;
        }

        $member_info = $usces->get_member_info($member_id);
        $name = usces_localized_name($member_info['mem_name1'], $member_info['mem_name2'], 'return');
        $contracted_date = (empty($continue_data['contractedday'])) ? dlseller_next_contracting($order_id) : $continue_data['contractedday'];
        if (!empty($contracted_date)) {
            list( $contracted_year, $contracted_month, $contracted_day ) = explode('-', $contracted_date);
        } else {
            $contracted_year = 0;
            $contracted_month = 0;
            $contracted_day = 0;
        }
        $charged_date = (empty($continue_data['chargedday'])) ? dlseller_next_charging($order_id) : $continue_data['chargedday'];
        if (!empty($charged_date)) {
            list( $charged_year, $charged_month, $charged_day ) = explode('-', $charged_date);
        } else {
            $charged_year = 0;
            $charged_month = 0;
            $charged_day = 0;
        }
        $year = substr(date_i18n('Y', current_time('timestamp')), 0, 4);
        $total_full_price = $order_data['order_item_total_price'] - $order_data['order_usedpoint'] + $order_data['order_discount'] + $order_data['order_shipping_charge'] + $order_data['order_cod_fee'] + $order_data['order_tax'];

        $log_data = $this->getActingLog($order_id, $member_id);
        $num = $log_data ? count($log_data) : 1;

        ?>
        <div class="wrap">
            <div class="usces_admin">
                <h1>Welcart Management <?php _e('Continuation charging member information', 'dlseller'); ?></h1>
                <p class="version_info">Version <?php echo WCEX_DLSELLER_VERSION; ?></p>
                <?php usces_admin_action_status(); ?>
                <div class="edit_pagenav"><?php echo $navibutton; ?></div>
                <div id="datatable">
                    <div id="tablesearch" class="usces_tablesearch">
                        <div id="searchBox" style="display:block">
                            <table class="search_table">
                                <tr>
                                    <td class="label"><?php _e('Continuation charging information', 'dlseller'); ?></td>
                                    <td>
                                        <table class="order_info">
                                        <tr>
                                            <th><?php _e('Member ID', 'dlseller'); ?></th>
                                            <td><?php echo $member_id; ?></td>
                                            <th><?php _e('Contractor name', 'dlseller'); ?></th>
                                            <td><?php echo esc_html($name); ?></td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Order ID', 'dlseller'); ?></th>
                                            <td><?php echo $order_id; ?></td>
                                            <th><?php _e('Application Date', 'dlseller'); ?></th>
                                            <td><?php echo $order_data['order_date']; ?></td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Renewal Date', 'dlseller'); ?></th>
                                            <td>
                                                <select id="contracted-year">
                                                    <option value="0"<?php if ($contracted_year == 0) {
                                                        echo ' selected="selected"';
                                                                     } ?>></option>
                                                    <?php for ($i = 0; $i <= 10; $i++) : ?>
                                                    <option value="<?php echo ($year + $i); ?>"<?php if ($contracted_year == ($year + $i)) {
                                                        echo ' selected="selected"';
                                                                   } ?>><?php echo ($year + $i); ?></option>
                                                    <?php endfor; ?>
                                                </select>-
                                                <select id="contracted-month">
                                                    <option value="0"<?php if ($contracted_month == 0) {
                                                        echo ' selected="selected"';
                                                                     } ?>></option>
                                                    <?php for ($i = 1; $i <= 12; $i++) : ?>
                                                    <option value="<?php printf('%02d', $i); ?>"<?php if ((int)$contracted_month == $i) {
                                                        echo ' selected="selected"';
                                                                   } ?>><?php printf('%2d', $i); ?></option>
                                                    <?php endfor; ?>
                                                </select>-
                                                <select id="contracted-day">
                                                    <option value="0"<?php if ($contracted_day == 0) {
                                                        echo ' selected="selected"';
                                                                     } ?>></option>
                                                    <?php for ($i = 1; $i <= 31; $i++) : ?>
                                                    <option value="<?php printf('%02d', $i); ?>"<?php if ((int)$contracted_day == $i) {
                                                        echo ' selected="selected"';
                                                                   } ?>><?php printf('%2d', $i); ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </td>
                                            <th><?php _e('Next Withdrawal Date', 'dlseller'); ?></th>
                                            <td>
                                                <select id="charged-year">
                                                    <option value="0"<?php if ($charged_year == 0) {
                                                        echo ' selected="selected"';
                                                                     } ?>></option>
                                                    <option value="<?php echo $year; ?>"<?php if ($charged_year == $year) {
                                                        echo ' selected="selected"';
                                                                   } ?>><?php echo $year; ?></option>
                                                    <option value="<?php echo $year + 1; ?>"<?php if ($charged_year == ($year + 1)) {
                                                        echo ' selected="selected"';
                                                                   } ?>><?php echo $year + 1; ?></option>
                                                </select>-
                                                <select id="charged-month">
                                                    <option value="0"<?php if ($charged_month == 0) {
                                                        echo ' selected="selected"';
                                                                     } ?>></option>
                                                    <?php for ($i = 1; $i <= 12; $i++) : ?>
                                                    <option value="<?php printf('%02d', $i); ?>"<?php if ((int)$charged_month == $i) {
                                                        echo ' selected="selected"';
                                                                   } ?>><?php printf('%2d', $i); ?></option>
                                                    <?php endfor; ?>
                                                </select>-
                                                <select id="charged-day">
                                                    <option value="0"<?php if ($charged_day == 0) {
                                                        echo ' selected="selected"';
                                                                     } ?>></option>
                                                    <?php for ($i = 1; $i <= 31; $i++) : ?>
                                                    <option value="<?php printf('%02d', $i); ?>"<?php if ((int)$charged_day == $i) {
                                                        echo ' selected="selected"';
                                                                   } ?>><?php printf('%2d', $i); ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Amount on order', 'usces'); ?></th>
                                            <td><?php usces_crform($continue_data['order_price'], false); ?></td>
                                            <th><?php _e('Settlement amount', 'usces'); ?></th>
                                            <td><input type="text" id="price" style="text-align: right;" value="<?php usces_crform($continue_data['price'], false, false, '', false); ?>"><?php usces_crcode(); ?></td>
                                        </tr>
                                        <tr>
                                            <th><?php _e('Status', 'dlseller'); ?></th>
                                            <td><select id="dlseller-status">
                                            <?php ob_start(); ?>
                                            <?php if ($continue_data['status'] == 'continuation') : ?>
                                                <option value="continuation" selected="selected"><?php _e('Continuation', 'dlseller'); ?></option>
                                                <option value="cancellation"><?php _e('Stop', 'dlseller'); ?></option>
                                            <?php else : ?>
                                                <option value="cancellation" selected="selected"><?php _e('Cancellation', 'dlseller'); ?></option>
                                                <option value="continuation"><?php _e('Resumption', 'dlseller'); ?></option>
                                            <?php endif; ?>
                                            <?php
                                                $dlseller_status_options = ob_get_contents();
                                                ob_end_clean();
                                                $dlseller_status_options = apply_filters('usces_filter_continuation_charging_status_options', $dlseller_status_options, $continue_data);
                                                echo $dlseller_status_options;
                                            ?>
                                            </select></td>
                                            <td colspan="2"><input id="continuation-update" type="button" class="button button-primary" value="<?php _e('Update'); ?>" /></td>
                                        </tr>
                                        </table>
                                        <?php do_action('usces_action_continuation_charging_information', $continue_data, $member_id, $order_id); ?>
                                    </td>
                                </tr>
                            </table>
                        </div><!-- searchBox -->
                    </div><!-- tablesearch -->
                    <table id="mainDataTable" class="new-table order-new-table">
                        <thead>
                            <tr>
                                <th scope="col">&nbsp;</th>
                                <th scope="col"><?php _e('Processing date', 'usces'); ?></th>
                                <th scope="col"><?php _e('Transaction ID', 'usces'); ?></th>
                                <th scope="col"><?php _e('Settlement amount', 'usces'); ?></th>
                                <th scope="col"><?php _e('Processing classification', 'usces'); ?></th>
                                <th scope="col">&nbsp;</th>
                            </tr>
                        </thead>
                        <?php foreach ((array)$log_data as $log_row) : ?>
                            <?php
                            $log = $log_row->getLog();
                            $state = $log->getTransactionState();
                            $amount = $log->getAmount()->getAmount();
                            ?>
                            <tbody>
                                <tr>
                                    <td><?php echo $num; ?></td>
                                    <td><?php echo $log->getLocalDateTime(); ?></td>
                                    <td><?php echo !empty($log->getTransactionId()) ? $log->getTransactionId() : ''; ?></td>
                                    <td class="amount"><?php echo !empty($amount) ? usces_crform($amount, false, true, 'return', true) : ''; ?></td>
                                    <?php if ($state !== null) : ?>
                                        <?php echo TransactionStateDisplay::getOrderListTransactionIdRowColumnHtml($state, false); ?>
                                    <?php else : ?>
                                        <td>&nbsp;</td>
                                    <?php endif; ?>
                                    <td>
                                        <input
                                            type="button"
                                            id="settlement-information-<?php echo $log->getTransactionId(); ?>-<?php echo $num; ?>"
                                            class="button settlement-information"
                                            value="<?php _e('Settlement info', 'usces'); ?>"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                            <?php $num--; ?>
                        <?php endforeach; ?>
                    </table>
                </div><!--datatable-->
                <input name="member_id" type="hidden" id="member_id" value="<?php echo $member_id; ?>" />
                <input name="order_id" type="hidden" id="order_id" value="<?php echo $order_id; ?>" />
                <input name="usces_referer" type="hidden" id="usces_referer" value="<?php echo urlencode($curent_url); ?>" />
                <?php wp_nonce_field('order_edit', 'wc_nonce'); ?>
            </div><!--usces_admin-->
        </div><!--wrap-->
        <?php
        $order_action = 'edit';
        $cart = [];
        $action_args = compact('order_action', 'order_id', 'cart');
        // $this->settlement_dialog( $order_data, $action_args );
        include(ABSPATH . 'wp-admin/admin-footer.php');
    }

    /**
     * Returns an array of `TransactionLogUscesRow` instances for building transaction history table
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $order_id
     * @param int $member_id
     * @return Logging\TransactionLogUscesRow[]
     */
    abstract public function getActingLog($order_id, $member_id);
}
