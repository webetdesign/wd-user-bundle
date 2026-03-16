<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Security\Passport;

use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use WebEtDesign\UserBundle\Security\Passport\Badge\AzureUserBadge;
use WebEtDesign\UserBundle\Services\AuthUserHelper;

class AzurePassport extends SelfValidatingPassport
{

    public function __construct(
        OAuth2ClientInterface $client,
        ?callable $userLoader = null,
        ?AuthUserHelper $userHelper = null,
        ?string $clientName = null,
        array $badges = []
    ) {
        $userData = $client->fetchUserFromToken($client->getAccessToken());

        $userBadge = new AzureUserBadge($userData, $userLoader, $userHelper, $clientName);

        parent::__construct($userBadge, $badges);
        // Map custom UserBadge to the core UserBadge class for Passport::getUser()
        $this->addBadge($userBadge, UserBadge::class);
    }

}
