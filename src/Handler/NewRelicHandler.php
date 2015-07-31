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
        } else {
            $this->setNewRelicAppName(\Config::get('services.newrelic.appname'));
        }

        if ($transactionName = $this->getTransactionName($record['context'])) {
            $this->setNewRelicTransactionName($transactionName);
            unset($record['context']['transaction_name']);
        }

        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
            newrelic_notice_error($record['context']['exception']->getMessage(), $record['context']['exception']);
            unset($record['context']['exception']);
        } else {
            newrelic_notice_error($record['message']);
        }

        if (isset($record['extra']['uid'])) {
            newrelic_add_custom_parameter('uid', $record['extra']['uid']);
            unset($record['extra']['uid']);
        }

        if (isset($record['static'])) {
            foreach ($record['static'] as $key => $parameter) {
                newrelic_add_custom_parameter('static_' . $key, $parameter);
            }
        }

        foreach ($record['context'] as $key => $parameter) {
            if (is_array($parameter) && $this->explodeArrays) {
                foreach ($parameter as $paramKey => $paramValue) {
                    newrelic_add_custom_parameter(
                        'context_' . $key . '_' . $paramKey,
                        $this->normalizeValue($paramValue)
                    );
                }
            } else {
                newrelic_add_custom_parameter(
                    'context_' . $key,
                    $this->normalizeValue($parameter)
                );
            }
        }

        foreach ($record['extra'] as $key => $parameter) {
            if (is_array($parameter) && $this->explodeArrays) {
                foreach ($parameter as $paramKey => $paramValue) {
                    newrelic_add_custom_parameter('extra_' . $key . '_' . $paramKey, $paramValue);
                }
            } else {
                newrelic_add_custom_parameter('extra_' . $key, $parameter);
            }
        }
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function normalizeValue($value)
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
}
