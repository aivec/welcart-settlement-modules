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
     * Reflection of subclass instance
     *
     * @var ReflectionClass|null
     */
    private $child = null;

    /**
     * Loops through `$hookMetaMap` and invokes `dynamicallyRegisterHook` method
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array $hookMetaMap
     * @return void
     */
    public function dynamicallyRegisterHooks(array $hookMetaMap) {
        $this->child = new ReflectionClass($this);
        foreach ($hookMetaMap as $hookMeta) {
            $this->dynamicallyRegisterHook($hookMeta);
        }
    }

    /**
     * Finds all parent methods invoked by an action/filter hook that are redeclared in child class
     *
     * If a redeclaration is found, the corresponding action/filter hook will be added.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param HookMeta $hookMeta
     * @return void
     */
    public function dynamicallyRegisterHook(HookMeta $hookMeta) {
        if ($this->child === null) {
            $this->child = new ReflectionClass($this);
        }
        foreach ($hookMeta->getMethodsInvokedByHook() as $methodName) {
            try {
                $childm = $this->child->getMethod($methodName);
                $declaringClass = $childm->getDeclaringClass()->getName();
                if ($declaringClass === $this->child->getName()) {
                    if (is_callable($hookMeta->getHook())) {
                        $hookMeta->callHook();
                        break;
                    }
                }
            } catch (ReflectionException $e) {
                // method doesn't exist...
            }
        }
    }
}
