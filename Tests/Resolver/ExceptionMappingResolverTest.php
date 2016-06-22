<?php

namespace Riper\Bundle\ExceptionTransformerBundle\Tests\Resolver;

use Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface;
use Riper\Bundle\ExceptionTransformerBundle\Resolver\ExceptionMappingResolver;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException;

class ExceptionMappingResolverTest extends \PHPUnit_Framework_TestCase
{


    /**
     * @var ExceptionTransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transformer1;
    /**
     * @var ExceptionTransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transformer2;

    /**
     * @var ExceptionMappingResolver|
     */
    private $resolver;

    public function setup()
    {

        $this->transformer1 = $this->getMockBuilder(
            '\Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface'
        )->getMock();

        $this->transformer2 = $this->getMockBuilder(
            '\Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface'
        )->getMock();

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

        $this->setExpectedException('\Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException');
        $this->resolver->resolve($exceptionToTransform);
    }

    public function testIShouldHaveOnlyTheFirstTransformerCalledWhenItTransformAnException()
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

        $this->setExpectedException('\Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException');
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

        $this->setExpectedException('\Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException');
        $this->resolver->resolve($exceptionToTransform);
    }

    public function testIShouldHaveTheTwoTransformersCalledWhenTheFirstDoesNotThrowAnything()
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

        $this->setExpectedException('\Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException');
        $this->resolver->resolve($exceptionToTransform);
    }

    public function testIShouldHaveOnlyTheTransformersThatMatchTheNamespaceScopeToBeCalled()
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
            if ($e !== $expectedException) {
                $this->fail('The Exception thrown is not the one expected. got ' . get_class($e));
            }
        }
    }

    public function testIShouldHaveNoTransformerCalledAtAllAndTheOriginalExceptionThrownWhenNoTransformerMatchScopeOfTheException()
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
            if ($e !== $exceptionToTransform) {
                $this->fail(
                    'The Original exception is expected to be thrown, not a transformed one. got ' . get_class($e)
                );
            }
        }

    }

    public function testIShouldHaveTheOriginalExceptionThrownIfNotTransformersAreRegistred()
    {
        $exceptionToTransform = new PouetException('originalException');

        try {
            $this->resolver->resolve($exceptionToTransform);
        } catch (\Exception $e) {
            if ($e !== $exceptionToTransform) {
                $this->fail('The Original exception is expected to be thrown, not a transformed one');
            }
        }
    }


    public function testICanHaveMultipleTransformerWithTheSameNamespaceScope()
    {
        $transformer1 = $this->getMockBuilder(
            'Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface'
        )->disableOriginalConstructor()
            ->getMock();

        $transformer2 = $this->getMockBuilder(
            'Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface'
        )->disableOriginalConstructor()
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
