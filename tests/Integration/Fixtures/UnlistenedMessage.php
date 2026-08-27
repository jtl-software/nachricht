<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

/**
 * Deliberately never registered in a MessageCache, so ListenerProvider::eventHasListeners()
 * returns false for it and AmqpTransport routes it to missing_listener__<routingKey>.
 */
final class UnlistenedMessage extends IntegrationTestMessage
{
}
