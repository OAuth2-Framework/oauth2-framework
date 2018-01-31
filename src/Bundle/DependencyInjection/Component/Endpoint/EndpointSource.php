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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;

final class EndpointSource implements Component
{
    /**
     * TokenEndpointAuthMethodSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new AuthorizationEndpointSource());
        $this->addSubSource(new ClientConfigurationSource());
        $this->addSubSource(new ClientRegistrationSource());
        $this->addSubSource(new TokenEndpointSource());
        $this->addSubSource(new TokenIntrospectionEndpointSource());
        $this->addSubSource(new TokenRevocationEndpointSource());
        $this->addSubSource(new JwksUriEndpointSource());
        $this->addSubSource(new IssuerDiscoveryEndpointSource());
        $this->addSubSource(new SessionManagementEndpointSource());
        $this->addSubSource(new MetadataEndpointSource());
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'endpoint';
    }
}
