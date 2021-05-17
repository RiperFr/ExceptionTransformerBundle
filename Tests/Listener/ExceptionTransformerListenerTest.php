<?php


namespace Riper\Bundle\ExceptionTransformerBundle\Tests\Listener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Riper\Bundle\ExceptionTransformerBundle\Listener\ExceptionTransformerListener;
use Riper\Bundle\ExceptionTransformerBundle\Resolver\ExceptionMappingResolver;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionTransformerListenerTest extends TestCase
{
    /**
     * @var ExceptionMappingResolver | MockObject
     */
    public $resolver;

    protected function setup(): void
    {
        $this->resolver = $this->getMockBuilder(ExceptionMappingResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIShouldHaveTheListOfEventToListen(): void
    {
        $exceptionTransformerListener = new ExceptionTransformerListener($this->resolver);

        $events = $exceptionTransformerListener->getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);

    }

    public function testIShouldHaveTheResolverCalledAndTheTransformedExceptionThrown(): void
    {
        $exceptionTransformerListener = new ExceptionTransformerListener($this->resolver);
        $originalException = new \Exception('original');
        $this->resolver->expects($this->once())
            ->method('resolve')
            ->with($this->equalTo($originalException))
            ->will($this->throwException(new PouetException('Not original')));

        $event = $this->getMockBuilder(GetResponseForExceptionEvent::class)
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
                        if (get_class($exception) !== PouetException::class) {
                            $that->fail('The exception got is not the one expected. Got' . get_class($exception));
                        }
                    }
                )
            );

        //$this->setExpectedException('\Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException');
        $exceptionTransformerListener->onKernelException($event);

    }

    public function testIShouldNotHaveAnyTransformationForNonMasterRequest(): void
    {
        $exceptionTransformerListener = new ExceptionTransformerListener($this->resolver);
        $originalException = new \Exception('original');
        $this->resolver->expects($this->never())
            ->method('resolve');

        $event = $this->getMockBuilder(GetResponseForExceptionEvent::class)
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
