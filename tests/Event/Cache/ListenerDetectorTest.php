<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/12
 */

namespace JTL\Nachricht\Event\Cache;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Listener\Listener;
use Mockery;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\TestCase;

/**
 * Class ListenerDetectorTest
 * @package JTL\Nachricht\Event\Cache
 *
 * @covers \JTL\Nachricht\Event\Cache\ListenerDetector
 */
class ListenerDetectorTest extends TestCase
{
    /**
     * @var ListenerDetector
     */
    private $listenerDetector;

    /**
     * @var Mockery\MockInterface|Class_
     */
    private $class;

    /**
     * @var Mockery\MockInterface|ClassMethod
     */
    private $classMethod;


    /**
     * @var Mockery\MockInterface|Name
     */
    private $listenerClassName;

    /**
     * @var Mockery\MockInterface|Name
     */
    private $eventClassName;

    /**
     * @var Mockery\MockInterface|Name
     */
    private $interfaceName;

    /**
     * @var Mockery\MockInterface|Param
     */
    private $param;

    /**
     * @var Mockery\MockInterface|Identifier
     */
    private $methodIdentifier;

    public function setUp(): void
    {
        $this->class = Mockery::mock(Class_::class);
        $this->classMethod = Mockery::mock(ClassMethod::class);
        $this->interfaceName = Mockery::mock(Name::class);
        $this->listenerClassName = Mockery::mock(Name::class);
        $this->eventClassName = Mockery::mock(Name::class);
        $this->param = Mockery::mock(Param::class);
        $this->methodIdentifier = Mockery::mock(Identifier::class);
        $this->listenerDetector = new ListenerDetector();
    }

    public function testCanDetectListener(): void
    {
        $this->listenerClassName->parts = ['JTL', 'Nachricht', 'Event', 'Cache', 'FooListener'];
        $this->class->namespacedName = $this->listenerClassName;

        $this->eventClassName->parts = ['JTL', 'Nachricht', 'Event', 'Cache', 'TestEvent'];
        $this->param->type = $this->eventClassName;
        $this->classMethod->params[0] = $this->param;

        $this->methodIdentifier->name = 'listen';
        $this->classMethod->name = $this->methodIdentifier;

        $this->listenerDetector->enterNode($this->class);
        $this->listenerDetector->enterNode($this->classMethod);

        $this->assertEquals([
            [
                'methodName' => 'listen',
                'eventClass' => 'JTL\Nachricht\Event\Cache\TestEvent'
            ]
        ], $this->listenerDetector->getListenerMethods());

        $this->assertEquals('JTL\Nachricht\Event\Cache\FooListener', $this->listenerDetector->getListenerClass());
        $this->assertTrue($this->listenerDetector->isClassListener());
    }
}


class TestEvent implements Event
{
}

class FooListener implements Listener
{
}
