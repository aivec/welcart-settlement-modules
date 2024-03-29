<?php

namespace Aivec\Welcart\SettlementModules;

use Aivec\Plugins\EnvironmentSwitcher;
use Aivec\CptmClient\ServerControlled;
use Aivec\Welcart\SettlementModules\Interfaces\Initializer;

/**
 * Settlement Module registration factory
 */
class Factory implements Initializer
{
    /**
     * The settlement module
     *
     * @var Module
     */
    protected $module;

    /**
     * Payment name displayed on the settlement settings page
     *
     * This is for cases where the payment name registered on the 基本設定ページ is different
     * than what should be displayed on the settlement settings page
     *
     * @var string
     */
    private $payment_display_name;

    /**
     * Determines whether to display the settlement tab or not.
     *
     * @var boolean
     */
    private $display_tab;

    /**
     * Settlement settings error string
     *
     * @var string
     */
    private $error_mes = '';

    /**
     * Initializes settings for a settlement module.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @param string $payment_display_name
     * @return void
     */
    public function __construct(Module $module, $payment_display_name = '') {
        $this->module = $module;
        $this->payment_display_name = !empty($payment_display_name) ? $payment_display_name : $this->module->getPaymentName();
    }

    /**
     * Adds mandatory actions/filters
     *
     * Returns current instance for optional chaining
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return Factory
     */
    public function init() {
        if (!is_admin()) {
            return $this;
        }
        add_action('usces_action_settlement_tab_title', [$this, 'settlementTabTitle']);
        add_action('usces_action_settlement_tab_body', [$this, 'settlementTabBody']);
        add_action('usces_action_admin_settlement_update', [$this, 'settlementUpdate']);
        $this->setAvailableSettlement();
        return $this;
    }

    /**
     * Adds module as a tab to Welcarts array of payment tabs
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @link /wp-admin/admin.php?page=usces_settlement
     * @return void
     */
    public function settlementTabTitle() {
        $settlement_selected = get_option('usces_settlement_selected');
        if (in_array($this->module->getActing(), (array)$settlement_selected, true)) {
            $this->display_tab = true;
        }

        if ($this->display_tab === true) {
            ?>
            <li>
                <a href="#uscestabs_<?php echo esc_attr($this->module->getActing()); ?>">
                    <?php echo esc_html($this->payment_display_name); ?>
                </a>
            </li>
            <?php
        }
    }

