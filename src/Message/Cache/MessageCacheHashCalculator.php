<?php

namespace JTL\Nachricht\Message\Cache;

class MessageCacheHashCalculator
{
    /**
     * @param array<string> $fileList
     * @return string
     */
    public function calculateHash(array $fileList): string
    {
        $dates = '';
        
        foreach ($fileList as $file) {
            $dates .= (string)filemtime($file);
        }
        
        return sha1($dates);
    }
}
