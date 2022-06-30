<?php
/**
 * This file is part of the jtl-software/nachricht
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) JTL-Software-GmbH
 * @author rherrgesell
 * @license http://opensource.org/licenses/MIT MIT
 * @link https://packagist.org/packages/jtl/nachricht Packagist
 * @link https://github.com/jtl-software/nachricht GitHub
 */

namespace JTL\Nachricht\Contract\Message;

interface AmqpTransportableMessage extends Message
{
    public static function getRoutingKey(): string;

    public function getExchange(): string;

    public function setExchange(string $exchange): void;

    public function getMessageId(): string;

    public function setLastError(string $errorMessage): void;

    public function isDeadLetter(): bool;

    public function getRetryCount(): int;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getRetryDelay(): int;

    public function getDelay(): int;
}
