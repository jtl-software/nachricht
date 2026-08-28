<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

/**
 * Minimal wrapper around the RabbitMQ management HTTP API. Used to purge queues between test
 * cases (tests sharing a routing key share a queue) and to read queue counters the AMQP API
 * does not expose - notably messages_unacknowledged, which is what the UNACKED accumulation
 * investigation cared about. Deliberately dependency-free (stream context, not ext-curl).
 */
final class RabbitMqManagementClient
{
    public function __construct(
        private readonly string $host,
        private readonly string $httpPort,
        private readonly string $user = 'guest',
        private readonly string $password = 'guest',
        private readonly string $vhost = '/',
    ) {
    }

    private function queuePath(string $queueName): string
    {
        return '/api/queues/' . rawurlencode($this->vhost) . '/' . rawurlencode($queueName);
    }

    public function purgeQueue(string $queueName): void
    {
        // 404 if the queue was never declared yet - that's fine, nothing to purge.
        $this->request('DELETE', $this->queuePath($queueName) . '/contents');
    }

    /**
     * @return array{ready: int, unacknowledged: int}|null null when the queue does not exist
     */
    public function queueCounters(string $queueName): ?array
    {
        $body = $this->request('GET', $this->queuePath($queueName));
        if ($body === null) {
            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !isset($decoded['messages_ready'], $decoded['messages_unacknowledged'])) {
            return null;
        }

        return [
            'ready' => (int)$decoded['messages_ready'],
            'unacknowledged' => (int)$decoded['messages_unacknowledged'],
        ];
    }

    /**
     * Number of consumers the broker currently has registered on the queue. A renewSubscription
     * that failed to cancel its previous consumer would show up here as a count above 1.
     */
    public function consumerCount(string $queueName): ?int
    {
        $body = $this->request('GET', $this->queuePath($queueName));
        if ($body === null) {
            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !isset($decoded['consumers'])) {
            return null;
        }

        return (int)$decoded['consumers'];
    }

    private function request(string $method, string $path): ?string
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

        $body = @file_get_contents($url, false, $context);

        return $body === false ? null : $body;
    }
}
