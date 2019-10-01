<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/10/01
 */

namespace JTL\Nachricht\Event\Cache;

use Mockery;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractVisitorTest
 * @package JTL\Nachricht\Event\Cache
 *
 * @covers \JTL\Nachricht\Event\Cache\AbstractVisitor
 */
class AbstractVisitorTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanCheckIfInterfaceIsImplemented(): void
    {
        $class = Mockery::mock(Class_::class);
        $className = Mockery::mock(Name::class);
        $className->parts = ['JTL', 'Nachricht', 'Event', 'Cache', 'TestClass'];
        $class->namespacedName = $className;
        
        $testVisitor = new TestVisitor();
        $testVisitor->enterNode($class);

        $this->assertTrue($testVisitor->implementsInterface);
        $this->assertSame(TestClass::class, $testVisitor->className);
    }

    public function testFailBecauseClassNameIsNotSet(): void
    {
        $class = Mockery::mock(Class_::class);

        $testVisitor = new TestVisitor();
        $testVisitor->enterNode($class);

        $this->assertFalse($testVisitor->implementsInterface);
        $this->assertNull($testVisitor->className);
    }

    public function testFailBecauseClassImplementsReturnsError(): void
    {
        $class = Mockery::mock(Class_::class);
        $className = Mockery::mock(Name::class);
        $className->parts = ['JTL', 'Nachricht', 'Event', 'Cache', uniqid('garbage', true)];
        $class->namespacedName = $className;

        $testVisitor = new TestVisitor();
        $testVisitor->enterNode($class);

        $this->assertFalse($testVisitor->implementsInterface);
    }
}

class TestVisitor extends AbstractVisitor
{

    /**
     * @var bool
     */
    public $implementsInterface;

    /**
     * @var string
     */
    public $className;

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->implementsInterface = $this->classImplementsInterface($node, TestInterface::class);
            $this->className = $this->getClassName($node);
        }
    }
}

class TestClass implements TestInterface
{
}

interface TestInterface
{
}
