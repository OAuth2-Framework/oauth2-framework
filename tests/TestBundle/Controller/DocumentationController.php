<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/doc', host: 'foo.foo')]
final class DocumentationController extends AbstractController
{
    #[Route(path: '/service/{hello}', name: 'service_documentation')]
    public function serviceAction(string $hello): Response
    {
        return new Response(sprintf('Hello %s, you are on the documentation service page', $hello));
    }

    #[Route(path: '/tos', name: 'op_tos_uri')]
    public function policyAction(): Response
    {
        return new Response('You are on the Term of Service page');
    }

    #[Route(path: '/policy', name: 'op_policy_uri')]
    public function tosAction(): Response
    {
        return new Response('You are on the Policy page');
    }
}
