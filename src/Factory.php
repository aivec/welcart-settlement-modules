<?php
namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;

/**
 * Settlement Module registration factory
 */
class Factory {

    /**
     * The settlement module
     *
     * @var Module
     */
    protected $module;

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
     * @throws InvalidArgumentException Thrown if module is not an instance of `Module`.
     */
    public function __construct(Module $module) {
        $this->module = $module;
        add_action('usces_action_settlement_tab_title', [$this, 'settlementTabTitle']);
        add_action('usces_action_settlement_tab_body', [$this, 'settlementTabBody']);
        add_action('usces_action_admin_settlement_update', [$this, 'settlementUpdate']);

        $this->setAvailableSettlement();
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
                    <?php echo esc_html($this->module->getPaymentName()); ?>
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
            $available_settlement[$this->module->getActing()] = $this->module->getPaymentName();
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
                        <?php echo esc_html($this->module->getPaymentName()); ?>
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
                                    $this->module->getPaymentName()
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
                                            <?php echo (boolean)$acting_opts['sandbox'] === true ? 'checked' : ''; ?>
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
                    <?php
                    if ($this->module->getAauth() !== null) {
                        echo $this->filterAauthRow(
                            $this->module->getAauth()->getSellers()->getSettlementModuleSellerRadioSelections(),
                            $acting_opts
                        );
                    }
                    ?>
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
                                $this->module->getPaymentName()
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
                                $this->module->getPaymentName()
                            );
                        ?>
                        </strong>
                    </p>
                    <a href="<?php echo esc_url($documentation_url); ?>" target="_blank">
                        <?php
                            echo sprintf(
                                /* translators: %s: formatted plugin name. */
                                esc_html__('Click here for more information about %s', 'smodule'),
                                $this->module->getPaymentName()
                            );
                        ?>
                    </a>
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

        $options = $this->module->getActingOpts();
        $options['activate'] = isset($_POST['activate']) ? sanitize_text_field(wp_unslash($_POST['activate'])) : $options['activate'];
        $options['sandbox'] = empty($_POST['sandbox']) ? true : false;
        if ($this->module->getAauth() !== null) {
            $this->module->getAauth()->getSellers()->updateAuthProvider();
        }
        if ($this->module->getCapturePaymentOptSupport() === true) {
            $options['payment_capture_type'] = isset($_POST['payment_capture_type']) ? $_POST['payment_capture_type'] : $options['payment_capture_type'];
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
                    $this->module->getPaymentName()
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