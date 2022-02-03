<?php


namespace  WebEtDesign\UserBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use WebEtDesign\RgpdBundle\Entity\LoginAttempt;
use WebEtDesign\RgpdBundle\Repository\LoginAttemptRepository;
use WebEtDesign\UserBundle\Entity\WDUser;


class AdminLoginAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'admin_login';

    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UserPasswordEncoderInterface $passwordEncoder,
        private LoginAttemptRepository $loginAttemptRepository
    )
    {}

    public function supports(Request $request): ?bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * Override to change what happens after a bad username/password is submitted.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     * @return RedirectResponse|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        if ($request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        $url = $this->getLoginUrl();

        // Début de notre modification: on sauvegarde une tentative de connexion
        $newLoginAttempt = new LoginAttempt($request->getClientIp(), $request->request->get('_username'), 'admin');
        $this->entityManager->persist($newLoginAttempt);
        $this->entityManager->flush();

        return new RedirectResponse($url);
    }

    /**
     * @throws Exception
     */
    public function authenticate(Request $request): Passport
    {
        $delay = $this->params->get('wd_rgpd.security.admin_delay');
        $maxAttempts = $this->params->get('wd_rgpd.security.admin_max_attempts');

        $password = $request->request->get('_password');
        $username = $request->request->get('_username');
        $csrfToken = $request->request->get('_csrf_token');

        // Deuxième modification, la vérification
        if ($this->loginAttemptRepository->countRecentLoginAttempts($username, 'admin', $delay) > $maxAttempts) {
            // CustomUserMessageAuthenticationException nous permet de définir nous-même le message,
            // qui sera affiché à l'utilisateur (ou bien sa clef de traduction).
            // Attention toutefois à ne pas révéler trop d'informations dans le messages,
            // notamment ne pas indiquer si le compte existe.
            throw new CustomUserMessageAuthenticationException('Vous avez essayé de vous connecter avec un mot'
                .' de passe incorrect de trop nombreuses fois. Merci de rééssayer utlérieurement');
        }

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge()
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $firewallName): ?RedirectResponse
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        /** @var WDUser $user */
        $user = $token->getUser();

        if($user->getLastUpdatePassword()) {
            try {
                $timeAgo = new \DateTimeImmutable(sprintf('-%d weeks', $this->params->get('security.admin_password_validity')));
                if($user->getLastUpdatePassword()->getTimeStamp() < $timeAgo->getTimeStamp()) {
                    $request->getSession()->getBag('flashes')->add('error', 'Vous devez mettre à jour votre mot de passe.');
                    $targetPath = $this->urlGenerator->generate('admin_app_user_user_edit', ['id' => $user->getId()]);
                }
            } catch (Exception $e) {
                throw new $e;
            }
        }

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        // For example : return new RedirectResponse($this->urlGenerator->generate('some_route'));
        return new RedirectResponse($this->getLoginUrl());
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
    
}
