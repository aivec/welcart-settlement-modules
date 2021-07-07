<?php

namespace Aivec\Welcart\SettlementModules\Helpers;

trait SerializeTargetSetter
{
    /**
     * Either `display` or `db`
     *
     * May also be a custom user defined target
     *
     * @var string
     */
    private $serializeTarget = 'display';

    /**
     * Returns current target
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getSerializeTarget() {
        return $this->serializeTarget;
    }

    /**
     * Returns `true` if the current serialize target is `display`, `false` otherwise
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function serializeTargetIsDisplay() {
        return $this->serializeTarget === 'display';
    }

    /**
     * Returns `true` if the current serialize target is `db`, `false` otherwise
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function serializeTargetIsDb() {
        return $this->serializeTarget === 'db';
    }

    /**
     * Sets to a custom target
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $target
     * @return void
     */
    public function setSerializeTarget($target) {
        $this->serializeTarget = $target;
    }

    /**
     * Sets target to `db`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function setSerializeTargetToDb() {
        $this->serializeTarget = 'db';
    }

    /**
     * Sets target to `display`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function setSerializeTargetToDisplay() {
        $this->serializeTarget = 'display';
    }
}
