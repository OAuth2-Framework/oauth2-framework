<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Annotation;

use Doctrine\Common\Annotations\Reader;
use OAuth2Framework\ServerBundle\Annotation\Checker\Checker;
use OAuth2Framework\ServerBundle\Security\Authentication\Token\OAuth2Token;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\Response\OAuth2Response;
use OAuth2Framework\Component\Core\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AnnotationDriver
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Checker[]
     */
    private $checkers = [];

    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * @var OAuth2ResponseFactoryManager
     */
    private $oauth2ResponseFactoryManager;

    /**
     * AnnotationDriver constructor.
     *
     * @param Reader                       $reader
     * @param TokenStorageInterface        $tokenStorage
     * @param TokenTypeManager             $tokenTypeManager
     * @param OAuth2ResponseFactoryManager $oauth2ResponseFactoryManager
     */
    public function __construct(Reader $reader, TokenStorageInterface $tokenStorage, TokenTypeManager $tokenTypeManager, OAuth2ResponseFactoryManager $oauth2ResponseFactoryManager)
    {
        $this->reader = $reader;
        $this->tokenStorage = $tokenStorage;
        $this->tokenTypeManager = $tokenTypeManager;
        $this->oauth2ResponseFactoryManager = $oauth2ResponseFactoryManager;
    }

    /**
     * @param Checker $checker
     *
     * @return AnnotationDriver
     */
    public function addChecker(Checker $checker): self
    {
        $this->checkers[] = $checker;

        return $this;
    }

    /**
     * @return Checker[]
     */
    public function getCheckers(): array
    {
        return $this->checkers;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $object = new \ReflectionObject($controller[0]);
        $method = $object->getMethod($controller[1]);
        $classConfigurations = $this->reader->getClassAnnotations($object);
        $methodConfigurations = $this->reader->getMethodAnnotations($method);

        foreach (array_merge($classConfigurations, $methodConfigurations) as $configuration) {
            if ($configuration instanceof OAuth2) {
                $token = $this->tokenStorage->getToken();

                if (!$token instanceof OAuth2Token) {
                    $this->createAuthenticationException($event, 'OAuth2 authentication required');

                    return;
                }

                foreach ($this->getCheckers() as $checker) {
                    $message = $checker->check($token, $configuration);
                    if (null !== $message) {
                        $this->createAccessDeniedException($event, $message);

                        return;
                    }
                }
            }
        }
    }

    /**
     * @param FilterControllerEvent $event
     * @param string                $message
     */
    private function createAuthenticationException(FilterControllerEvent &$event, $message)
    {
        $schemes = $this->tokenTypeManager->getSchemes();
        $response = $this->oauth2ResponseFactoryManager->getResponse(
            401,
            [
                'error' => OAuth2Exception::ERROR_ACCESS_DENIED,
                'error_description' => $message,
                'schemes' => $schemes,
            ]
        );

        $this->updateFilterControllerEvent($event, $response);
    }

    /**
     * @param FilterControllerEvent $event
     * @param string                $message
     */
    private function createAccessDeniedException(FilterControllerEvent &$event, $message)
    {
        $response = $this->oauth2ResponseFactoryManager->getResponse(
            403,
            [
                'error' => OAuth2Exception::ERROR_ACCESS_DENIED,
                'error_description' => $message,
            ]
        );
        $this->updateFilterControllerEvent($event, $response);
    }

    /**
     * @param FilterControllerEvent $event
     * @param OAuth2Response        $response
     */
    private function updateFilterControllerEvent(FilterControllerEvent &$event, OAuth2Response $response)
    {
        $event->setController(function () use ($response) {
            $factory = new HttpFoundationFactory();
            $response = $factory->createResponse($response->getResponse());

            return $response;
        });
    }
}
