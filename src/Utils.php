<?php

namespace Aivec\Welcart\SettlementModules;

/**
 * Utility methods for settlement modules
 */
class Utils
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const DEFAULT_TIME_ZONE = 'Asia/Tokyo';

    /**
     * Returns a `Y-m-d H:i:s` formatted local date time string given a UNIX timestamp
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param int $unixt
     * @return string|int
     */
    public static function getLocalDateTimeFromUnixTimestamp($unixt) {
        $timezone = new \DateTimeZone(self::DEFAULT_TIME_ZONE);
        if (function_exists('wp_timezone')) {
            $timezone = wp_timezone();
        }

        try {
            return (new \DateTime('@' . $unixt))->setTimezone($timezone)->format(self::DATETIME_FORMAT);
        } catch (\Exception $e) {
            // return as-is in case of an exception
            return $unixt;
        }
    }
}
