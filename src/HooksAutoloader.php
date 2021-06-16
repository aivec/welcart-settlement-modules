<?php

namespace Aivec\Welcart\SettlementModules;

use ReflectionClass;
use ReflectionException;

/**
 * A trait for any class that adds action or filter hooks
 */
trait HooksAutoloader
{
    /**
     * Loops through `$hookMetaMap` and invokes `dynamicallyRegisterHook` method
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $hookMetaMap
     * @return void
     */
    public function dynamicallyRegisterHooks(array $hookMetaMap) {
        $currentInstance = new ReflectionClass($this);
        foreach ($hookMetaMap as $hookMeta) {
            $this->dynamicallyRegisterHook($hookMeta, $currentInstance);
        }
    }

    /**
     * Finds all parent methods invoked by an action/filter hook that are redeclared in a child class
     *
     * If a redeclaration is found, the corresponding action/filter hook will be added.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param HookMeta        $hookMeta
     * @param ReflectionClass $currentInstance
     * @return void
     */
    public function dynamicallyRegisterHook(HookMeta $hookMeta, $currentInstance) {
        foreach ($hookMeta->getMethodsInvokedByHook() as $methodName) {
            try {
                // will throw exception if method doesn't exist
                $method = $currentInstance->getMethod($methodName);
                $parent = $currentInstance->getParentClass();
                // if current instance is not an extended class, optional hooks wont do anything anyways...
                if ($parent !== false) {
                    // only register hook if declared in a child class
                    if ($method->class !== $parent->name) {
                        if (is_callable($hookMeta->getHook())) {
                            // registers the action or filter
                            $hookMeta->callHook();
                            break;
                        }
                    }
                }
            } catch (ReflectionException $e) {
                // method doesn't exist...
            }
        }
    }
}
