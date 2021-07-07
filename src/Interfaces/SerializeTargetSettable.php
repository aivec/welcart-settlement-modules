<?php

namespace Aivec\Welcart\SettlementModules\Interfaces;

interface SerializeTargetSettable
{
    /**
     * Returns current target
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getSerializeTarget();

    /**
     * Returns `true` if the current serialize target is `display`, `false` otherwise
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function serializeTargetIsDisplay();

    /**
     * Returns `true` if the current serialize target is `db`, `false` otherwise
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return bool
     */
    public function serializeTargetIsDb();

    /**
     * Sets to a custom target
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $target
     * @return void
     */
    public function setSerializeTarget($target);

    /**
     * Sets target to `db`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function setSerializeTargetToDb();

    /**
     * Sets target to `display`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function setSerializeTargetToDisplay();
}
