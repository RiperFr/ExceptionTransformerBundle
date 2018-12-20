<?php

namespace Riper\Bundle\ExceptionTransformerBundle\Tests\ExceptionTransformer;

use Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\HttpExceptionTransformer;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedOneException;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\IncompatibleSourceException;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException;

class HttpExceptionTransformerTest extends \PHPUnit_Framework_TestCase
{

    private $shortcuts
        = array(
            'one'          => 'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedOneException',
            'two'          => 'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedTwoException',
            'Incompatible' => 'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\IncompatibleHttpTransformerException',
        );


    /**
     * @var HttpExceptionTransformer
     */
    private $transformer;

    public function setup()
    {
        $map = array(
            'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException'              => 'one',
            'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\IncompatibleSourceException' => 'Incompatible',
            //ExpectedOneException
            'Exception'                                                                                   => 'two'
            //ExpectedTwoException
        );
        $this->transformer = new HttpExceptionTransformer($this->shortcuts);
        $this->transformer->addMap($map);
    }

    public function testIShouldHaveTheExceptionTransformedAndThrownWhenNamespacedExceptionIsGivenForTransformation()
    {
        $this->setExpectedException(
            '\Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedOneException'
        );
        $this->transformer->transform(new PouetException());
    }

    public function testIShouldHaveTheExceptionTransformedAndThrownWhenGlobalNamespaceExceptionIsGivenForTransformation()
    {
        $this->setExpectedException(
            '\Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedTwoException'
        );
        $this->transformer->transform(new \Exception());
    }

    public function testIShouldHaveAnHttpNotFoundExceptionWhenShortcutDoesNotExist()
    {
        $map = array(
            'RuntimeException' => 'FortyTwo'
        );

        $this->transformer->addMap($map);
        $this->setExpectedException('\Riper\Bundle\ExceptionTransformerBundle\Exceptions\HttpExceptionNotFound');
        $this->transformer->transform(new \RuntimeException());
    }

    public function testIShouldNotHaveExceptionThrownIfOriginalExceptionIsNotInTheMap()
    {
        try {
            $this->transformer->transform(new \DomainException());
        } catch (\Exception $e) {
            $this->fail(
                'An exception has been thrown but should not been because no ' .
                'configuration is made to transform \DomainException'
            );
        }
    }

    public function testIShouldHaveTheTransformedExceptionMessageAndReferenceInTheNewlyCreatedException()
    {

        $exception = null;
        $SourceException = new PouetException('Super exception');
        try {
            $this->transformer->transform($SourceException);
        } catch (ExpectedOneException $e) {
            $exception = $e;
        }
        $this->assertTrue(
            $exception->previous === $SourceException,
            " HttpException must have a reference  to the old exception for tracability"
        );
        $this->assertEquals($exception->getMessage(), $SourceException->getMessage());
    }


    public function testItTransformsOnlyToExceptionHavingConstructorTakingMessageAndPreviousException()
    {
        $exception = null;
        try {
            $this->transformer->transform(new IncompatibleSourceException());
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertTrue(
            ($exception !== null),
            'An exception must be thrown when the exception constructor is not compatible'
        );
    }
}
