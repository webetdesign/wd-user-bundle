<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Controller\Admin;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use WebEtDesign\UserBundle\Security\AdminFormLoginAuthenticator;

class SecurityController extends AbstractController
{

    #[Route(path: '/admin/login', name: 'admin_login')]
    #[Route(path: '/admin/login_check/{client_name}', name: 'admin_login_check', defaults: ['client_name' => null])]
    public function login(
        Request $request,
        RouterInterface $router,
        AuthenticationUtils $authenticationUtils,
        ClientRegistry $clientRegistry,
        ParameterBagInterface $parameterBag,
    ): Response {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $withPassword = false;
        $action       = $router->generate('admin_login');


        // first form with email or username are submitted
        if ($request->isMethod('POST')) {
            $email = $request->get('_username');

            $configClients = $parameterBag->get('wd_user.azure.clients');

            // loop on user_bundle's asure_directory clients config to find on available client
            foreach ($configClients as $config) {
                if ($config['enabled']) {
                    $client  = $clientRegistry->getClient($config['client_name']);
                    $domains = implode('|', array_map(fn($domain) => preg_quote($domain), $config['domains']));
                    if ($client && preg_match("/$domains/", $email)) {
                        // Client are found and activated, we redirect user on azure auth
                        return $client->redirect(
                            ['openid', 'email', 'profile'],
                            ['login_hint' => $email, 'client_name' => $config['client_name']]
                        );
                    }
                }
            }

            // No azure configuration has been found, we display the second form with password field
            $action       = $router->generate('admin_login_check');
            $lastUsername = $request->get('_username');
            $withPassword = true;
        } elseif ($request->getSession()->get('_security.error_context') === AdminFormLoginAuthenticator::ERROR_CONTEXT) {
            // When user's credentials (login / password) are wrong, we forced the second form with password field.
            $request->getSession()->remove('_security.error_context');
            $action       = $router->generate('admin_login_check');
            $withPassword = true;
        }

        return $this->render('@WDUser/admin/security/login.html.twig', [
            'action'               => $action,
            'last_username'        => $lastUsername,
            'error'                => $error,
            'csrf_token_intention' => 'authenticate',


            'with_password' => $withPassword
        ]);
    }

    #[Route(path: '/admin/logout', name: 'admin_logout')]
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
