<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/doc", host="foo.foo")
 */
final class DocumentationController extends AbstractController
{
    /**
     * @Route("/service/{hello}", name="service_documentation")
     */
    public function serviceAction(string $hello)
    {
        return new Response(\Safe\sprintf('Hello %s, you are on the documentation service page', $hello));
    }

    /**
     * @Route("/tos", name="op_tos_uri")
     */
    public function policyAction()
    {
        return new Response('You are on the Term of Service page');
    }

    /**
     * @Route("/policy", name="op_policy_uri")
     */
    public function tosAction()
    {
        return new Response('You are on the Policy page');
    }
}
