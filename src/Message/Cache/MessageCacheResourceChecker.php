<?php

namespace JTL\Nachricht\Message\Cache;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\ResourceCheckerInterface;

class MessageCacheResourceChecker implements ResourceCheckerInterface
{
    /**
     * @param array<string> $fileList
     */
    public function __construct(private array $fileList, private bool $isDevelopment)
    {
    }

    public function supports(ResourceInterface $metadata)
    {
        return $metadata instanceof MessageCacheResource;
    }
    
    public function isFresh(ResourceInterface $resource, int $timestamp)
    {
        if (!$this->isDevelopment) {
            return true;
        }

        $messageCacheHashCalculator = new MessageCacheHashCalculator();
        
        $hash = $messageCacheHashCalculator->calculateHash($this->fileList);
        
        return (string)$resource === $hash;
    }
}
