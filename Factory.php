<?php
namespace Aivec\Welcart\SettlementModules;

use Exception;

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
     * @throws Exception Thrown if module is not an instance of Aivec\Welcart\SettlementModules\Module.
     */
    public function __construct($module) {
        if (!($module instanceof Module)) {
            throw new Exception('the provided module is not an instance of Aivec\Welcart\SettlementModules\Module');
        }

        $this->module = $module;
        add_action('usces_action_settlement_tab_title', array( $this, 'settlementTabTitle' ));
        add_action('usces_action_settlement_tab_body', array( $this, 'settlementTabBody' ));
        add_action('usces_action_admin_settlement_update', array( $this, 'settlementUpdate' ));

        $this->setAvailableSettlement();
    }

    /**
     * Hook Source: Welcart
     * Hook func: usces_action_settlement_tab_title
     *
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
     * Hook Source: Welcart
     * Hook func: usces_filter_available_settlement
     *
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
     * Hook Source: Welcart
     * Hook func: usces_action_settlement_tab_body
     *
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

        $options = get_option('usces');

        $acting_opts
            = isset($options[Module::SETTINGS_KEY][$this->module->getActing()])
            ? $options[Module::SETTINGS_KEY][$this->module->getActing()]
            : array();
    
        $acting_opts['activate'] = isset($acting_opts['activate']) ? $acting_opts['activate'] : 'off';
        $acting_opts['sandbox'] = isset($acting_opts['sandbox']) ? $acting_opts['sandbox'] : true;
        $acting_opts = $this->filterActingOptions($acting_opts);

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
                    <table class="settle_table">
                        <tr>
                            <th>
                                <?php echo sprintf(
                                    /* translators: %s: formatted plugin name. */
                                    esc_html__('enable/disable %s', 'smodule'),
                                    $this->module->getPaymentName()
                                ); ?>
                            </th>
                            <td>
                                <input
                                    name="activate"
                                    type="radio"
                                    id="activate_<?php echo esc_attr($this->module->getActing()); ?>_1"
                                    value="on"
                                    <?php echo $acting_opts['activate'] === 'on' ? 'checked' : ''; ?>
                                />
                            </td>
                            <td>
                                <label for="activate_<?php echo esc_attr($this->module->getActing()); ?>_1">
                                    <?php echo esc_html__('Enable', 'smodule'); ?>
                                </label>
                            </td>
                            <td>
                                <input
                                    name="activate"
                                    type="radio"
                                    id="activate_<?php echo esc_attr($this->module->getActing()); ?>_2"
                                    value="off"
                                    <?php echo $acting_opts['activate'] === 'off' ? 'checked' : ''; ?>
                                />
                            </td>
                            <td>
                                <label for="activate_<?php echo esc_attr($this->module->getActing()); ?>_2">
                                    <?php echo esc_html__('Disable', 'smodule'); ?>
                                </label>
                            </td>
                            <td></td>
                        </tr>
                        <?php $this->settlementModuleFields($acting_opts); ?>
                        <tr>
                            <th>
                                <a
                                    style="cursor:pointer;"
                                    onclick="toggleVisibility('ex_sandbox_<?php echo esc_attr($this->module->getActing()); ?>');"
                                >
                                    <?php echo esc_html__('Environment', 'smodule'); ?>
                                </a>
                            </th>
                            <td>
                                <input
                                    name="sandbox" 
                                    type="radio"
                                    id="sandbox_<?php echo esc_attr($this->module->getActing()); ?>_1"
                                    value=""
                                    <?php echo (boolean)$acting_opts['sandbox'] === true ? 'checked' : ''; ?>
                                />
                            </td>
                            <td>
                                <label for="sandbox_<?php echo esc_attr($this->module->getActing()); ?>_1">
                                    <?php echo esc_html__('Test environment', 'smodule'); ?>
                                </label>
                            </td>
                            <td>
                                <input
                                    name="sandbox"
                                    type="radio"
                                    id="sandbox_<?php echo esc_attr($this->module->getActing()); ?>_2"
                                    value="1"
                                    <?php echo $acting_opts['sandbox'] === false ? 'checked' : ''; ?>
                                />
                            </td>
                            <td>
                                <label for="sandbox_<?php echo esc_attr($this->module->getActing()); ?>_2">
                                    <?php echo esc_html__('Production environment', 'smodule'); ?>
                                </label>
                            </td>
                            <td>
                                <div id="ex_sandbox_<?php echo esc_attr($this->module->getActing()); ?>" class="explanation">
                                    <?php echo esc_html__('Changes between test and production versions.', 'smodule'); ?>
                                </div>
                            </td>
                        </tr>
                    </table>
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
                    <p>
                        <?php
                            echo sprintf(
                                /* translators: %s: formatted plugin name. */
                                esc_html__('This module is for %s', 'smodule'),
                                $this->module->getPaymentName()
                            );
                        ?>
                    </p>
                </div>
            </div>
        <?php endif;
    }
    
    /**
     * Hook Source: Welcart
     * Hook func: usces_action_admin_settlement_update
     *
     * Update usces settlement options with config
     * 決済オプション登録・更新
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function settlementUpdate() {
        global $usces;

        if (isset($_POST['acting'])) {
            if ($this->module->getActing() !== $_POST['acting']) {
                return;
            }
        }

        $options = get_option('usces');
        $options[Module::SETTINGS_KEY][$this->module->getActing()]['activate'] = isset($_POST['activate']) ? sanitize_text_field(wp_unslash($_POST['activate'])) : '';
        $options[Module::SETTINGS_KEY][$this->module->getActing()]['sandbox'] = empty($_POST['sandbox']) ? true : false;
        $options[Module::SETTINGS_KEY][$this->module->getActing()] = $this->filterUpdateOptionsProcessing($options[Module::SETTINGS_KEY][$this->module->getActing()]);
        
        $this->error_mes = '';
        $this->error_mes = $this->validateFormPost($this->error_mes);
            
        if (\WCUtils::is_blank($this->error_mes)) {
            $usces->action_status = 'success';
            $usces->action_message = __('options are updated', 'usces');
            if ('on' === $options[Module::SETTINGS_KEY][$this->module->getActing()]['activate']) {
                $usces->payment_structure[$this->module->getActingFlag()] = sprintf(
                    /* translators: %s: formatted plugin name. */
                    esc_html__('%s Settlement', 'smodule'),
                    $this->module->getPaymentName()
                );
            } else {
                $options[Module::SETTINGS_KEY][$this->module->getActing()]['activate'] = 'off';
                unset($usces->payment_structure[$this->module->getActingFlag()]);
            }
        } else {
            $usces->action_status = 'error';
            $usces->action_message = __('Data have deficiency.', 'usces');
            $options[Module::SETTINGS_KEY][$this->module->getActing()]['activate'] = 'off';
            unset($usces->payment_structure[$this->module->getActingFlag()]);
        }
        update_option('usces', $options);
        ksort($usces->payment_structure);
        update_option('usces_payment_structure', $usces->payment_structure);
    }

    /**
     * Filters the settlement acting_settings option array
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $acting_opts
     * @return array
     */
    protected function filterActingOptions($acting_opts) {
        return $acting_opts;
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
    protected function settlementModuleFields($acting_opts) {}

    /**
     * Override to add extra tables/options
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $acting_opts
     * @return void
     */
    protected function extraSettings($acting_opts) {}

    /**
     * Filter options with POST request
     *
     * @param array $options
     * @return array
     */
    protected function filterUpdateOptionsProcessing($options) {
        return $options;
    }

    /**
     * Filter error message after form validation
     *
     * @param string $error_message
     * @return string
     */
    protected function validateFormPost($error_message) {
        return $error_message;
    }
}
