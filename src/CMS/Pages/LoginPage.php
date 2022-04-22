<?php

namespace WebEtDesign\UserBundle\CMS\Pages;

use WebEtDesign\CmsBundle\Attribute\AsCmsPage;
use WebEtDesign\CmsBundle\CmsTemplate\AbstractPage;

#[AsCmsPage(code: self::CODE)]
class LoginPage extends AbstractPage
{
    const CODE = 'APP_LOGIN';

    protected ?string $label = 'Page de connexion';

}
