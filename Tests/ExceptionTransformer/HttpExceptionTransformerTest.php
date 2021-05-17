<?php

namespace Riper\Bundle\ExceptionTransformerBundle\Tests\ExceptionTransformer;

use PHPUnit\Framework\TestCase;
use Riper\Bundle\ExceptionTransformerBundle\Exceptions\HttpExceptionNotFound;
use Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\HttpExceptionTransformer;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedOneException;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedTwoException;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\IncompatibleSourceException;
use Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\PouetException;

class HttpExceptionTransformerTest extends TestCase
{
    private array $shortcuts
        = array(
            'one'          => 'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedOneException',
            'two'          => 'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\ExpectedTwoException',
            'Incompatible' => 'Riper\Bundle\ExceptionTransformerBundle\Tests\Exceptions\IncompatibleHttpTransformerException',
        );

    private HttpExceptionTransformer $transformer;

    protected function setup(): void
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

    public function testIShouldHaveTheExceptionTransformedAndThrownWhenNamespacedExceptionIsGivenForTransformation(): void
    {
        $this->expectException(ExpectedOneException::class);
        $this->transformer->transform(new PouetException());
    }

    public function testIShouldHaveTheExceptionTransformedAndThrownWhenGlobalNamespaceExceptionIsGivenForTransformation(): void
    {
        $this->expectException(ExpectedTwoException::class);
        $this->transformer->transform(new \Exception());
    }

    public function testIShouldHaveAnHttpNotFoundExceptionWhenShortcutDoesNotExist(): void
    {
        $map = array(
            'RuntimeException' => 'FortyTwo'
        );

        $this->transformer->addMap($map);
        $this->expectException(HttpExceptionNotFound::class);
        $this->transformer->transform(new \RuntimeException());
    }

    public function testIShouldNotHaveExceptionThrownIfOriginalExceptionIsNotInTheMap(): void
    {
        try {
            $this->transformer->transform(new \DomainException());
        } catch (\Exception $e) {
            $this->fail(
                'An exception has been thrown but should not been because no ' .
                'configuration is made to transform \DomainException'
            );
        }

        $this->expectNotToPerformAssertions();
    }

    public function testIShouldHaveTheTransformedExceptionMessageAndReferenceInTheNewlyCreatedException(): void
    {
        $exception = null;
        $SourceException = new PouetException('Super exception');
        try {
            $this->transformer->transform($SourceException);
        } catch (ExpectedOneException $e) {
            $exception = $e;
        }
        $this->assertSame(
            $SourceException, $exception->previous,
            " HttpException must have a reference  to the old exception for tracability"
        );
        $this->assertEquals($exception->getMessage(), $SourceException->getMessage());
    }


    public function testItTransformsOnlyToExceptionHavingConstructorTakingMessageAndPreviousException(): void
    {
        $exception = null;
        try {
            $this->transformer->transform(new IncompatibleSourceException());
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'An exception must be thrown when the exception constructor is not compatible');
    }
}
