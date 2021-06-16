<?php

namespace Aivec\Welcart\SettlementModules;

/**
 * Dlseller 継続課金会員リスト
 */
class SubscriptionOrderMemberPage
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
     * Dynamically adds actions/filters.
     *
     * Only hooks implemented by the child class are registered
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function addHooks() {
        $map = [
            new HookMeta(['filterSubscriptionMemberListCondition'], function () {
                add_filter('dlseller_filter_continue_member_list_condition', [$this, 'filterSubscriptionMemberListConditionDI'], 10, 4);
            }),
            new HookMeta(['subscriptionDetailsPage'], function () {
                add_action('dlseller_action_continue_member_list_page', [$this, 'subscriptionDetailsPageDI'], 10, 1);
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
            $queryvars = [
                'page' => 'usces_continue',
                'continue_action' => 'settlement_' . $this->module->getActingFlag(),
                'member_id' => $member_id,
                'order_id' => $order_id,
            ];
            $detailsUrl = add_query_arg($queryvars, admin_url('admin.php'));
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
                $this->subscriptionDetailsPageDI($member_id, $order_id);
                exit();
            }
        }
    }

    /**
     * 継続課金会員決済状況ページ表示
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $member_id
     * @param int $order_id
     * @return void
     */
    public function subscriptionDetailsPage($member_id, $order_id) {
    }
}
