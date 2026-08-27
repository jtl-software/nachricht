<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

/**
 * Same shape as IntegrationTestMessage, but a distinct class -> distinct static routing key
 * -> distinct queue. Used by QueueIsolationTest to prove the delayed exchange (x-delayed-type:
 * direct) doesn't cross-deliver between two independently bound queues.
 */
final class IntegrationTestMessageAlt extends IntegrationTestMessage
{
}
