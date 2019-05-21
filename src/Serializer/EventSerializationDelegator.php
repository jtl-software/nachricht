<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Serializer;


class EventSerializationDelegator
{
    public function delegate(string $data)
    {
        $parts = explode('|', $data);

        if (class_exists($parts[0])) {
            $event = $parts[0]::deserialize($data);
            var_dump($event);
        }
    }
}
