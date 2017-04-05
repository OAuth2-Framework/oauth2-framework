<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Client\Behaviour;

use Assert\Assertion;

trait Metadata
{
    /**
     * @return array
     */
    abstract protected function getValues();

    /**
     * @param string $key
     * @param mixed  $value
     */
    abstract protected function setValue($key, $value);

    /**
     * @param string $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (method_exists($this, $name)) {
            return call_user_func([$this, $name], $arguments);
        }

        $method = mb_substr($name, 0, 3, '8bit');
        if (in_array($method, ['has', 'get', 'set'])) {
            $key = $this->decamelize(mb_substr($name, 3, null, '8bit'));
            $arguments = array_merge(
                [$key],
                $arguments
            );

            return call_user_func_array([$this, $method], $arguments);
        }
        throw new \BadMethodCallException(sprintf('Method "%s" does not exists.', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        Assertion::string($key, 'The first argument must be a string.');
        Assertion::string($key, 'The argument must be a string');

        return property_exists($this, $key) || array_key_exists($key, $this->getValues());
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        Assertion::string($key, 'The first argument must be a string.');
        Assertion::true($this->has($key), sprintf('The value with key "%s" does not exist.', $key));

        if (property_exists($this, $key)) {
            return $this->$key;
        }

        return $this->getValues()[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        Assertion::string($key, 'The first argument must be a string.');

        if (property_exists($this, $key)) {
            $this->$key = $value;

            return;
        }

        $this->setValue($key, $value);
    }

    /**
     * @param string $word
     *
     * @return string
     */
    private function decamelize($word)
    {
        return preg_replace_callback(
            '/(^|[a-z])([A-Z])/',
            function($m) {
                return mb_strtolower(mb_strlen($m[1], '8bit') ? sprintf('%s_%s', $m[1], $m[2]) : $m[2], '8bit');
            },
            $word
        );
    }
}
