Exception transformer
======================

This bundle can listen to exception that reach the kernel of Symfony to transform them before the kernel render them.
This is useful when you want to transform domain/business exception from your code to presentation exception such as http exception that contain http status code.


## Usage

### Require the dependency in your composer.json

    "riper/exception-transformer" : "1.*"

### Register the bundle in app-kernel

    new Riper\Bundle\ExceptionTransformerBundle\RiperCommonExceptionTransformerBundle(),

### Transform exception with a service (most portable way)

Implement the interface **\Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer\ExceptionTransformerInterface**

Implement the **transform** method. It can (if required) throw a new exception according to the exception given.

Example :

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

Create a service description tagged with the following information 

* name : riper.exception.transformer
* scope: [The namespace the transformer will be restricted to]

example : 

    riper.moderation.exception.transformer:
        class: Riper\Bundle\Moderation\AmoBundle\Exceptions\ExceptionTransformer
        tags:
            - { name: riper.exception.transformer , namespace_scope: "Riper\\Bundle\\Moderation\\AmoBundle\\"}


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

*The status code/exception is not in the shortcut list ? use the first method with the transformer tagged* 

Example : 

    parameters:
        customercare.contact.riper_exception_map:
            Riper\Bundle\CustomerCare\ContactBundle\Exceptions\InvalidParameterException: BadRequestHttpException
            Riper\Bundle\CustomerCare\ContactBundle\Exceptions\ContactException: BadRequestHttpException
