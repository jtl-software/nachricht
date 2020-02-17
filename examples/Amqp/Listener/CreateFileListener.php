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
use JTL\Nachricht\Examples\Amqp\Message\CreateFileAmqpMessage;

class CreateFileListener implements Listener
{
    private const TMP_DIR = __DIR__ . '/../tmp';

    /**
     * @param CreateFileAmqpMessage $event
     * @throws Exception
     */
    public function listen(CreateFileAmqpMessage $event): void
    {
        if (!is_dir(self::TMP_DIR)) {
            mkdir(self::TMP_DIR);
        }

        usleep(random_int(50, 800) * 1000);

        $this->randomFail();

        $handle = fopen(self::TMP_DIR . '/' . $event->getFilename(), 'w+');
        fwrite($handle, bin2hex(random_bytes(1024 * 1024)));
        fclose($handle);

        echo "{$event->getMessageId()} Created file {$event->getFilename()}\n";
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
