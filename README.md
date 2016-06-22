Exception transformer
======================

This bundle can listen to exception that reach the kernel of Symfony to transform them before the kernel render them.
This is useful when you want to transform domain/business exception from your code to presentation exception such as http exception that contain http status code.

[![Build Status](https://scrutinizer-ci.com/g/RiperFr/ExceptionTransformerBundle/badges/build.png?b=master)](https://scrutinizer-ci.com/g/RiperFr/ExceptionTransformerBundle/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/RiperFr/ExceptionTransformerBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/RiperFr/ExceptionTransformerBundle/?branch=master)
![License CC-BY-4](https://img.shields.io/badge/licence-CC--BY--4.0-blue.svg)
![php version](https://img.shields.io/badge/php->=5.3.5,%205.4,%205.5,%205.6,%207-blue.svg)
![symfony version](https://img.shields.io/badge/symfony-2.6,%202.7,%202.8,%203-blue.svg)


[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f72a264e-1e57-4973-b953-8fe3465792e9/big.png)](https://insight.sensiolabs.com/projects/f72a264e-1e57-4973-b953-8fe3465792e9)

## Usage

### Require the dependency in your composer.json

    "riper/exception-transformer" : "1.*"

### Register the bundle in app-kernel

    new Riper\Bundle\ExceptionTransformerBundle\RiperCommonExceptionTransformerBundle(),

### Transform exception with a service (most portable way)

Implement the interface **\Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface**

Implement the **transform** method. It can (if required) throw a new exception according to the exception given.

Example :
```php
<?php
namespace Riper\Bundle\Moderation\AmoBundle\Exceptions;


use Riper\Bundle\Accounts\AuthenticationBundle\Exceptions\NotFoundException;
use Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionTransformer implements ExceptionTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(\Exception $exception)
    {
        switch (1){
          case $exception instanceof NotFoundException :
              throw new NotFoundHttpException($exception->getMessage(),$exception);
          case $exception instanceof InvalidParametersException :
              throw new BadRequestHttpException($exception->getMessage(),$exception);
        }
    }
}
````
Create a service description tagged with the following information 

* name : riper.exception.transformer
* scope: [The namespace the transformer will be restricted to]

example : 
```yaml
riper.moderation.exception.transformer:
    class: Riper\Bundle\biduleBundle\Exceptions\ExceptionTransformer
    tags:
        - { name: riper.exception.transformer , namespace_scope: "Riper\\Bundle\\Bidule\\"}
```

### Use the built-in transformer for http error exception
You can transform your exceptions in httpException from **symfony/http-kernel** just by adding some configuration. No code needed.

1. Create a configuration key in parameters with a **unique name** ending by **"riper_exception_map"**.
2. The parameter contain a key=>value with 
    * key = THe full namespace of the exception to transform (without a slash at the beginning)
    * value = The http status to generate (an exception will be thrown with the proper http status code)

The list of available status is located in __riper_exception_mapping.shortcuts__ parameter in __ExceptionTransformerBundle/Resources/config/exceptions_mapping.yml__.
The following is a non-exhaustive list

* BadRequestHttpException
* NotFoundHttpException
* ConflictHttpException
* AccessDeniedHttpException
* GoneHttpException
* LengthRequiredHttpException
* MethodNotAllowedHttpException
* NotAcceptableHttpException
* PreconditionFailedHttpException
* PreconditionRequiredHttpException
* UnprocessableEntityHttpException
* UnsupportedMediaTypeHttpException

Example : 

    parameters:
        customercare.contact.riper_exception_map:
            Riper\Bundle\CustomerCare\ContactBundle\Exceptions\InvalidParameterException: BadRequestHttpException
            Riper\Bundle\CustomerCare\ContactBundle\Exceptions\ContactException: BadRequestHttpException


*The status code/exception is not in the shortcut list ? use the first method with the transformer tagged*
