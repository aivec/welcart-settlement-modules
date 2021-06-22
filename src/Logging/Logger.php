<?php

namespace Aivec\Welcart\SettlementModules\Logging;

use Aivec\Welcart\SettlementModules\Module;
use Psr\Log\AbstractLogger;

/**
 * Module logger, implements `psr/log` with Welcart's `usces_log` function
 */
class Logger extends AbstractLogger
{
    /**
     * Acting `Module` instance
     *
     * @var Module
     */
    private $module;

    /**
     * Name of the log file
     *
     * @var string
     */
    public $logfilename;

    /**
     * Instantiates logger
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Module $module
     * @param string $logfilename
     * @return void
     */
    public function __construct(Module $module, $logfilename) {
        $this->module = $module;
        $this->logfilename = $logfilename;
    }

    /**
     * Uses Welcart's `usces_log` function to log errors/notices/etc.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $level
     * @param mixed  $message
     * @param array  $context
     * @return void
     */
    public function log($level, $message, array $context = []) {
        $logm = $this->module->getPaymentName() . ' [' . $level . ']: ' . (string)$message;
        usces_log($logm, $this->logfilename);
    }
}
