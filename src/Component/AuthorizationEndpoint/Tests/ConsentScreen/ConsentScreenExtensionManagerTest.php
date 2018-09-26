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

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\Extension;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
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
    public function theManagerCanCallExtensionsAfterConsentScreenExtension()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $client = new Client(
            new ClientId('CLIENT_ID'),
            new DataBag([]),
            null
        );
        $authorization = new AuthorizationRequest($client, []);
        $this->getExtensionManager()->process($request->reveal(), $authorization);
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
                ->process(Argument::type(ServerRequestInterface::class), Argument::type(AuthorizationRequest::class))
                ->will(function ($args) {
                    /** @var AuthorizationRequest $authorization */
                    $authorization = $args[1];
                    $authorization->setData('After Consent', true);
                });

            $this->extensionManager = new ExtensionManager();
            $this->extensionManager->add($extension->reveal());
        }

        return $this->extensionManager;
    }
}
