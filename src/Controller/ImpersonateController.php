<?php

namespace WebEtDesign\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImpersonateController extends AbstractController
{

    #[Route(path: '/exit_impersonate', name: 'exit_impersonate')]
    public function exit(ParameterBagInterface $parameterBag): RedirectResponse
    {
        return $this->redirectToRoute($parameterBag->get('wd_user.impersonate.logout_route'));
    }

}
