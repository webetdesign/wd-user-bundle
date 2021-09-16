<?php


namespace  WebEtDesign\UserBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use WebEtDesign\RgpdBundle\Entity\LoginAttempt;
use WebEtDesign\RgpdBundle\Repository\LoginAttemptRepository;
use WebEtDesign\UserBundle\Entity\WDUser;


class AdminLoginAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'admin_login';


    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private UrlGeneratorInterface $urlGenerator;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private UserPasswordEncoderInterface $passwordEncoder;
    private LoginAttemptRepository $loginAttemptRepository;
    private WDUser $user;

    public function __construct(
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        LoginAttemptRepository $loginAttemptRepository
    )
    {
        $this->params = $params;
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->loginAttemptRepository = $loginAttemptRepository;
    }

    public function supports(Request $request)
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * Override to change what happens after a bad username/password is submitted.
     *
     * @return RedirectResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
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

    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('_username'),
            'password' => $request->request->get('_password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $this->user = $this->entityManager->getRepository($this->params->get('wd_user.user.class'))->findOneBy(['username' => $credentials['username']]);

        if (!$this->user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Utilisateur introuvable');
        }

        return $this->user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $delay = $this->params->get('wd_rgpd.security.admin_delay');
        $maxAttempts = $this->params->get('wd_rgpd.security.admin_max_attempts');

        // Deuxième modification, la vérification
        if ($this->loginAttemptRepository->countRecentLoginAttempts($credentials['username'], 'admin', $delay) > $maxAttempts) {
            // CustomUserMessageAuthenticationException nous permet de définir nous-même le message,
            // qui sera affiché à l'utilisateur (ou bien sa clef de traduction).
            // Attention toutefois à ne pas révéler trop d'informations dans le messages,
            // notamment ne pas indiquer si le compte existe.
            throw new CustomUserMessageAuthenticationException('Vous avez essayé de vous connecter avec un mot'
                .' de passe incorrect de trop nombreuses fois. Merci de rééssayer utlérieurement');
        }

        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if($this->user->getLastUpdatePassword()) {
            try {
                $timeAgo = new \DateTimeImmutable(sprintf('-%d weeks', $this->params->get('security.admin_password_validity')));
                if($this->user->getLastUpdatePassword()->getTimeStamp() < $timeAgo->getTimeStamp()) {
                    $request->getSession()->getBag('flashes')->add('error', 'Vous devez mettre à jour votre mot de passe.');
                    $targetPath = $this->urlGenerator->generate('admin_app_user_user_edit', ['id' => $this->user->getId()]);
                }
            } catch (\Exception $e) {
                throw new $e;
            }
        }

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        // For example : return new RedirectResponse($this->urlGenerator->generate('some_route'));
        return new RedirectResponse($this->getLoginUrl());
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
