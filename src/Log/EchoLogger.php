<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2020/04/17
 */

namespace JTL\Nachricht\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class EchoLogger implements LoggerInterface
{
    /**
     * @inheritDoc
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @inheritDoc
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->printMessage($level, $message, $context);

        switch ($level) {
            case LogLevel::WARNING:
            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
                $this->logErrorMessage($level, $message, $context);
                break;
        }
    }

    /**
     * @param string $severity
     * @param mixed $message
     * @param array<mixed> $context
     */
    private function logErrorMessage(string $severity, $message, array $context = []): void
    {
        error_log($this->generateLogMessage($severity, (string)$message, $context));
    }

    /**
     * @param string $severity
     * @param mixed $message
     * @param array<mixed> $context
     */
    private function printMessage(string $severity, $message, array $context = []): void
    {
        echo "{$this->generateLogMessage($severity, (string)$message, $context)}\n";
    }

    /**
     * @param string $severity
     * @param string $message
     * @param mixed[] $context
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
