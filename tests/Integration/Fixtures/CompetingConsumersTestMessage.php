<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

/**
 * Message for CompetingConsumersTest.
 *
 * Own class purely to own its own routing key: getRoutingKey() is derived from the class name,
 * so a dedicated class means a dedicated queue and no interference from any other test.
 */
final class CompetingConsumersTestMessage extends IntegrationTestMessage
{
}
