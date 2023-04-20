<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Security;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use WebEtDesign\UserBundle\Security\Passport\LoginAttemptBadge;

class AdminFormLoginAuthenticator extends AbstractAuthenticator
{

    public const ERROR_CONTEXT = 'form_login';

    public function __construct(
        protected HttpUtils $httpUtils,
        protected UserProviderInterface $userProvider,
        protected RouterInterface $router,
        protected ParameterBagInterface $parameterBag,
        protected array $options = []
    ) {
        $this->options = array_merge([
            'username_parameter' => '_username',
            'password_parameter' => '_password',
            'login_path'         => 'admin_login',
            'check_path'         => 'admin_login_check',
            'post_only'          => true,
            'form_only'          => false,
            'enable_csrf'        => true,
            'csrf_parameter'     => '_csrf_token',
            'csrf_token_id'      => 'authenticate',
        ], $options);
    }

    public function supports(Request $request): ?bool
    {
        return (!$this->options['post_only'] || $request->isMethod('POST'))
            && $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);

        $passport = new Passport(
            new UserBadge($credentials['username'], [$this->userProvider, 'loadUserByIdentifier']),
            new PasswordCredentials($credentials['password']),
            [new RememberMeBadge()]
        );

        if ($this->options['enable_csrf']) {
            $passport->addBadge(new CsrfTokenBadge($this->options['csrf_token_id'], $credentials['csrf_token']));
        }

        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($credentials['password'], $this->userProvider));
        }

        $passport->addBadge(new LoginAttemptBadge($request->getClientIp(), $request->get('_username')));

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->httpUtils->generateUri($request, 'sonata_admin_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $response = new RedirectResponse($this->router->generate('admin_login'));

        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        $request->getSession()->set('_security.error_context', self::ERROR_CONTEXT);

        return $response;
    }

    private function getCredentials(Request $request): array
    {
        $credentials               = [];
        $credentials['csrf_token'] = $request->get($this->options['csrf_parameter']);

        if ($this->options['post_only']) {
            $credentials['username'] = $request->request->get($this->options['username_parameter']);
            $credentials['password'] = $request->request->get($this->options['password_parameter'], '');
        } else {
            $credentials['username'] = $request->get($this->options['username_parameter']);
            $credentials['password'] = $request->get($this->options['password_parameter'], '');
        }

        if (!\is_string($credentials['username']) && !$credentials['username'] instanceof \Stringable) {
            throw new BadRequestHttpException(sprintf('The key "%s" must be a string, "%s" given.',
                $this->options['username_parameter'], \gettype($credentials['username'])));
        }

        $credentials['username'] = trim($credentials['username']);

        if (\strlen($credentials['username']) > UserBadge::MAX_USERNAME_LENGTH) {
            throw new BadCredentialsException('Invalid username.');
        }

        $request->getSession()->set(Security::LAST_USERNAME, $credentials['username']);

        return $credentials;
    }
}
