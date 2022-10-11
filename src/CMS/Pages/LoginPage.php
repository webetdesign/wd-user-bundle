<?php

namespace WebEtDesign\UserBundle\CMS\Pages;

use WebEtDesign\CmsBundle\Attribute\AsCmsPage;
use WebEtDesign\CmsBundle\CmsTemplate\AbstractPage;

abstract class LoginPage extends AbstractPage
{
    const CODE = 'APP_LOGIN';

    protected ?string $label = 'Page de connexion';

}
