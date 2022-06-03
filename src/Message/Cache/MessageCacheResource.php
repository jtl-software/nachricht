<?php

namespace JTL\Nachricht\Message\Cache;

use Symfony\Component\Config\Resource\ResourceInterface;

class MessageCacheResource implements ResourceInterface
{
    public function __construct(private string $timestampHash)
    {
    }

    public function getTimestampHash(): string
    {
        return $this->timestampHash;
    }
    
    public function __toString()
    {
        return $this->getTimestampHash();
    }
}
