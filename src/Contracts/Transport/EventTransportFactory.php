<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/23
 */

namespace JTL\Nachricht\Contracts\Transport;

use JTL\Nachricht\Contracts\Serializer\EventSerializer;

interface EventTransportFactory
{
    /**
     * @param array $connectionSettings
     * @param EventSerializer $serializer
     * @return EventTransport
     */
    public function createTransport(array $connectionSettings, EventSerializer $serializer): EventTransport;
}
