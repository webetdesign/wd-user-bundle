<?php


namespace WebEtDesign\UserBundle\Controller\User;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use WebEtDesign\CmsBundle\Services\CmsHelper;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name=WebEtDesign\UserBundle\Security\FrontLoginAuthenticator::LOGIN_ROUTE)
     * @param AuthenticationUtils $authenticationUtils
     * @param CmsHelper $cmsHelper
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils, CmsHelper $cmsHelper): Response
    {
        // Le firewall de Symfony ne permet de définir plusieurs routes pour la page de login.
        // La fonction suivante permet de récupérer une page afin de la passer au template de manière
        // à conserver le header et le footer qui on besoin d'une page pour ce générer.
        $page = $cmsHelper->retrievePageByRouteName('%login_page');

        if ($this->getUser()) {
            return $this->redirectToRoute('profile');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            '@WDUserBundle/Resources/views/pages/user/login.html.twig',
            [
                'page'          => $page,
                'last_username' => $lastUsername,
                'error'         => $error,
            ]
        );
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }

    /**
     * @Route("/exit_impersonate", name="exit_impersonate")
     *
     * @return RedirectResponse
     */
    public function exitImpersonate(): RedirectResponse
    {
        return $this->redirectToRoute('admin_app_user_user_list');
    }
}
