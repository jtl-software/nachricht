<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2020/04/17
 */

namespace JTL\Nachricht\Log;

use Psr\Log\LoggerInterface;

class EchoLogger implements LoggerInterface
{

    /**
     * @inheritDoc
     */
    public function emergency($message, array $context = array())
    {
        $this->logErrorMessage('EMERGENCY', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function alert($message, array $context = array())
    {
        $this->logErrorMessage('ALERT', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function critical($message, array $context = array())
    {
        $this->logErrorMessage('CRITICAL', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function error($message, array $context = array())
    {
        $this->logErrorMessage('ERROR', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function warning($message, array $context = array())
    {
        $this->logErrorMessage('WARNING', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function notice($message, array $context = array())
    {
        $this->logErrorMessage('NOTICE', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = array())
    {
        $this->printMessage('INFO', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function debug($message, array $context = array())
    {
        $this->printMessage('DEBUG', $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array())
    {
        $this->printMessage('LOG', $message, $context);
    }

    /**
     * @param string $severity
     * @param mixed $message
     * @param array $context
     */
    private function logErrorMessage(string $severity, $message, array $context = []): void
    {
        error_log($this->generateLogMessage($severity, (string)$message, $context));
    }

    /**
     * @param string $severity
     * @param mixed $message
     * @param array $context
     */
    private function printMessage(string $severity, $message, array $context = []): void
    {
        echo "{$this->generateLogMessage($severity, (string)$message, $context)}\n";
    }

    /**
     * @param string $severity
     * @param string $message
     * @param array $context
     * @return string
     */
    private function generateLogMessage(string $severity, string $message, array $context = []): string
    {
        $timestamp = date('c');
        $messageContext = '';

        if (count($context) > 0) {
            $messageContext = '(' . var_export($context, true) . ')';
        }

        return "[{$timestamp}][{$severity}]{$message} {$messageContext}";
    }
}
