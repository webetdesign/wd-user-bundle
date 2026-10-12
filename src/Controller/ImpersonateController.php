<?php

namespace WebEtDesign\UserBundle\Controller;

use App\Entity\User\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ImpersonateController extends AbstractController
{

    #[Route(path: '/impersonate/{user}', name: 'impersonate')]
    public function impersonate(ParameterBagInterface $parameterBag, User $user): RedirectResponse
    {
        return $this->redirectToRoute($parameterBag->get('wd_user.impersonate.login_route'), ['_switch_user' => $user->getUserIdentifier()]);
    }

    #[Route(path: '/exit_impersonate', name: 'exit_impersonate')]
    public function exit(ParameterBagInterface $parameterBag): RedirectResponse
    {
        return $this->redirectToRoute($parameterBag->get('wd_user.impersonate.logout_route'));
    }

}
