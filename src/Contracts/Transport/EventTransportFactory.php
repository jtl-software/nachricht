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
    public function createTransport(array $connectionSettings, EventSerializer $serializer): EventTransport;
}
