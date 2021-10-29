<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Scope;

use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Scope\ScopeParameterChecker;
use OAuth2Framework\Component\Scope\ScopeRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class ScopeParameterCheckerTest extends TestCase
{
    use ProphecyTrait;

    private ?ScopeParameterChecker $scopeParameterChecker = null;

    /**
     * @inheritdoc}
     */
    protected function setUp(): void
    {
        if (! class_exists(AuthorizationRequest::class)) {
            static::markTestSkipped('The component "oauth2-framework/authorization-endpoint" is not installed.');
        }
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithNoScopeParameterIsChecked(): void
    {
        $client = $this->prophesize(Client::class);
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $authorization->hasQueryParam('scope')
            ->willReturn(false)
            ->shouldBeCalled()
        ;
        $authorization->setResponseParameter('scope', Argument::any())->shouldNotBeCalled();
        $this->getScopeParameterChecker()
            ->check($authorization->reveal())
        ;
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithScopeParameterIsChecked(): void
    {
        $client = $this->prophesize(Client::class);
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $authorization->hasQueryParam('scope')
            ->willReturn(true)
            ->shouldBeCalled()
        ;
        $authorization->getQueryParam('scope')
            ->willReturn('scope1')
            ->shouldBeCalled()
        ;
        $authorization->getMetadata()
            ->willReturn(new DataBag([]))->shouldBeCalled();
        $authorization
            ->setResponseParameter('scope', Argument::any())
            ->shouldBeCalled()
            ->will(function () {})
        ;
        $this->getScopeParameterChecker()
            ->check($authorization->reveal())
        ;
    }

    /**
     * @test
     */
    public function anAuthorizationRequestWithAnUnsupportedScopeParameterIsChecked(): void
    {
        $client = $this->prophesize(Client::class);
        $authorization = $this->prophesize(AuthorizationRequest::class);
        $authorization->getClient()
            ->willReturn($client->reveal())
        ;
        $authorization->hasQueryParam('scope')
            ->willReturn(true)
            ->shouldBeCalled()
        ;
        $authorization->getQueryParam('scope')
            ->willReturn('invalid_scope')
            ->shouldBeCalled()
        ;
        $authorization->setResponseParameter('scope', Argument::any())->shouldNotBeCalled();

        try {
            $this->getScopeParameterChecker()
                ->check($authorization->reveal())
            ;
            static::fail('Expected exception nt thrown.');
        } catch (InvalidArgumentException $e) {
            static::assertSame(
                'An unsupported scope was requested. Available scopes are scope1, scope2.',
                $e->getMessage()
            );
        }
    }

    private function getScopeParameterChecker(): ScopeParameterChecker
    {
        if ($this->scopeParameterChecker === null) {
            $scopeRepository = $this->prophesize(ScopeRepository::class);
            $scopeRepository->all()
                ->willReturn(['scope1', 'scope2'])
            ;
            $scopePolicyManager = $this->prophesize(ScopePolicyManager::class);
            $scopePolicyManager->apply(Argument::any(), Argument::type(Client::class))->willReturnArgument(0);

            $this->scopeParameterChecker = new ScopeParameterChecker(
                $scopeRepository->reveal(),
                $scopePolicyManager->reveal()
            );
        }

        return $this->scopeParameterChecker;
    }
}
