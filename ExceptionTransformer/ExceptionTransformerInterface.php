<?php


namespace Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer;

interface ExceptionTransformerInterface
{


    /**
     * @param \Exception $exception The exception to transform
     *
     * @throws \Exception if a match is found, the new exception is thrown directly
     * @return void
     */
    public function transform(\Exception $exception);
}
