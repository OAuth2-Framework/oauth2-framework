<?php

namespace OAuth2Framework\Component\Client\Response;

use Assert\Assertion;
use OAuth2Framework\Component\Client\Behaviour\Metadata;
use Psr\Http\Message\ResponseInterface;

abstract class OAuth2Response implements OAuth2ResponseInterface
{
    use Metadata;
    
    /**
     * @var array
     */
    private $values;

    /**
     * {@inheritdoc}
     */
    public static function createFromResponse(ResponseInterface $response)
    {
        $content = $response->getBody()->getContents();
        $json = json_decode($content, true);
        Assertion::isArray($json, 'The response is not a valid OAuth2 Response.');
        
        if (array_key_exists('error', $json)) {
            $class = Error::class;
        } elseif (array_key_exists('access_token', $json)) {
            $class = AccessToken::class;
        } else {
            throw new \InvalidArgumentException('Unsupported response.');
        }

        /**
         * @var $object self
         */
        $object = new $class();
        $object->values = $json;
        
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    protected function getValues()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    protected function setValue($key, $value)
    {
        $this->values[$key] = $value;
    }
}
