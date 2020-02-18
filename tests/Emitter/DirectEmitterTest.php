<?php declare(strict_types=1);
/**
 * This file is part of the jtl-software/nachricht
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) JTL-Software-GmbH
 * @author avermeulen
 * @license http://opensource.org/licenses/MIT MIT
 * @link https://packagist.org/packages/jtl/nachricht Packagist
 * @link https://github.com/jtl-software/nachricht GitHub
 */

namespace JTL\Nachricht\Emitter;

use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Listener\ListenerProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Nachricht\Emitter\DirectEmitter
 */
class DirectEmitterTest extends TestCase
{
    public function testCanEmit()
    {
        $messageStub = $this->createStub(Message::class);
        $messageExecuteCounter = 0;
        $listener = function (object $message) use (&$messageExecuteCounter) {
            ++$messageExecuteCounter;
        };
        $listenerProviderMock = $this->createMock(ListenerProvider::class);
        $listenerProviderMock->expects($this->once())->method('getListenersForMessage')->with($messageStub)->willReturn([$listener]);

        $emitter = new DirectEmitter($listenerProviderMock);
        $emitter->emit($messageStub);

        $this->assertEquals(1, $messageExecuteCounter);
    }

    public function testCanEmitMessageList()
    {
        $messageStub = $this->createStub(Message::class);
        $messageExecuteCounter = 0;
        $listener = function (object $message) use (&$messageExecuteCounter) {
            ++$messageExecuteCounter;
        };
        $listenerProviderMock = $this->createMock(ListenerProvider::class);
        $listenerProviderMock->expects($this->exactly(4))->method('getListenersForMessage')->with($messageStub)->willReturn([$listener]);

        $emitter = new DirectEmitter($listenerProviderMock);
        $emitter->emit($messageStub, $messageStub, $messageStub, $messageStub);

        $this->assertEquals(4, $messageExecuteCounter);
    }
}
