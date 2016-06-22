<?php


namespace Riper\Bundle\ExceptionTransformerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExceptionMappingCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->attachTaggedServiceToResolver($container);
        $this->processBundlesMap($container);
    }


    /**
     * Will find all tagged service with "'riper.exception.transformer'" and add it to the resolver
     *
     * @param ContainerBuilder $container
     */
    private function attachTaggedServiceToResolver(ContainerBuilder $container)
    {
        if (!$container->has('riper.exception_transformer.resolver')) {
            return;
        }

        $resolverServiceDefinition = $container->getDefinition('riper.exception_transformer.resolver');


        $transformers = $container->findTaggedServiceIds('riper.exception.transformer');

        //after finding all exception transformers , we add them to the resolver
        foreach ($transformers as $transformerIdService => $tags) {
            foreach ($tags as $attributes) {
                $resolverServiceDefinition->addMethodCall(
                    'addExceptionTransformers',
                    array($attributes['namespace_scope'], new Reference($transformerIdService))
                );
            }
        }
    }

    /**
     * Will search for parameters that end with "riper_exception_map" and register maps in service
     *
     * @param ContainerBuilder $container
     */
    private function processBundlesMap(ContainerBuilder $container)
    {
        if (!$container->has('riper.exception_transformer.transformers.http_exception_transformer')) {
            return;
        }

        $definition = $container->getDefinition(
            'riper.exception_transformer.transformers.http_exception_transformer'
        );

        $parameterBag = $container->getParameterBag();

        foreach ($parameterBag->all() as $key => $value) {
            if ($this->endsWith($key, "riper_exception_map")) {
                $definition->addMethodCall('addMap', array($value));
            }
        }
    }


    /**
     * Detect if a string end with another string
     * @param string $haystack
     * @param string $needle
     *
     * @return bool true if the $haystack finish by the $needle, false otherwise
     */
    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
