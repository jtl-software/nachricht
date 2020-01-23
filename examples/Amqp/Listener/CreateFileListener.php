<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Listener;


use Exception;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Examples\Amqp\Event\CreateFileAmqpEvent;

class CreateFileListener implements Listener
{
    private const TMP_DIR = __DIR__ . '/../tmp';

    /**
     * @param CreateFileAmqpEvent $event
     * @throws Exception
     */
    public function listen(CreateFileAmqpEvent $event): void
    {
        if (!is_dir(self::TMP_DIR)) {
            mkdir(self::TMP_DIR);
        }

        usleep(random_int(50, 800) * 1000);

        $this->randomFail();
    }

    /**
     * @throws Exception
     */
    private function randomFail(): void
    {
        if(random_int(1, 10000) % 6 === 0) {
            $msg = "Listener failed intentionally";
            echo $msg . PHP_EOL;
            throw new Exception($msg);
        }
    }
}
