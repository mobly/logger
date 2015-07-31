<?php namespace Mobly\Logger\Processor;

use Mobly\Logger\Uid;

/**
 * Adds a unique identifier into records
 */
class UidProcessor extends \Monolog\Processor\UidProcessor
{
    /**
     * @var string
     */
    private $uid;

    public function __construct($key = null)
    {
        $this->uid = (string) Uid::instance($key);
    }

    /**
     * @param array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['extra']['uid'] = (string) $this->uid;

        return $record;
    }
}
