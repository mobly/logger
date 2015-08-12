<?php namespace Mobly\Logger\Handler;

use Monolog\Handler\MissingExtensionException;

/**
 * Class to record a log on a NewRelic application.
 * Enabling New Relic High Security mode may prevent capture of useful information.
 *
 * @author Caio Costa <caio.costa@mobly.com.br>
 *
 * @see https://docs.newrelic.com/docs/agents/php-agent
 * @see https://docs.newrelic.com/docs/accounts-partnerships/accounts/security/high-security
 */
class NewRelicHandler extends \Monolog\Handler\NewRelicHandler
{
    /**
     * {@inheritDoc}
     */
    protected function write(array $record)
    {
        if (!$this->isNewRelicEnabled()) {
            throw new MissingExtensionException('The newrelic PHP extension is required to use the NewRelicHandler');
        }

        if ($appName = $this->getAppName($record['context'])) {
            $this->setNewRelicAppName($appName);
        }

        if ($transactionName = $this->getTransactionName($record['context'])) {
            $this->setNewRelicTransactionName($transactionName);
            unset($record['context']['transaction_name']);
        }

        $this->noticeUid($record);

        $this->noticeException($record);

        $this->noticeSection($record, 'context');
        $this->noticeSection($record, 'extra');
        $this->noticeSection($record, 'static');
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private function normalizeValue($value)
    {
        if (false === is_scalar($value)) {
            if (is_array($value) || $value instanceof \JsonSerializable) {
                $value = json_encode($value);
            } else {
                $value = preg_replace("/\]\=\>\n(\s+)/m", "] => ", print_r($value, true));
            }
        } elseif (is_numeric($value)) {
            $value = (string) $value;
        }

        return $value;
    }

    /**
     * @param array $record
     */
    private function noticeException(array &$record)
    {
        if (!empty($record['exception']) && $record['exception'] instanceof \Exception) {
            newrelic_notice_error($record['exception']->getMessage(), $record['exception']);
            unset($record['exception']);
        } elseif (!empty($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
            newrelic_notice_error($record['context']['exception']->getMessage(), $record['context']['exception']);
            unset($record['context']['exception']);
        } elseif (!empty($record['extra']['exception']) && $record['extra']['exception'] instanceof \Exception) {
            newrelic_notice_error($record['extra']['exception']->getMessage(), $record['extra']['exception']);
            unset($record['extra']['exception']);
        } elseif (!empty($record['message'])) {
            newrelic_notice_error($record['message']);
        }
    }

    /**
     * @param array $record
     * @return bool
     */
    private function noticeUid(array &$record)
    {
        if (!empty($record['uid'])) {
            newrelic_add_custom_parameter('uid', $record['uid']);
            unset($record['uid']);
        } elseif (!empty($record['context']['uid'])) {
            newrelic_add_custom_parameter('uid', $record['context']['uid']);
            unset($record['context']['uid']);
        } elseif (!empty($record['extra']['uid'])) {
            newrelic_add_custom_parameter('uid', $record['extra']['uid']);
            unset($record['extra']['uid']);
        }
    }

    /**
     * @param array $record
     * @param string $prefix
     */
    private function noticeSection(array $record, $prefix)
    {
        if (!isset($record[$prefix]) || !is_array($record[$prefix])) {
            return;
        }

        foreach ($record[$prefix] as $key => $parameter) {
            if (is_array($parameter) && $this->explodeArrays) {
                foreach ($parameter as $paramKey => $paramValue) {
                    newrelic_add_custom_parameter($prefix . '_' . $key . '_' . $paramKey, $this->normalizeValue($paramValue));
                }
            } else {
                newrelic_add_custom_parameter($prefix . '_' . $key, $this->normalizeValue($parameter));
            }
        }
    }
}
