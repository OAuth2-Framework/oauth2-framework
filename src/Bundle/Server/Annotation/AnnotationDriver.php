<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Annotation;

use Doctrine\Common\Annotations\Reader;
use OAuth2Framework\Bundle\Server\Annotation\Checker\CheckerInterface;
use OAuth2Framework\Bundle\Server\Security\Authentication\Token\OAuth2Token;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Zend\Diactoros\Response;

final class AnnotationDriver
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
     * @var CheckerInterface[]
     */
    private $checkers = [];

    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * AnnotationDriver constructor.
     *
     * @param Reader                $reader
     * @param TokenStorageInterface $tokenStorage
     * @param TokenTypeManager      $tokenTypeManager
     */
    public function __construct(Reader $reader, TokenStorageInterface $tokenStorage, TokenTypeManager $tokenTypeManager)
    {
        $this->reader = $reader;
        $this->tokenStorage = $tokenStorage;
        $this->tokenTypeManager = $tokenTypeManager;
    }

    /**
     * @param CheckerInterface $checker
     *
     * @return AnnotationDriver
     */
    public function addChecker(CheckerInterface $checker): AnnotationDriver
    {
        $this->checkers[] = $checker;

        return $this;
    }

    /**
     * @return CheckerInterface[]
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

                // If no access token is found by the firewall, then returns an authentication error
                if (!$token instanceof OAuth2Token) {
                    $this->createAuthenticationException($event, 'OAuth2 authentication required');

                    return;
                }

                foreach ($this->getCheckers() as $checker) {
                    $result = $checker->check($token, $configuration);
                    if (null !== $result) {
                        $this->createAccessDeniedException($event, $result);

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
        $exception = new OAuth2Exception(
            401,
            [
                'error'             => OAuth2ResponseFactoryManager::ERROR_ACCESS_DENIED,
                'error_description' => $message,
                'schemes'           => $schemes,
            ]
        );

        $this->updateFilterControllerEvent($event, $exception);
    }

    /**
     * @param FilterControllerEvent $event
     * @param string                $message
     */
    private function createAccessDeniedException(FilterControllerEvent &$event, $message)
    {
        $exception = new OAuth2Exception(
            403,
            [
                'error'             => OAuth2ResponseFactoryManager::ERROR_ACCESS_DENIED,
                'error_description' => $message,
            ]
        );

        $this->updateFilterControllerEvent($event, $exception);
    }

    /**
     * @param FilterControllerEvent $event
     * @param OAuth2Exception       $exception
     */
    private function updateFilterControllerEvent(FilterControllerEvent &$event, OAuth2Exception $exception)
    {
        $event->setController(function () use ($exception) {
            $response = new Response();
            //$exception->getHttpResponse($response);
            $response->getBody()->rewind();
            $factory = new HttpFoundationFactory();
            $response = $factory->createResponse($response);

            return $response;
        });
    }
}
