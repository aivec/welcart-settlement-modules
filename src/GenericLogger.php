<?php
namespace Aivec\Welcart\SettlementModules;

/**
 * Logger for errors of any kind (not necessarily related to transactions).
 *
 * Implements `psr/log` with Welcart's `usces_log` function
 */
class GenericLogger extends Logger {

    /**
     * Constructs `Logger` with the log file set to `$module->getActing() . '.log'`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @return void
     */
    public function __construct(Module $module) {
        $file = $module->getActing() . '.log';
        $logdir = USCES_PLUGIN_DIR . '/logs';
        $filep = $logdir . '/' . $file;
        if (!file_exists($filep)) {
            file_put_contents($filep, '');
        }
        parent::__construct($module, $module->getActing() . '.log');
    }
}
