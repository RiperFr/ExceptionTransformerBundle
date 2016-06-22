<?php

namespace Riper\Bundle\ExceptionTransformerBundle;

use Riper\Bundle\ExceptionTransformerBundle\DependencyInjection\ExceptionMappingCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;


class RiperExceptionTransformerBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ExceptionMappingCompilerPass());
    }
}
