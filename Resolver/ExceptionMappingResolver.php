<?php


namespace Riper\Bundle\ExceptionTransformerBundle\Resolver;

use Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface;

class ExceptionMappingResolver
{

    private $exceptionsTransformers = array();


    /**
     * @param \Exception $exception
     *
     * @throws \Exception throw the exception resulting of the transformation. It can be the original exception that is thrown if no match is found
     */
    public function resolve(\Exception $exception)
    {
        $fullyQualifiedName = get_class($exception);
        $transformers = $this->getTransformerOfNamespace($fullyQualifiedName);
        foreach ($transformers as $transformer) {
            try {
                $transformer->transform($exception);
            } catch (\Exception $e) {
                // if the exception is the same as in input, we skip to continue the foreach
                if ($e !== $exception) {
                    throw $e;
                }
            }
        }
        throw $exception;
    }

    /**
     * Find all transformer that are responsible for the scope of the $namespace given
     *
     * @param $namespace
     *
     * @return ExceptionTransformerInterface[] flat list of transformer
     */
    private function getTransformerOfNamespace($namespace)
    {
        $transformers = array();
        foreach ($this->exceptionsTransformers as $scope => $scopedTransformers) {
            //the scope is always at the beginning
            if ($scope == "" || strpos($namespace, $scope) === 0) {
                $transformers = array_merge($transformers, $scopedTransformers);
            }
        }

        return $transformers;
    }

    /**
     * Add a transformer that will be use to resolve the exception mapping according to the original exception
     *
     * @param  string                       $namespaceScope (the namespace the transformer is related to)
     * @param ExceptionTransformerInterface $exceptionTransformer
     */
    public function addExceptionTransformers($namespaceScope, ExceptionTransformerInterface $exceptionTransformer)
    {
        if (!isset($this->exceptionsTransformers[$namespaceScope])) {
            $this->exceptionsTransformers[$namespaceScope] = array();
        }
        $this->exceptionsTransformers[$namespaceScope][] = $exceptionTransformer;
    }
}
