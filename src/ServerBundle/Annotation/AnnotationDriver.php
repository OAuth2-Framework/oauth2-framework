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
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use Psr\Http\Message\ResponseInterface;
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
     * @var OAuth2MessageFactoryManager
     */
    private $oauth2ResponseFactoryManager;

    /**
     * AnnotationDriver constructor.
     *
     * @param Reader                      $reader
     * @param TokenStorageInterface       $tokenStorage
     * @param OAuth2MessageFactoryManager $oauth2ResponseFactoryManager
     */
    public function __construct(Reader $reader, TokenStorageInterface $tokenStorage, OAuth2MessageFactoryManager $oauth2ResponseFactoryManager)
    {
        $this->reader = $reader;
        $this->tokenStorage = $tokenStorage;
        $this->oauth2ResponseFactoryManager = $oauth2ResponseFactoryManager;
    }

    /**
     * @param Checker $checker
     *
     * @return AnnotationDriver
     */
    public function add(Checker $checker): self
    {
        $this->checkers[] = $checker;

        return $this;
    }

    /**
     * @return Checker[]
     */
    public function all(): array
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
                $this->processOAuth2Annotation($event, $configuration);
            }
        }
    }

    /**
     * @param FilterControllerEvent $event
     * @param OAuth2                $configuration
     */
    private function processOAuth2Annotation(FilterControllerEvent $event, OAuth2 $configuration): void
    {
        $token = $this->tokenStorage->getToken();

        if (!$token instanceof OAuth2Token) {
            $this->createAuthenticationException($event, 'OAuth2 authentication required', $configuration);

            return;
        }

        foreach ($this->all() as $checker) {
            try {
                $checker->check($token, $configuration);
            } catch (\Exception $e) {
                $this->createAccessDeniedException($event, $e->getMessage(), $configuration, $e);
            }
        }
    }

    /**
     * @param FilterControllerEvent $event
     * @param string                $message
     * @param OAuth2                $configuration
     */
    private function createAuthenticationException(FilterControllerEvent $event, string $message, OAuth2 $configuration)
    {
        $additionalData = $configuration->getScope() ? ['scope' => $configuration->getScope()] : [];
        $response = $this->oauth2ResponseFactoryManager->getResponse(
            new OAuth2Message(
                401,
                OAuth2Message::ERROR_ACCESS_DENIED,
                $message
            ),
            $additionalData
        );

        $this->updateFilterControllerEvent($event, $response);
    }

    /**
     * @param FilterControllerEvent $event
     * @param string                $message
     * @param OAuth2                $configuration
     * @param \Exception            $previous
     */
    private function createAccessDeniedException(FilterControllerEvent $event, string $message, OAuth2 $configuration, \Exception $previous)
    {
        $additionalData = $configuration->getScope() ? ['scope' => $configuration->getScope()] : [];
        $response = $this->oauth2ResponseFactoryManager->getResponse(
            new OAuth2Message(
            403,
            OAuth2Message::ERROR_ACCESS_DENIED,
                $message,
                $previous
            ),
            $additionalData
        );
        $this->updateFilterControllerEvent($event, $response);
    }

    /**
     * @param FilterControllerEvent $event
     * @param ResponseInterface     $psr7Response
     */
    private function updateFilterControllerEvent(FilterControllerEvent $event, ResponseInterface $psr7Response)
    {
        $event->setController(function () use ($psr7Response) {
            $factory = new HttpFoundationFactory();
            $symfonyResponse = $factory->createResponse($psr7Response);

            return $symfonyResponse;
        });
    }
}
