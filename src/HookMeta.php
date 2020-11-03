<?php
namespace Aivec\Welcart\SettlementModules;

use InvalidArgumentException;

/**
 * Holds meta data information about an action or filter hook
 */
final class HookMeta {

    /**
     * An array of method names that are invoked by the action/filter called
     * by the `$hook` parameter passed to this class
     *
     * @var array
     */
    private $methodsInvokedByHook;

    /**
     * A callable that invokes either `add_action` or `add_filter`
     *
     * @var callable
     */
    private $hook;

    /**
     * Constructs meta object
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array    $methodsInvokedByHook
     * @param callable $hook
     * @return void
     * @throws InvalidArgumentException Thrown if any of the values in `$methodsInvokedByHook` are not a `string`.
     */
    public function __construct(array $methodsInvokedByHook, callable $hook) {
        foreach ($methodsInvokedByHook as $m) {
            if (!is_string($m)) {
                throw new InvalidArgumentException('$methodsInvokedByHook parameter must only contain strings');
            }
        }

        $this->methodsInvokedByHook = $methodsInvokedByHook;
        $this->hook = $hook;
    }

    /**
     * Getter for `$methodsInvokedByHook`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function getMethodsInvokedByHook() {
        return $this->methodsInvokedByHook;
    }

    /**
     * Getter for `$hook` callable
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return callable
     */
    public function getHook() {
        return $this->hook;
    }

    /**
     * Invokes `$hook` callable
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function callHook() {
        call_user_func($this->hook);
    }
}
