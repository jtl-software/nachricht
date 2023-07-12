<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: marius
 * Date: 3/10/23
 */

namespace JTL\Nachricht\Message;

use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Contract\Message\MessageCounter;

class DefaultMessageCounter implements MessageCounter
{
    public function countMessage(Message $message): void
    {
    }
}