    /**
     * Adds module as a payment option to Welcarts option array of payment options
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    private function setAvailableSettlement() {
        $available_settlement = get_option('usces_available_settlement');
        if (!in_array($this->module->getActing(), $available_settlement, true)) {
            $available_settlement[$this->module->getActing()] = $this->payment_display_name;
            update_option('usces_available_settlement', $available_settlement);
        }
    }

    /**
     * Echos the html for this modules settlement tab body.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @link /wp-admin/admin.php?page=usces_settlement
     * @return void
     */
    public function settlementTabBody() {
        $settlement_selected = get_option('usces_settlement_selected');
        if (in_array($this->module->getActing(), (array)$settlement_selected, true)) {
            $this->display_tab = true;
        }

        $acting_opts = $this->module->getActingOpts();
        $documentation_url = $this->filterDocumentationUrl('');

        if ($this->display_tab === true) : ?>
            <div id="uscestabs_<?php echo esc_attr($this->module->getActing()); ?>">
                <div class="settlement_service">
                    <span class="service_title">
                        <?php echo esc_html($this->payment_display_name); ?>
                    </span>
                </div>
        
                <?php if ('' !== $this->error_mes) : ?>
                    <div class="error_message"><?php echo $this->error_mes; ?></div>
                <?php elseif (isset($acting_opts['activate']) && 'on' === $acting_opts['activate']) : ?>
                    <div class="message">
                        <?php echo esc_html__('Thoroughly test your integration before going live.', 'smodule'); ?>
                    </div>
                <?php endif; ?>

                <form
                    action=""
                    method="post"
                    name="<?php echo esc_attr($this->module->getActing()); ?>_form"
                    id="<?php echo esc_attr($this->module->getActing()); ?>_form"
                >
                    <table class="settle_table aivec">
                        <tr class="radio">
                            <th>
                                <?php echo sprintf(
                                    /* translators: %s: formatted plugin name. */
                                    esc_html__('enable/disable %s', 'smodule'),
                                    $this->payment_display_name
                                ); ?>
                            </th>
                            <td>
                                <div>
                                    <label>
                                        <input
                                            name="activate"
                                            type="radio"
                                            id="activate_<?php echo esc_attr($this->module->getActing()); ?>_1"
                                            value="on"
                                            <?php echo $acting_opts['activate'] === 'on' ? 'checked' : ''; ?>
                                        />
                                        <span><?php echo esc_html__('Enable', 'smodule'); ?></span>
                                    </label>
                                    <label>
                                        <input
                                            name="activate"
                                            type="radio"
                                            id="activate_<?php echo esc_attr($this->module->getActing()); ?>_2"
                                            value="off"
                                            <?php echo $acting_opts['activate'] === 'off' ? 'checked' : ''; ?>
                                        />
                                        <span><?php echo esc_html__('Disable', 'smodule'); ?></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <?php $this->settlementModuleFields($acting_opts); ?>
                        <?php if ($this->module->getCapturePaymentOptSupport() === true) : ?>
                            <tr class="radio">
                                <th>
                                    <a class="explanation-label" id="label_ex_payment_capture_type_<?php echo esc_attr($this->module->getActing()); ?>">
                                        <?php echo esc_html__('Processing classification', 'usces'); // 処理区分 ?>
                                    </a>
                                </th>
                                <td>
                                    <div>
                                        <label>
                                            <input
                                                name="payment_capture_type"
                                                type="radio"
                                                id="payment_capture_type_<?php echo esc_attr($this->module->getActing()); ?>_2"
                                                value="after_purchase"
                                                <?php echo $acting_opts['payment_capture_type'] === 'after_purchase' ? 'checked' : ''; ?>
                                            />
                                            <span><?php _e('Credit', 'usces'); // 与信 ?></span>
                                        </label>
                                        <label>
                                            <input
                                                name="payment_capture_type" 
                                                type="radio"
                                                id="payment_capture_type_<?php echo esc_attr($this->module->getActing()); ?>_1"
                                                value="on_purchase"
                                                <?php echo $acting_opts['payment_capture_type'] === 'on_purchase' ? 'checked' : ''; ?>
                                            />
                                            <span><?php _e('Credit sales', 'usces'); // 与信売上計上 ?></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr id="ex_payment_capture_type_<?php echo esc_attr($this->module->getActing()); ?>" class="explanation">
                                <td colspan="2">
                                    <?php _e("In case of 'Credit' setting, it need to change to 'Sales recorded' manually in later. In case of 'Credit sales recorded' setting, sales will be recorded at the time of purchase.", 'usces'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($this->module->canHandleSubscriptionOrders()) : ?>
                            <tr class="radio">
                                <th>
                                    <a class="explanation-label" id="label_ex_recurring_payment_capture_type_<?php echo esc_attr($this->module->getActing()); ?>">
                                        <?php _e('Automatic Continuing Charging Processing Classification', 'usces'); // 自動継続課金処理区分 ?>
                                    </a>
                                </th>
                                <td>
                                    <div>
                                        <label>
                                            <input
                                                name="recurring_payment_capture_type"
                                                type="radio"
                                                id="recurring_payment_capture_type_<?php echo esc_attr($this->module->getActing()); ?>_2"
                                                value="after_purchase"
                                                <?php echo $acting_opts['recurring_payment_capture_type'] === 'after_purchase' ? 'checked' : ''; ?>
                                            />
                                            <span><?php _e('Credit', 'usces'); // 与信 ?></span>
                                        </label>
                                        <label>
                                            <input
                                                name="recurring_payment_capture_type"
                                                type="radio"
                                                id="recurring_payment_capture_type_<?php echo esc_attr($this->module->getActing()); ?>_1"
                                                value="on_purchase"
                                                <?php echo $acting_opts['recurring_payment_capture_type'] === 'on_purchase' ? 'checked' : ''; ?>
                                            />
                                            <span><?php _e('Credit sales', 'usces'); // 与信売上計上 ?></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr id="ex_recurring_payment_capture_type_<?php echo esc_attr($this->module->getActing()); ?>" class="explanation">
                                <td colspan="2">
                                    <?php _e('Processing classification when automatic continuing charging (required WCEX DLSeller).', 'usces'); ?>
                                </td>
                            </tr>
                            <tr class="radio">
                                <th>
                                    <a class="explanation-label" id="label_ex_auto_settlement_mail_<?php echo esc_attr($this->module->getActing()); ?>">
                                        <?php _e('Automatic Continuing Charging Completion Mail', 'usces'); // 自動継続課金完了メール ?>
                                    </a>
                                </th>
                                <td>
                                    <div>
                                        <label>
                                            <input
                                                name="auto_settlement_mail"
                                                type="radio"
                                                id="auto_settlement_mail_<?php echo esc_attr($this->module->getActing()); ?>_1"
                                                value="on"
                                                <?php echo $acting_opts['auto_settlement_mail'] === 'on' ? 'checked' : ''; ?>
                                            />
                                            <span><?php _e('Send', 'usces'); ?></span>
                                        </label>
                                        <label>
                                            <input
                                                name="auto_settlement_mail"
                                                type="radio"
                                                id="auto_settlement_mail_<?php echo esc_attr($this->module->getActing()); ?>_2"
                                                value="off"
                                                <?php echo $acting_opts['auto_settlement_mail'] === 'off' ? 'checked' : ''; ?>
                                            />
                                            <span><?php _e("Don't send", 'usces'); ?></span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr id="ex_auto_settlement_mail_<?php echo esc_attr($this->module->getActing()); ?>" class="explanation">
                                <td colspan="2">
                                    <?php _e('Send billing completion mail to the member on which automatic continuing charging processing (required WCEX DLSeller) is executed.', 'usces'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php
                        ob_start();
                        ?>
                        <tr class="radio">
                            <th>
                                <a class="explanation-label" id="label_ex_sandbox_<?php echo esc_attr($this->module->getActing()); ?>">
                                    <?php echo esc_html__('Environment', 'smodule'); ?>
                                </a>
                            </th>
                            <td>
                                <div>
                                    <label>
                                        <input
                                            name="sandbox" 
                                            type="radio"
                                            id="sandbox_<?php echo esc_attr($this->module->getActing()); ?>_1"
                                            value=""
                                            <?php echo (bool)$acting_opts['sandbox'] === true ? 'checked' : ''; ?>
                                        />
                                        <span><?php echo esc_html__('Test environment', 'smodule'); ?></span>
                                    </label>
                                    <label>
                                        <input
                                            name="sandbox"
                                            type="radio"
                                            id="sandbox_<?php echo esc_attr($this->module->getActing()); ?>_2"
                                            value="1"
                                            <?php echo $acting_opts['sandbox'] === false ? 'checked' : ''; ?>
                                        />
                                        <span><?php echo esc_html__('Production environment', 'smodule'); ?></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr id="ex_sandbox_<?php echo esc_attr($this->module->getActing()); ?>" class="explanation">
                            <td colspan="2">
                                <?php echo esc_html__('Changes between test and production versions.', 'smodule'); ?>
                            </td>
                        </tr>
                        <?php
                        $html = ob_get_contents();
                        $html = $this->filterEnvironmentRow($html, $acting_opts);
                        ob_end_clean();
                        echo $html;
                        ?>
                    </table>
                    <?php $this->displaySettlementModuleProviderRadioSelections(); ?>
                    <?php $this->extraSettings($acting_opts); ?>
                    <input name="acting" id="acting" type="hidden" value="<?php echo esc_attr($this->module->getActing()); ?>" />
                    <input
                        name="usces_option_update"
                        type="submit"
                        class="button button-primary"
                        value="
                            <?php echo sprintf(
                                /* translators: %s: formatted plugin name. */
                                esc_attr__('Update %s Settings', 'smodule'),
                                $this->payment_display_name
                            ); ?>"
                    />
                    <?php wp_nonce_field('admin_settlement', 'wc_nonce'); ?>
                </form>
                <div class="settle_exp">
                    <p>
                        <strong>
                        <?php
                            echo sprintf(
                                /* translators: %s: formatted plugin name. */
                                esc_html__('%s Settlement', 'smodule'),
                                $this->payment_display_name
                            );
                        ?>
                        </strong>
                    </p>
                    <a href="<?php echo esc_url($documentation_url); ?>" target="_blank">
                        <?php
                            echo sprintf(
                                /* translators: %s: formatted plugin name. */
                                esc_html__('Click here for more information about %s', 'smodule'),
                                $this->payment_display_name
                            );
                        ?>
                    </a>
                    <?php $this->moduleDescription($acting_opts); ?>
                </div>
            </div>
            <style>
                table.settle_table.aivec tr.radio td > div {
                    display: flex;
                    flex-flow: column nowrap;
                }
                @media only screen and (max-width: 782px) {
                    table.settle_table.aivec tr.radio td > div > *:not(:last-child) {
                        padding-bottom: 10px;
                    }
                }
            </style>
        <?php endif;
    }

    /**
     * Provider selection box for Settlement Module using `CptmClient` when more than
     * one provider exists
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function displaySettlementModuleProviderRadioSelections() {
        $client = $this->module->getCptmClient();
        if ($client === null) {
            return;
        }
        $providers = $client->getProviders();
        $providers = $providers === null ? [] : $providers;
        $selected = $client->getSelectedProvider();
        if ($selected !== null) {
            $selected = $selected->getIdentifier();
        }
        ob_start();
        ?>
        <tr class="radio">
            <th><?php esc_html_e('Please choose your provider', 'cptmc'); ?></th>
            <td>
                <div>
                <?php foreach ($providers as $provider) : ?>
                    <label>
                        <input
                            name="<?php echo $client->selectedProviderOptName; ?>"
                            type="radio"
                            id="cptmc_provider_<?php echo esc_attr($client->getItemUniqueId() . $provider->getIdentifier()); ?>"
                            value="<?php echo esc_attr($provider->getIdentifier()); ?>"
                            <?php echo $selected === $provider->getIdentifier() ? 'checked' : ''; ?>
                        />
                        <span><?php echo $client->getProviderEndpoint($provider)->getDisplayText(); ?></span>
                    </label>
                <?php endforeach; ?>
                </div>
            </td>
        </tr>
        <?php
        $selectfields = (string)ob_get_clean();
        if (EnvironmentSwitcher\Utils::getEnv() !== 'development') {
            if (empty($providers)) {
                return;
            }

            $this->providerSelectionWrapper($selectfields);
            return;
        }

        ob_start();
        if ($client instanceof ServerControlled) : ?>
            <tr>
                <th><?php esc_html_e('Testing Providers URL', 'cptmc'); ?></th>
                <td>
                    <?php $optname = $client->providersUrlOverrideOptName; ?>
                    <input
                        type="text"
                        size="60"
                        name="<?php echo $optname; ?>"
                        id="<?php echo $optname; ?>"
                        value="<?php form_option($optname); ?>"
                    />
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <th><?php esc_html_e('Testing Update URL', 'cptmc'); ?></th>
            <td>
                <?php $optname = $client->updateUrlOverrideOptName; ?>
                <input
                    type="text"
                    size="60"
                    name="<?php echo $optname; ?>"
                    id="<?php echo $optname; ?>"
                    value="<?php form_option($optname); ?>"
                />
            </td>
        </tr>
        <?php
        $devfields = (string)ob_get_clean();
        $this->providerSelectionWrapper($selectfields . $devfields);
    }

    /**
     * Wraps providers section with a table and displays it
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $innerhtml
     * @return void
     */
    public function providerSelectionWrapper($innerhtml) {
        ?>
        <div class="settlement_service">
            <span class="service_title">
                <?php esc_html_e('Authentication', 'smodule'); ?>
            </span>
        </div>
        <table class="settle_table aivec">
            <?php echo $innerhtml; ?>
        </table>
        <?php
    }

    /**
     * Update usces settlement options with config
     * 決済オプション登録・更新
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function settlementUpdate() {
        global $usces;

        if (!isset($_POST['acting'])) {
            return;
        }

        if ($this->module->getActing() !== $_POST['acting']) {
            return;
        }

        $cptmc = $this->module->getCptmClient();
        if ($cptmc !== null) {
            $cptmc->updateOptionsWithPost();
        }

        $options = $this->module->getActingOpts();
        $options['activate'] = isset($_POST['activate']) ? sanitize_text_field(wp_unslash($_POST['activate'])) : $options['activate'];
        $options['sandbox'] = empty($_POST['sandbox']) ? true : false;
        if ($this->module->getCapturePaymentOptSupport() === true) {
            $options['payment_capture_type'] = isset($_POST['payment_capture_type']) ? $_POST['payment_capture_type'] : $options['payment_capture_type'];
        }
        if ($this->module->canHandleSubscriptionOrders() === true) {
            $options['recurring_payment_capture_type'] = isset($_POST['recurring_payment_capture_type']) ? $_POST['recurring_payment_capture_type'] : $options['recurring_payment_capture_type'];
            $options['auto_settlement_mail'] = isset($_POST['auto_settlement_mail']) ? $_POST['auto_settlement_mail'] : $options['auto_settlement_mail'];
        }
        $options = $this->filterUpdateOptionsProcessing($options);

        $this->error_mes = '';
        $this->error_mes = $this->validateFormPost($this->error_mes);

        if (\WCUtils::is_blank($this->error_mes)) {
            $usces->action_status = 'success';
            $usces->action_message = __('options are updated', 'usces');
            if ('on' === $options['activate']) {
                $usces->payment_structure[$this->module->getActingFlag()] = sprintf(
                    /* translators: %s: formatted plugin name. */
                    esc_html__('%s Settlement', 'smodule'),
                    $this->payment_display_name
                );
                $this->onSettingsUpdateActivate($options);
            } else {
                $options['activate'] = 'off';
                unset($usces->payment_structure[$this->module->getActingFlag()]);
                $this->onSettingsUpdateDeactivate($options);
            }
        } else {
            $usces->action_status = 'error';
            $usces->action_message = __('Data have deficiency.', 'usces');
            $options['activate'] = 'off';
            unset($usces->payment_structure[$this->module->getActingFlag()]);
            $this->onSettingsUpdateDeactivate($options);
        }

        $this->module->updateActingOpts($options);
        ksort($usces->payment_structure);
        update_option('usces_payment_structure', $usces->payment_structure);
    }

    /**
     * Called when settings are successfully updated and the payment module is activated
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $options
     * @return void
     */
    protected function onSettingsUpdateActivate(array $options) {
    }

    /**
     * Called when settings are updated and either an error occurs or the payment module
     * is deactivated
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $options
     * @return void
     */
    protected function onSettingsUpdateDeactivate(array $options) {
    }

    /**
     * Filters the documentation url
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $url
     * @return string
     */
    protected function filterDocumentationUrl($url) {
        return $url;
    }

    /**
     * Override to add more config options
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $acting_opts
     * @return void
     */
    protected function settlementModuleFields($acting_opts) {
    }

    /**
     * Filter the aauth provider select row
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $html
     * @param array  $acting_opts
     * @return string
     */
    protected function filterAauthRow($html, $acting_opts) {
        return $html;
    }

    /**
     * Filter the environment select row
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $html
     * @param array  $acting_opts
     * @return string
     */
    protected function filterEnvironmentRow($html, $acting_opts) {
        return $html;
    }

    /**
     * Override to add extra tables/options
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $acting_opts
     * @return void
     */
    protected function extraSettings($acting_opts) {
    }

    /**
     * Override to add a description section for the module
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $acting_opts
     * @return void
     */
    protected function moduleDescription($acting_opts) {
    }

    /**
     * Filter options with POST request
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $options
     * @return array
     */
    protected function filterUpdateOptionsProcessing($options) {
        return $options;
    }

    /**
     * Filter error message after form validation
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $error_message
     * @return string
     */
    protected function validateFormPost($error_message) {
        return $error_message;
    }
}
