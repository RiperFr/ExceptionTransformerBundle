<?php

namespace Riper\Bundle\ExceptionTransformerBundle\Listener;

use Riper\Bundle\ExceptionTransformerBundle\Resolver\ExceptionMappingResolver;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionTransformerListener implements EventSubscriberInterface
{

    /**
     * @var ExceptionMappingResolver
     */
    private $exceptionMappingResolver;


    public function __construct(ExceptionMappingResolver $exceptionMappingResolver)
    {
        $this->exceptionMappingResolver = $exceptionMappingResolver;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => array('onKernelException', 255),
        );
    }

    /**
     * Executed when an exception is thrown and not caught by the program
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $ex = $event->getException();
        try {
            $this->exceptionMappingResolver->resolve($ex);
        } catch (\Exception $e) {
            $event->setException($e);
        }
    }
}
