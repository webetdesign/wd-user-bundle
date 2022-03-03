<?php

namespace WebEtDesign\UserBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use WebEtDesign\UserBundle\Entity\WDUser;
use WebEtDesign\UserBundle\Services\UserService;

class AdminLoginAuthenticator extends SocialAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE         = 'admin_login';
    public const AZURE_CONNECT_ROUTE = 'admin_azure_connect';
    public const ADMIN_USER_EDIT_ROUTE = 'admin_app_user_user_edit';

    protected ClientRegistry               $clientRegistry; // Current client
    protected EntityManagerInterface       $em;
    protected RouterInterface              $router;
    protected ParameterBagInterface        $parameterBag;
    protected array                        $client;
    protected UserPasswordEncoderInterface $userPasswordEncoder;
    protected string                       $userClass;
    /**
     * @var mixed|object|null
     */
    private ?WDUser     $user = null;
    private UserService $userService;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        RouterInterface $router,
        ParameterBagInterface $parameterBag,
        UserPasswordEncoderInterface $userPasswordEncoder,
        UserService $userService
    ) {
        $this->clientRegistry      = $clientRegistry;
        $this->em                  = $em;
        $this->router              = $router;
        $this->parameterBag        = $parameterBag;
        $this->client              = [];
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->userService         = $userService;

        $this->userClass = $this->parameterBag->get('wd_user.user.class');
    }

    public function start(
        Request $request,
        AuthenticationException $authException = null
    ): RedirectResponse {
        return new RedirectResponse(
            self::LOGIN_ROUTE,
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function supports(Request $request): bool
    {
        // ****************************** Connection with password ***********************************

        if ($request->attributes->get('_route') === self::LOGIN_ROUTE && isset($request->request->get('login_form')['password'])) {
            return true;
        }

        // ****************************** Connection with Azure ***********************************

        return $request->attributes->get('_route') === self::AZURE_CONNECT_ROUTE && $request->query->get('state') != null;
    }

    public function getCredentials(Request $request)
    {
        // ****************************** Connection with password ***********************************

        if ($request->attributes->get('_route') === self::LOGIN_ROUTE) {
            $credentials = [
                'username'   => $request->request->get('email'),
                'password'   => $request->request->get('login_form')['password'],
                'csrf_token' => $request->request->get('_csrf_token'),
            ];

            $request->getSession()->set(
                Security::LAST_USERNAME,
                $credentials['username']
            );

            return $credentials;
        }

        // ****************************** Connection with Azure ***********************************

        $this->client = $this->getClient($request->getSession()->get('azure_client')); // Set current client
        return $this->fetchAccessToken($this->clientRegistry->getClient($this->client['client_name']));
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // ****************************** Connection with password ***********************************

        if (is_array($credentials)) {
            $existingUser = $this->em->getRepository($this->userClass)
                ->findOneBy(['email' => $credentials['username']]);

            if ($existingUser && $this->userPasswordEncoder->isPasswordValid($existingUser,
                    $credentials['password'])) { // Verify password
                $this->user = $existingUser;
                return $existingUser;
            }

            return null;
        }

        // ****************************** Connection with Azure ***********************************

        $azureUser = $this->clientRegistry->getClient($this->client['client_name'])->fetchUserFromToken($credentials);
        $azureId   = $azureUser->toArray()['oid'];

        $existingUser = $this->em->getRepository($this->userClass)
            ->findOneBy(['azureId' => $azureId]);

        if ($existingUser) {
            return $existingUser;
        }

        $email = isset($azureUser->toArray()['email']) ? $azureUser->toArray()['email'] : null;

        $user = $this->em->getRepository($this->userClass)
            ->findOneBy(['email' => $email]);

        if ($user) {
            $user->setAzureId($azureId);
            $this->em->persist($user);
            $this->em->flush();
        } else {
            $user = $this->userService->createUser($azureUser->toArray(), $azureId,
                $this->client['roles']);
        }

        $this->user = $user;

        return $user;
    }

    //    public function checkCredentials($credentials, UserInterface $user): bool
    //    {
    //        $delay = $this->params->get('wd_rgpd.security.admin_delay');
    //        $maxAttempts = $this->params->get('wd_rgpd.security.admin_max_attempts');
    //
    //        // Deuxième modification, la vérification
    //        if ($this->loginAttemptRepository->countRecentLoginAttempts($credentials['username'], 'admin', $delay) > $maxAttempts) {
    //            // CustomUserMessageAuthenticationException nous permet de définir nous-même le message,
    //            // qui sera affiché à l'utilisateur (ou bien sa clef de traduction).
    //            // Attention toutefois à ne pas révéler trop d'informations dans le messages,
    //            // notamment ne pas indiquer si le compte existe.
    //            throw new CustomUserMessageAuthenticationException('Vous avez essayé de vous connecter avec un mot'
    //                .' de passe incorrect de trop nombreuses fois. Merci de rééssayer utlérieurement');
    //        }
    //
    //        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    //    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): RedirectResponse {
        // ****************************** Connection with password ***********************************

        if ($request->attributes->get('_route') === 'admin_login') {
            $request->getSession()->getFlashBag()->add('error', 'Mot de passe ou email incorrecte');
            $targetUrl = $this->router->generate('admin_login');
            return new RedirectResponse($targetUrl);
        }

        // ****************************** Connection with Azure ***********************************

        $request->getSession()->getFlashBag()->add('error',
            'Impossible de se connecter avec ce compte');
        $targetUrl = $this->router->generate('sonata_admin_dashboard');
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        $providerKey
    ): RedirectResponse {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if ($this->user && $this->user->getLastUpdatePassword() && !$this->user->getAzureId()) {
            try {
                $timeAgo = new \DateTimeImmutable(sprintf('-%d weeks',
                    $this->parameterBag->get('security.admin_password_validity')));
                if ($this->user->getLastUpdatePassword()->getTimeStamp() < $timeAgo->getTimeStamp()) {
                    $request->getSession()->getBag('flashes')->add('error',
                        'Vous devez mettre à jour votre mot de passe.');
                    $targetPath = $this->router->generate(self::ADMIN_USER_EDIT_ROUTE,
                        ['id' => $this->user->getId()]);
                }
            } catch (\Exception $e) {
                dump($e);
                throw new $e;
            }
        }

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }
        $targetUrl = $this->router->generate('sonata_admin_dashboard');
        return new RedirectResponse($targetUrl);
    }

    private function getClient(string $name)
    {                                                                         // Get client by name with session
        $clients = $this->parameterBag->get('wd_user.azure_connect.clients'); //
        $client  = null;
        foreach ($clients as $c) {
            if ($name === $c['client_name']) {
                $client = $c;
            }
        }

        return $client;
    }
}
