<?php


namespace Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions;

class ExpectedOneException extends \Exception
{

    public $previous;

    public function __construct($message, \Exception $previous)
    {
        parent::__construct($message);
        $this->previous = $previous;
    }
}
