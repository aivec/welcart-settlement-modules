<?php

namespace Aivec\Welcart\SettlementModules\Email;

use Aivec\Welcart\SettlementModules\Module;

/**
 * Methods for sending emails related to dlseller subscription charges
 */
class AutoChargeMail
{
    /**
     * Settlement module object
     *
     * @var Module
     */
    protected $module;

    /**
     * 継続課金結果通知メール
     *
     * @var array
     */
    protected $continuation_charging_mail = [];

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
     * 自動継続課金処理 result admin email
     *
     * @global \usc_e_shop $usces
     * @param string $today
     * @param array  $todays_charging
     * @return void
     */
    public function sendAdminEmailAfterAutoChargeProcessing($today, $todays_charging) {
        global $usces;

        if (empty($this->continuation_charging_mail)) {
            return;
        }

        if (empty($todays_charging)) {
            return;
        }

        $ok = empty($this->continuation_charging_mail['OK']) ? 0 : $this->continuation_charging_mail['OK'];
        $error = empty($this->continuation_charging_mail['NG']) ? 0 : $this->continuation_charging_mail['NG'];
        $admin_subject = $this->getAutoChargeAdminEmailSubject($today);
        $admin_footer = $this->getAutoChargeAdminEmailFooter();
        $admin_message = __('Report that automated accounting process has been completed.', 'usces') . "\r\n\r\n"
            . __('Processing date', 'usces') . ' : ' . date_i18n('Y-m-d H:i:s', current_time('timestamp')) . "\r\n"
            . __('Normal done', 'usces') . ' : ' . $ok . "\r\n"
            . __('Abnormal done', 'usces') . ' : ' . $error . "\r\n\r\n";
        foreach ((array)$this->continuation_charging_mail['mail'] as $mail) {
            $admin_message .= $mail . "\r\n";
        }
        $admin_message .= $admin_footer . "\r\n";

        $to_admin = [
            'to_name' => apply_filters('usces_filter_bccmail_to_admin_name', 'Shop Admin'),
            'to_address' => $usces->options['order_mail'],
            'from_name' => apply_filters('usces_filter_bccmail_from_admin_name', 'Welcart Auto BCC'),
            'from_address' => $usces->options['sender_mail'],
            'return_path' => $usces->options['sender_mail'],
            'subject' => $admin_subject,
            'message' => $admin_message,
        ];
        usces_send_mail($to_admin);
        unset($this->continuation_charging_mail);
    }

    /**
     * Returns subject text for the auto charge admin email
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $today
     * @return string
     */
    public function getAutoChargeAdminEmailSubject($today) {
        return apply_filters(
            $this->module->getActing() . '_auto_settlement_mail_admin_subject',
            __('Automatic Continuing Charging Process Result', 'usces') . ' ' . $today,
            $today
        );
    }

    /**
     * Returns footer text for the auto charge admin email
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getAutoChargeAdminEmailFooter() {
        return apply_filters(
            $this->module->getActing() . '_auto_settlement_mail_admin_mail_footer',
            __('For details, please check on the administration panel > Continuous charge member list > Continuous charge member information.', 'usces')
        );
    }

    /**
     * 自動継続課金処理メール（正常）
     *
     * @param int    $member_id
     * @param int    $order_id
     * @param string $mail_body
     * @return void
     */
    public function sendAutoChargeSuccessEmail($member_id, $order_id, $mail_body) {
        $this->sendAutoChargeEmail($member_id, $order_id, $mail_body, false);
    }

    /**
     * 自動継続課金処理メール（エラー）
     *
     * @param int    $member_id
     * @param int    $order_id
     * @param string $mail_body
     * @return void
     */
    public function sendAutoChargeErrorEmail($member_id, $order_id, $mail_body) {
        $this->sendAutoChargeEmail($member_id, $order_id, $mail_body, true);
    }

