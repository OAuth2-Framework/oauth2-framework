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

namespace OAuth2Framework\Bundle\Command;

use OAuth2Framework\Bundle\Model\ClientRepository;
use OAuth2Framework\Component\Core\Client\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

final class CreateClientCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oauth2-server:client:create')
            ->setDescription('Create a new client')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command will create a new client.

  <info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var ClientRepository
         */
        $service = $this->getContainer()->get(ClientRepository::class);

        $this->selectResponseTypes($input, $output, $client);
        $this->selectGrantTypes($input, $output, $client);
        $this->selectRedirectUris($input, $output, $client);
        $this->selectTokenEndpointAuthenticationMethod($input, $output, $client);

        $service->saveClient($client);

        $output->writeln('A client has been created');
        $output->writeln(sprintf('Its configuration is "%s"', json_encode($client, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Client          $client
     */
    private function selectTokenEndpointAuthenticationMethod(InputInterface $input, OutputInterface $output, Client $client)
    {
        $token_endpoint_auth_method_manager = $this->getContainer()->get('oauth2_server.client_authentication.manager');
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select the token endpoint authentication method.',
            $token_endpoint_auth_method_manager->getSupportedTokenEndpointAuthMethods()
        );
        $question->setMultiselect(false);
        $question->setErrorMessage('The token endpoint authentication method "%s" is invalid.');

        $token_endpoint_auth_method = $helper->ask($input, $output, $question);
        $client->set('token_endpoint_auth_method', $token_endpoint_auth_method);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Client          $client
     */
    private function selectRedirectUris(InputInterface $input, OutputInterface $output, Client $client)
    {
        $redirect_uris = [];
        $helper = $this->getHelper('question');

        do {
            $question = new Question('Enter a redirect URI/URN (leave blank to continue).');
            $uri = $helper->ask($input, $output, $question);
            if (!empty($uri)) {
                if (false === $this->isAnUrlOrUrn($uri, false)) {
                    $output->writeln('Invalid input.');
                } else {
                    $redirect_uris[] = $uri;
                }
            }
        } while (!empty($uri));

        if (!empty($redirect_uris)) {
            $client->set('redirect_uris', $redirect_uris);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Client          $client
     */
    private function selectResponseTypes(InputInterface $input, OutputInterface $output, Client $client)
    {
        $authorization_factory = $this->getContainer()->get('oauth2_server.response_type.manager');

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select allowed response types.',
            $authorization_factory->getSupportedResponseTypes()
        );
        $question->setMultiselect(true);
        $question->setErrorMessage('The response type "%s" is invalid.');

        $grant_types = $helper->ask($input, $output, $question);
        $client->set('response_types', $grant_types);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Client          $client
     */
    private function selectGrantTypes(InputInterface $input, OutputInterface $output, Client $client)
    {
        $token_endpoint = $this->getContainer()->get('oauth2_server.grant_type.manager');

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select allowed grant types.',
            $token_endpoint->getSupportedGrantTypes()
        );
        $question->setMultiselect(true);
        $question->setErrorMessage('The grant type "%s" is invalid.');

        $grant_types = $helper->ask($input, $output, $question);
        $client->set('grant_types', $grant_types);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->getContainer()->has('oauth2_server.client_manager');
    }

    /**
     * @param string $uri
     * @param bool   $path_traversal_allowed
     *
     * @return bool
     */
    private static function isAnUrlOrUrn($uri, $path_traversal_allowed)
    {
        if ('urn:' === mb_substr($uri, 0, 4, '8bit')) {
            if (false === self::checkUrn($uri)) {
                return false;
            }
        } else {
            if (false === self::checkUrl($uri, $path_traversal_allowed)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $url
     * @param bool   $path_traversal_allowed
     *
     * @return bool
     */
    public static function checkUrl($url, $path_traversal_allowed)
    {
        // If URI is not a valid URI, return false
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);

        // Checks for path traversal (e.g. http://foo.bar/redirect/../bad/url)
        if (isset($parsed['path']) && !$path_traversal_allowed) {
            $path = urldecode($parsed['path']);
            // check for 'path traversal'
            if (preg_match('#/\.\.?(/|$)#', $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $urn
     *
     * @return bool
     */
    private static function checkUrn($urn)
    {
        return 1 === preg_match('/^urn:[a-z0-9][a-z0-9-]{0,31}:[a-z0-9()+,\-.:=@;$_!*\'%\/?#]+$/', $urn);
    }
}
