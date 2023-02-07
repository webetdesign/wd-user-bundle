<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use WebEtDesign\UserBundle\Security\Passport\AzurePassport;
use WebEtDesign\UserBundle\Services\AuthUserHelper;

class AdminAzureLoginAuthenticator extends AbstractAuthenticator
{

    public function __construct(
        protected HttpUtils $httpUtils,
        protected UserProviderInterface $userProvider,
        protected ClientRegistry $clientRegistry,
        protected AuthUserHelper $userHelper,
        protected array $options = []
    ) {
        $this->options = array_merge([
            'check_path' => 'admin_login_check',
        ], $options);
    }

    public function supports(Request $request): ?bool
    {
        return $this->httpUtils->checkRequestPath($request, $this->options['check_path'])
            && $request->query->has('state') && $request->query->has('session_state')
            && $request->attributes->has('client_name');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient($request->get('client_name'));

        return new AzurePassport(
            $client,
            [$this->userProvider, 'loadUserByIdentifier'],
            $this->userHelper,
            $request->get('client_name')
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->httpUtils->generateUri($request, 'sonata_admin_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->httpUtils->generateUri($request, 'admin_login'));
    }
}
