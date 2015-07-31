<?php namespace Mobly\Logger;

/**
 * Generates an unique identifier to application execution
 *
 * @package Mobly\Logger
 * @author
 */
class Uid
{
    /**
     * @var string
     */
    private $uid;

    /**
     * @var Uid
     */
    private static $instance;

    /**
     * @param string $key
     */
    public function __construct($key = null)
    {
        $time = microtime(true);
        $key = ($key ?: uniqid());

        $this->uid = sprintf("%s-%6x-%06x", substr(md5($key), 0, 5), floor($time), ($time - floor($time)) * 1000000);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->uid;
    }

    /**
     * @param string $key
     * @return Uid
     */
    public static function instance($key = null)
    {
        if (null === static::$instance) {
            static::$instance = new static($key);
        }

        return static::$instance;
    }
}
