<?php

namespace WebEtDesign\UserBundle\CMS\Pages;

use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Attribute\AsCmsPage;
use WebEtDesign\CmsBundle\CmsTemplate\AbstractPage;
use WebEtDesign\CmsBundle\DependencyInjection\Models\RouteDefinition;
use WebEtDesign\UserBundle\Controller\User\ResettingController;

#[AsCmsPage(code: self::CODE)]
class ResetPasswordRequestPage extends AbstractPage
{
    const CODE = ResettingController::ROUTE_RESETTING_REQUEST;

    protected ?string $label = 'Demande de rĂ©initialisation de mot de passe';

    protected ?string $template = 'pages/user/resetting_request.html.twig';

    public function getRoute(): ?RouteDefinition
    {
        return RouteDefinition::new()
            ->setController(ResettingController::class)
            ->setAction('request')
            ->setName(ResettingController::ROUTE_RESETTING_REQUEST)
            ->setMethods([
                Request::METHOD_GET,
                Request::METHOD_POST,
            ]);
    }
}
