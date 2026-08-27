<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

/**
 * Minimal wrapper around the RabbitMQ management HTTP API, used only to purge queues between
 * test cases so tests sharing a routing key (and therefore a queue) don't see each other's
 * leftover messages. Deliberately dependency-free (stream context instead of ext-curl/guzzle).
 */
final class RabbitMqManagementClient
{
    public function __construct(
        private readonly string $host,
        private readonly string $httpPort,
        private readonly string $user = 'guest',
        private readonly string $password = 'guest',
    ) {
    }

    public function purgeQueue(string $queueName): void
    {
        // 404 if the queue was never declared yet - that's fine, nothing to purge.
        $this->request('DELETE', '/api/queues/%2F/' . rawurlencode($queueName) . '/contents');
    }

    private function request(string $method, string $path): void
    {
        $url = "http://{$this->host}:{$this->httpPort}{$path}";
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => 'Authorization: Basic ' . base64_encode("{$this->user}:{$this->password}") . "\r\n",
                'ignore_errors' => true,
                'timeout' => 5,
            ],
        ]);

        @file_get_contents($url, false, $context);
    }
}
