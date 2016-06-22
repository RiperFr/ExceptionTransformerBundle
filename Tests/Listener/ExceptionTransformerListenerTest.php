<?php


namespace Riper\Bundle\ExceptionTransformerBundle\Tests\Listener;

use Riper\Bundle\ExceptionTransformerBundle\Listener\ExceptionTransformerListener;
use Riper\Bundle\ExceptionTransformerBundle\Resolver\ExceptionMappingResolver;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionTransformerListenerTest extends \PHPUnit_Framework_TestCase
{


    /**
     * @var ExceptionMappingResolver | \PHPUnit_Framework_MockObject_MockObject
     */
    public $resolver;

    public function setup()
    {
        $this->resolver = $this->getMockBuilder(
            '\Riper\Bundle\ExceptionTransformerBundle\Resolver\ExceptionMappingResolver'
        )
            ->disableOriginalConstructor()
            ->getMock();

    }

    public function testIShouldHaveTheListOfEventToListen()
    {
        $exceptionTransformerListener = new ExceptionTransformerListener($this->resolver);

        $events = $exceptionTransformerListener->getSubscribedEvents();

        $this->assertTrue(array_key_exists(KernelEvents::EXCEPTION, $events));

    }

    public function testIShouldHaveTheResolverCalledAndTheTransformedExceptionThrown()
    {
        $exceptionTransformerListener = new ExceptionTransformerListener($this->resolver);
        $originalException = new \Exception('original');
        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($originalException))
            ->will($this->throwException(new PouetException('Not original')));

        $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getException')
            ->will($this->returnValue($originalException));
        $event->expects($this->once())
            ->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::MASTER_REQUEST));

        $that = $this;
        $event->expects($this->once())
            ->method('setException')
            ->will(
                $this->returnCallback(
                    function ($exception) use ($that) {
                        if (get_class($exception)
                            !== 'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException'
                        ) {
                            $that->fail('The exception got is not the one expected. Got' . get_class($exception));
                        }
                    }
                )
            );

        //$this->setExpectedException('\Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException');
        $exceptionTransformerListener->onKernelException($event);

    }

    public function testIShouldNotHaveAnyTransformationForNonMasterRequest()
    {
        $exceptionTransformerListener = new ExceptionTransformerListener($this->resolver);
        $originalException = new \Exception('original');
        $this->resolver->expects($this->never())
            ->method('resolve');

        $event = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->never())->method('getException');
        $event->expects($this->once())->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::SUB_REQUEST));

        try {
            $exceptionTransformerListener->onKernelException($event);
        } catch (\Exception $e) {
            $this->fail(
                'No Exception should be thrown if the master query is not MASTER. Got ' . get_class($e) . ' - '
                . $e->getMessage()
            );
        }

    }
}
