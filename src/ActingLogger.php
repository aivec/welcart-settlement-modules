<?php
namespace Aivec\Welcart\SettlementModules;

/**
 * Module logger for acting errors, implements `psr/log` with Welcart's `usces_log` function
 */
class ActingLogger extends Logger {

    /**
     * Constructs a `Logger` with the log file set to `acting_transaction.log`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @return void
     */
    public function __construct(Module $module) {
        parent::__construct($module, 'acting_transaction.log');
    }
}
