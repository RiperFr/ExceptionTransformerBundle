<?php

namespace Riper\Bundle\ExceptionTransformerBundle\Tests\Resolver;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface;
use Riper\Bundle\ExceptionTransformerBundle\Resolver\ExceptionMappingResolver;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException;

class ExceptionMappingResolverTest extends TestCase
{
    /**
     * @var ExceptionTransformerInterface|MockObject
     */
    private $transformer1;
    /**
     * @var ExceptionTransformerInterface|MockObject
     */
    private $transformer2;

    private ExceptionMappingResolver $resolver;

    protected function setup(): void
    {
        $this->transformer1 = $this->getMockBuilder(ExceptionTransformerInterface::class)->getMock();
        $this->transformer2 = $this->getMockBuilder(ExceptionTransformerInterface::class)->getMock();

        $this->resolver = new ExceptionMappingResolver();
    }

    public function testIShouldHaveAnExceptionTransformed()
    {
        $this->resolver->addExceptionTransformers('', $this->transformer1);
        $exceptionToTransform = new \Exception('originalException');

        $this->transformer1->expects($this->once())
            ->method('transform')
            ->with($this->equalTo($exceptionToTransform))
            ->will(
                $this->throwException(new PouetException())
            );

        $this->expectException(PouetException::class);
        $this->resolver->resolve($exceptionToTransform);
    }

    public function testIShouldHaveOnlyTheFirstTransformerCalledWhenItTransformAnException(): void
    {
        $this->resolver->addExceptionTransformers('', $this->transformer1);
        $this->resolver->addExceptionTransformers('', $this->transformer2);
        $exceptionToTransform = new \Exception('originalException');

        $this->transformer1->expects($this->once())
            ->method('transform')
            ->with($this->equalTo($exceptionToTransform))
            ->will(
                $this->throwException(new PouetException())
            );
        $this->transformer2->expects($this->never())
            ->method('transform');

        $this->expectException(PouetException::class);
        $this->resolver->resolve($exceptionToTransform);
    }

    public function testIShouldHaveTheTwoTransformersCalledWhenTheFirstThrowTheOriginalException()
    {
        $this->resolver->addExceptionTransformers('', $this->transformer1);
        $this->resolver->addExceptionTransformers('', $this->transformer2);
        $exceptionToTransform = new \Exception('originalException');

        $this->transformer1->expects($this->once())
            ->method('transform')
            ->with($this->equalTo($exceptionToTransform))
            ->will(
                $this->throwException($exceptionToTransform)
            );
        $this->transformer2->expects($this->once())
            ->method('transform')
            ->with($this->equalTo($exceptionToTransform))
            ->will(
                $this->throwException(new PouetException())
            );

        $this->expectException(PouetException::class);
        $this->resolver->resolve($exceptionToTransform);
    }

    public function testIShouldHaveTheTwoTransformersCalledWhenTheFirstDoesNotThrowAnything(): void
    {
        $this->resolver->addExceptionTransformers('', $this->transformer1);
        $this->resolver->addExceptionTransformers('', $this->transformer2);
        $exceptionToTransform = new \Exception('originalException');

        $this->transformer1->expects($this->once())
            ->method('transform')
            ->with($this->equalTo($exceptionToTransform));

        $this->transformer2->expects($this->once())
            ->method('transform')
            ->with($this->equalTo($exceptionToTransform))
            ->will(
                $this->throwException(new PouetException())
            );

        $this->expectException(PouetException::class);
        $this->resolver->resolve($exceptionToTransform);
    }

    public function testIShouldHaveOnlyTheTransformersThatMatchTheNamespaceScopeToBeCalled(): void
    {
        $this->resolver->addExceptionTransformers('titi\toto', $this->transformer1);
        $this->resolver->addExceptionTransformers(
            'Riper\Bundle\ExceptionTransformerBundle\Tests',
            $this->transformer2
        );
        $exceptionToTransform = new PouetException('originalException');
        $expectedException = new \Exception('Expected Exception');

        $this->transformer1->expects($this->never())->method('transform');
        $this->transformer2->expects($this->once())
            ->method('transform')
            ->will($this->throwException($expectedException));

        try {
            $this->resolver->resolve($exceptionToTransform);
        } catch (\Exception $e) {
            $this->assertSame($expectedException, $e);
            return;
        }

        $this->fail('Expected an exception to be thrown');
    }

    public function testIShouldHaveNoTransformerCalledAtAllAndTheOriginalExceptionThrownWhenNoTransformerMatchScopeOfTheException(): void
    {
        $this->resolver->addExceptionTransformers('toto\titi', $this->transformer1);
        $this->resolver->addExceptionTransformers(
            'meetic\super\code\de\la\mort',
            $this->transformer2
        );
        $exceptionToTransform = new PouetException('originalException');

        $this->transformer1->expects($this->never())->method('transform');
        $this->transformer2->expects($this->never())->method('transform');

        try {
            $this->resolver->resolve($exceptionToTransform);
        } catch (\Exception $e) {
            $this->assertSame(
                $exceptionToTransform,
                $e,
                'The Original exception is expected to be thrown, not a transformed one. got ' . get_class($e)
            );
            return;
        }

        $this->fail('An Exception should have been thrown');
    }

    public function testIShouldHaveTheOriginalExceptionThrownIfNotTransformersAreRegistred(): void
    {
        $exceptionToTransform = new PouetException('originalException');

        try {
            $this->resolver->resolve($exceptionToTransform);
        } catch (\Exception $e) {
            $this->assertSame($exceptionToTransform, $e, 'The Original exception is expected to be thrown, not a transformed one');
            return;
        }

        $this->fail('An Exception should have been thrown');
    }

    public function testICanHaveMultipleTransformerWithTheSameNamespaceScope(): void
    {
        $transformer1 = $this->getMockBuilder(ExceptionTransformerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transformer2 = $this->getMockBuilder(ExceptionTransformerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transformer1->expects($this->once())
            ->method('transform');
        $transformer2->expects($this->once())
            ->method('transform');

        $exceptionToTransform = new PouetException('originalException');

        $this->resolver->addExceptionTransformers(
            'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions',
            $transformer1
        );
        $this->resolver->addExceptionTransformers(
            'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions',
            $transformer2
        );

        try {
            $this->resolver->resolve($exceptionToTransform);
        } catch (PouetException $e) {
            //Nothing to catch, it is normal to have the originalException because no transformer transformed it.
        }
    }
}