    /**
     * 自動継続課金処理メール
     *
     * @global \usc_e_shop $usces
     * @param int    $member_id
     * @param int    $order_id
     * @param string $mail_body
     * @param bool   $is_error
     * @return void
     */
    public function sendAutoChargeEmail($member_id, $order_id, $mail_body, $is_error) {
        global $usces;

        $acting_opts = $this->module->getActingOpts();
        $order_data = $usces->get_order_data($order_id, 'direct');

        $getSubject = $is_error ? 'getAutoChargeSubscriberErrorEmailSubject' : 'getAutoChargeSubscriberEmailSubject';
        $getHeader = $is_error ? 'getAutoChargeSubscriberErrorEmailHeader' : 'getAutoChargeSubscriberEmailHeader';
        $getFooter = $is_error ? 'getAutoChargeSubscriberErrorEmailFooter' : 'getAutoChargeSubscriberEmailFooter';
        if ($acting_opts['auto_settlement_mail'] === 'on') {
            $subject = $this->{$getSubject}($member_id, $order_id, $order_data);
            $member_info = $usces->get_member_info($member_id);
            $name = usces_localized_name($member_info['mem_name1'], $member_info['mem_name2'], 'return');
            $mail_data = $usces->options['mail_data'];
            $mail_header = $this->{$getHeader}($member_id, $order_id, $order_data);
            $mail_footer = $this->{$getFooter}($member_id, $order_id, $order_data, $mail_data);
            $hookkey = $is_error === true ? '_error' : '';
            $mail_body = apply_filters(
                $this->module->getActing() . '_auto_settlement' . $hookkey . '_mail_body',
                $mail_body,
                $member_id,
                $order_id,
                $order_data
            );
            $message = $mail_header . $mail_body . $mail_footer;
            if (isset($usces->options['put_customer_name']) && $usces->options['put_customer_name'] == 1) {
                $dear_name = sprintf(__('Dear %s', 'usces'), $name);
                $message = $dear_name . "\r\n\r\n" . $message;
            }
            $to_customer = [
                // phpcs:disable WordPress.WP.I18n.NoEmptyStrings
                'to_name' => sprintf(_x('%s', 'honorific', 'usces'), $name),
                // phpcs:enable
                'to_address' => $member_info['mem_email'],
                'from_name' => get_option('blogname'),
                'from_address' => $usces->options['sender_mail'],
                'return_path' => $usces->options['sender_mail'],
                'subject' => $subject,
                'message' => $message,
            ];
            usces_send_mail($to_customer);
        }

        $status = $is_error === true ? 'NG' : 'OK';
        $count = empty($this->continuation_charging_mail[$status]) ? 0 : $this->continuation_charging_mail[$status];
        $this->continuation_charging_mail[$status] = $count + 1;
        $this->continuation_charging_mail['mail'][] = $mail_body;
    }

    /**
     * Returns subject text for the auto charge subscriber email (success)
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int   $member_id
     * @param int   $order_id
     * @param array $order_data
     * @return string
     */
    public function getAutoChargeSubscriberEmailSubject($member_id, $order_id, $order_data) {
        return apply_filters(
            $this->module->getActing() . '_auto_settlement_mail_subject',
            __('Announcement of automatic continuing charging process', 'usces'),
            $member_id,
            $order_id,
            $order_data
        );
    }

    /**
     * Returns header text for the auto charge subscriber email (success)
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int   $member_id
     * @param int   $order_id
     * @param array $order_data
     * @return string
     */
    public function getAutoChargeSubscriberEmailHeader($member_id, $order_id, $order_data) {
        return apply_filters(
            $this->module->getActing() . '_auto_settlement_mail_header',
            __('We will report automated accounting process was carried out as follows.', 'usces') . "\r\n\r\n",
            $member_id,
            $order_id,
            $order_data
        );
    }

    /**
     * Returns footer text for the auto charge subscriber email (success)
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int   $member_id
     * @param int   $order_id
     * @param array $order_data
     * @param array $mail_data
     * @return string
     */
    public function getAutoChargeSubscriberEmailFooter($member_id, $order_id, $order_data, $mail_data) {
        return apply_filters(
            $this->module->getActing() . '_auto_settlement_mail_footer',
            __('If you have any questions, please contact us.', 'usces') . "\r\n\r\n" . $mail_data['footer']['thankyou'],
            $member_id,
            $order_id,
            $order_data,
            $mail_data
        );
    }

