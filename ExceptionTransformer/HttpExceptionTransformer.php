<?php


namespace Riper\Bundle\ExceptionTransformerBundle\ExceptionTransformer;

use Riper\Bundle\ExceptionTransformerBundle\Exceptions\HttpExceptionNotFound;

class HttpExceptionTransformer implements ExceptionTransformerInterface
{


    /**
     * @var array the list of short cut used for mapping
     */
    protected $shortcut;

    /**
     * @var array the list of exception to map to (shortcut|Fully qualified exceptions)
     */
    protected $map = array();

    public function __construct(array $shortcut)
    {
        $this->shortcut = $shortcut;
    }


    /**
     * @inheritdoc
     */
    public function transform(\Exception $exception)
    {
        $fullyQualifiedName = get_class($exception);
        if (isset($this->map[$fullyQualifiedName])) {
            $newException = $this->tryToResolveShortCut($this->map[$fullyQualifiedName]);
            //This line make expectation about the constructor of the exception.
            //It must follows this rule __construct($message, $previousException, [$code] );
            throw new $newException($exception->getMessage(), $exception);
        }
    }

    /**
     * Add new map to the map list.
     *
     * @param array $map
     */
    public function addMap(array $map)
    {
        $this->map = array_merge($map, $this->map); // first one win
    }


    /**
     * When shortcuts are used they must be transformed into real fully qualified exception.
     *
     * @param string $potentialShortCut
     *
     * @return string
     * @throws HttpExceptionNotFound if no mapping is found
     */
    private function tryToResolveShortCut($potentialShortCut)
    {
        if (isset($this->shortcut[$potentialShortCut])) {
            return $this->shortcut[$potentialShortCut];
        }

        throw new HttpExceptionNotFound('No http exception found for "' . $potentialShortCut . '"');
    }
}
