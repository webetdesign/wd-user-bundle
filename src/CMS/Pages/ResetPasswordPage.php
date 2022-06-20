<?php

namespace WebEtDesign\UserBundle\CMS\Pages;

use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Attribute\AsCmsPage;
use WebEtDesign\CmsBundle\CmsTemplate\AbstractPage;
use WebEtDesign\CmsBundle\DependencyInjection\Models\RouteAttributeDefinition;
use WebEtDesign\CmsBundle\DependencyInjection\Models\RouteDefinition;
use WebEtDesign\UserBundle\Controller\User\ResettingController;

#[AsCmsPage(code: self::CODE)]
class ResetPasswordPage extends AbstractPage
{
    const CODE = 'APP_RESET_PASSWORD';

    protected ?string $label = 'Réinitialisation de mot de passe oublié';

    protected ?string $template = 'pages/user/resetting.html.twig';

    public function getRoute(): ?RouteDefinition
    {
        return RouteDefinition::new()
            ->setController(ResettingController::class)
            ->setAction('resetting')
            ->setName(ResettingController::ROUTE_RESETTING)
            ->setMethods([
                Request::METHOD_GET,
                Request::METHOD_POST,
            ])
            ->setAttributes([
                RouteAttributeDefinition::new('token'),
            ]);
    }
}
