<?php

namespace WebEtDesign\UserBundle\Security\Azure;

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
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebEtDesign\UserBundle\Event\OnCreateUserFromAzureEvent;

class AzureAuthenticator extends SocialAuthenticator
{
    protected ClientRegistry               $clientRegistry; // Current client
    protected EntityManagerInterface       $em;
    protected RouterInterface              $router;
    protected ParameterBagInterface        $parameterBag;
    protected array                        $client;
    protected UserPasswordEncoderInterface $userPasswordEncoder;
    private EventDispatcherInterface       $eventDispatcher;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        RouterInterface $router,
        ParameterBagInterface $parameterBag,
        UserPasswordEncoderInterface $userPasswordEncoder,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->clientRegistry      = $clientRegistry;
        $this->em                  = $em;
        $this->router              = $router;
        $this->parameterBag        = $parameterBag;
        $this->client              = [];
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->eventDispatcher     = $eventDispatcher;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            'admin_login_email',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function supports(Request $request)
    {
        // ****************************** Connection with password ***********************************

        if ($request->attributes->get('_route') === 'admin_login_email' && isset($request->request->get('login_form')['password'])) {
            return true;
        }

        // ****************************** Connection with Azure ***********************************

        return $request->attributes->get('_route') === 'admin_azure_connect' && $request->query->get('state') != null;
    }

    public function getCredentials(Request $request)
    {
        // ****************************** Connection with password ***********************************

        if ($request->attributes->get('_route') === 'admin_login_email') {
            $credentials = [
                'username'   => $request->request->get('email'),
                'password'   => $request->request->get('login_form')['password'],
                'csrf_token' => $request->request->get('_csrf_token'),
            ];

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
            $existingUser = $this->em->getRepository($this->parameterBag->get('wd_user.user.class'))
                ->findOneBy(['email' => $credentials['username']]);

            if ($existingUser && $this->userPasswordEncoder->isPasswordValid($existingUser,
                    $credentials['password'])) { // Verify password
                return $existingUser;
            }

            return null;
        }

        // ****************************** Connection with Azure ***********************************

        $azureUser = $this->clientRegistry->getClient($this->client['client_name'])->fetchUserFromToken($credentials);
        $azureId   = $azureUser->toArray()['oid'];

        $existingUser = $this->em->getRepository($this->parameterBag->get('wd_user.user.class'))
            ->findOneBy(['azureId' => $azureId]);

        if ($existingUser) {
            return $existingUser;
        }

        $email = isset($azureUser->toArray()['email']) ? $azureUser->toArray()['email'] : null;

        $user = $this->em->getRepository($this->parameterBag->get('wd_user.user.class'))
            ->findOneBy(['email' => $email]);

        if ($user) {
            $user->setAzureId($azureId);
            $this->em->persist($user);
            $this->em->flush();
        } else {
            $user = $this->createUser($azureUser->toArray(), $azureId, $this->client['roles']);
        }
        return $user;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // ****************************** Connection with password ***********************************

        if ($request->attributes->get('_route') === 'admin_login_email') {
            $request->getSession()->getFlashBag()->add('error', 'Mot de passe ou email incorrecte');
            $targetUrl = $this->router->generate('admin_login_email');
            return new RedirectResponse($targetUrl);
        }

        // ****************************** Connection with Azure ***********************************

        $request->getSession()->getFlashBag()->add('error',
            'Impossible de se connecter avec ce compte');
        $targetUrl = $this->router->generate('sonata_admin_dashboard');
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetUrl = $this->router->generate('sonata_admin_dashboard');
        return new RedirectResponse($targetUrl);
    }

    public function createUser(array $azureUser, string $azureId, array $roles)
    {
        $userClass = $this->parameterBag->get('wd_user.user.class');

        $user = new $userClass();

        $user->setAzureId($azureId);

        $name = explode(" ", $azureUser['name']);
        $user->setFirstname($name[0]);
        if (sizeof($name) == 2) {
            $user->setLastname($name[1]);
        }

        $user->setEmail($azureUser['email']);
        $user->setUsername($azureUser['email']);
        $user->setEnabled(true);
        $user->setPermissions($roles);

        // Subscribe to this event to add other data to user.
        $event = new OnCreateUserFromAzureEvent('admin', $user, $azureUser);
        $this->eventDispatcher->dispatch($event, OnCreateUserFromAzureEvent::NAME);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function getClient(string $name)
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
