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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Tests\ConsentScreen;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentScreen\Extension;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentScreen\ExtensionManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @group ConsentScreenExtensionManager
 */
final class ConsentScreenExtensionManagerTest extends TestCase
{
    /**
     * @test
     */
    public function theManagerCanCallExtensionsBeforeConsentScreenExtension()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $client = Client::createEmpty();
        $client = $client->create(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = Authorization::create($client, []);
        $authorization = $this->getExtensionManager()->processBefore($request->reveal(), $authorization);
        static::assertTrue($authorization->hasData('Before Consent'));
        static::assertTrue($authorization->getData('Before Consent'));
    }

    /**
     * @test
     */
    public function theManagerCanCallExtensionsAfterConsentScreenExtension()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $client = Client::createEmpty();
        $client = $client->create(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = Authorization::create($client, []);
        $authorization = $this->getExtensionManager()->processAfter($request->reveal(), $authorization);
        static::assertTrue($authorization->hasData('After Consent'));
        static::assertTrue($authorization->getData('After Consent'));
    }

    /**
     * @var null|ExtensionManager
     */
    private $extensionManager = null;

    private function getExtensionManager(): ExtensionManager
    {
        if (null === $this->extensionManager) {
            $extension = $this->prophesize(Extension::class);
            $extension
                ->processBefore(Argument::type(ServerRequestInterface::class), Argument::type(Authorization::class))
                ->will(function ($args) {
                    /** @var Authorization $authorization */
                    $authorization = $args[1];
                    $authorization = $authorization->withData('Before Consent', true);

                    return $authorization;
                });
            $extension
                ->processAfter(Argument::type(ServerRequestInterface::class), Argument::type(Authorization::class))
                ->will(function ($args) {
                    /** @var Authorization $authorization */
                    $authorization = $args[1];
                    $authorization = $authorization->withData('After Consent', true);

                    return $authorization;
                });

            $this->extensionManager = new ExtensionManager();
            $this->extensionManager->add($extension->reveal());
        }

        return $this->extensionManager;
    }
}