    /**
     * Returns subject text for the auto charge subscriber email (error)
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int   $member_id
     * @param int   $order_id
     * @param array $order_data
     * @return string
     */
    public function getAutoChargeSubscriberErrorEmailSubject($member_id, $order_id, $order_data) {
        return apply_filters(
            $this->module->getActing() . '_auto_settlement_error_mail_subject',
            __('Announcement of automatic continuing charging process', 'usces'),
            $member_id,
            $order_id,
            $order_data
        );
    }

    /**
     * Returns header text for the auto charge subscriber email (error)
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int   $member_id
     * @param int   $order_id
     * @param array $order_data
     * @return string
     */
    public function getAutoChargeSubscriberErrorEmailHeader($member_id, $order_id, $order_data) {
        return apply_filters(
            $this->module->getActing() . '_auto_settlement_error_mail_header',
            __('We will reported that an error occurred in automated accounting process.', 'usces') . "\r\n\r\n",
            $member_id,
            $order_id,
            $order_data
        );
    }

    /**
     * Returns footer text for the auto charge subscriber email (error)
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int   $member_id
     * @param int   $order_id
     * @param array $order_data
     * @param array $mail_data
     * @return string
     */
    public function getAutoChargeSubscriberErrorEmailFooter($member_id, $order_id, $order_data, $mail_data) {
        return apply_filters(
            $this->module->getActing() . '_auto_settlement_error_mail_footer',
            __('If you have any questions, please contact us.', 'usces') . "\r\n\r\n" . $mail_data['footer']['thankyou'],
            $member_id,
            $order_id,
            $order_data,
            $mail_data
        );
    }

    /**
     * 自動継続課金処理メール本文
     *
     * @global \usc_e_shop $usces
     * @param int    $member_id
     * @param int    $order_id
     * @param array  $continue_data
     * @param string $result_section
     * @return string
     */
    public function getAutoChargeEmailMessage($member_id, $order_id, $continue_data, $result_section) {
        global $usces;

        $order_data = $usces->get_order_data($order_id, 'direct');
        $member_info = $usces->get_member_info($member_id);
        $name = usces_localized_name($member_info['mem_name1'], $member_info['mem_name2'], 'return');

        $message = usces_mail_line(2);// --------------------
        $message .= __('Order ID', 'dlseller') . ' : ' . $order_id . "\r\n";
        $message .= __('Application Date', 'dlseller') . ' : ' . $order_data['order_date'] . "\r\n";
        $message .= __('Member ID', 'dlseller') . ' : ' . $member_id . "\r\n";
        // phpcs:disable WordPress.WP.I18n.NoEmptyStrings
        $message .= __('Contractor name', 'dlseller') . ' : ' . sprintf(_x('%s', 'honorific', 'usces'), $name) . "\r\n";
        // phpcs:enable

        $cart = usces_get_ordercartdata($order_id);
        $cart_row = current($cart);
        $item_name = $usces->getCartItemName_byOrder($cart_row);
        $options = (empty($cart_row['options'])) ? [] : $cart_row['options'];
        $message .= __('Items', 'usces') . ' : ' . $item_name . "\r\n";
        if (is_array($options) && count($options) > 0) {
            $optstr = '';
            foreach ($options as $key => $value) {
                if (!empty($key)) {
                    $key = urldecode($key);
                    $value = maybe_unserialize($value);
                    if (is_array($value)) {
                        $c = '';
                        $optstr .= '( ' . $key . ' : ';
                        foreach ($value as $v) {
                            $optstr .= $c . rawurldecode($v);
                            $c = ', ';
                        }
                        $optstr .= " )\r\n";
                    } else {
                        $optstr .= '( ' . $key . ' : ' . rawurldecode($value) . " )\r\n";
                    }
                }
            }
            $message .= $optstr;
        }

        $message .= __('Settlement amount', 'usces') . ' : ' . usces_crform($continue_data['price'], true, false, 'return') . "\r\n";
        $message .= $result_section;
        $message .= usces_mail_line(2) . "\r\n";// --------------------
        return $message;
    }
}
