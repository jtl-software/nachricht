<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/12
 */

namespace JTL\Nachricht\Message\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Contract\Listener\Listener;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\TestCase;

/**
 * Class ListenerDetectorTest
 * @package JTL\Nachricht\Message\Cache
 */
#[CoversClass(ListenerDetector::class)]
class ListenerDetectorTest extends TestCase
{
    private ListenerDetector $listenerDetector;
    private Class_ $class;
    private ClassMethod $classMethod;
    private Name $listenerClassName;
    private Name $messageClassName;
    private Name $interfaceName;
    private Param $param;
    private Identifier $methodIdentifier;

    public function setUp(): void
    {
        $this->class = $this->createStub(Class_::class);
        $this->classMethod = $this->createStub(ClassMethod::class);
        $this->interfaceName = $this->createStub(Name::class);
        $this->listenerClassName = $this->createStub(Name::class);
        $this->messageClassName = $this->createStub(Name::class);
        $this->param = $this->createStub(Param::class);
        $this->methodIdentifier = $this->createStub(Identifier::class);
        $this->listenerDetector = new ListenerDetector();
    }

    public function testCanDetectListener(): void
    {
        $this->listenerClassName->parts = ['JTL', 'Nachricht', 'Message', 'Cache', 'FooListener'];
        $this->class->namespacedName = $this->listenerClassName;

        $this->messageClassName->parts = ['JTL', 'Nachricht', 'Message', 'Cache', 'TestMessage'];
        $this->param->type = $this->messageClassName;
        $this->classMethod->method('isPublic')->willReturn(true);
        $this->classMethod->params[0] = $this->param;

        $this->methodIdentifier->name = 'listen';
        $this->classMethod->name = $this->methodIdentifier;

        $this->listenerDetector->enterNode($this->class);
        $this->listenerDetector->enterNode($this->classMethod);

        $this->assertEquals([
            [
                'methodName' => 'listen',
                'eventClass' => 'JTL\Nachricht\Message\Cache\TestMessage'
            ]
        ], $this->listenerDetector->getListenerMethods());

        $this->assertEquals('JTL\Nachricht\Message\Cache\FooListener', $this->listenerDetector->getListenerClass());
        $this->assertTrue($this->listenerDetector->isClassListener());
    }

}


class TestMessage implements Message
{
}

class FooListener implements Listener
{
}
