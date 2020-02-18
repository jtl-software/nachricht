<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/16
 */

namespace JTL\Nachricht\Message\Cache;

class MessageCacheFileLoader
{
    /**
     * @param string $fileName
     * @return array
     */
    public function load(string $fileName): array
    {
        return require $fileName;
    }
}
