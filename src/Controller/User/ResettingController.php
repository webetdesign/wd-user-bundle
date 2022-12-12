<?php

namespace  WebEtDesign\UserBundle\Controller\User;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebEtDesign\CmsBundle\Controller\BaseCmsController;
use WebEtDesign\UserBundle\Event\Mail\ResettingEvent;
use WebEtDesign\UserBundle\Form\User\ResettingRequestType;
use WebEtDesign\UserBundle\Form\User\ResettingType;
use WebEtDesign\UserBundle\Repository\WDUserRepository;
use WebEtDesign\UserBundle\Security\FrontLoginAuthenticator;

class ResettingController extends BaseCmsController
{
    const ROUTE_RESETTING_REQUEST = 'reset_password_request';
    const ROUTE_RESETTING = 'reset_password';

    private WDUserRepository               $userRepository;
    private TokenGeneratorInterface      $tokenGenerator;
    private EventDispatcherInterface     $eventDispatcher;
    private UserPasswordEncoderInterface $userPasswordEncoder;
    private EntityManagerInterface       $em;

    public function __construct(
        EntityManagerInterface $em,
        WDUserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator,
        EventDispatcherInterface $eventDispatcher,
        UserPasswordEncoderInterface $userPasswordEncoder
    ) {
        $this->userRepository      = $userRepository;
        $this->tokenGenerator      = $tokenGenerator;
        $this->eventDispatcher     = $eventDispatcher;
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->em = $em;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function request(Request $request): Response
    {

        $form = $this->createForm(ResettingRequestType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user = $this->userRepository->findOneBy([
                'email'   => $form->getData()['email'],
                'enabled' => true
            ]);

            if (!$user) {
                $form->get('email')->addError(new FormError( 'user_not_found'));
            }

            if ($form->isValid()) {
                $token = $this->tokenGenerator->generateToken();

                $user->setConfirmationToken($token);
                $user->setPasswordRequestedAt(new DateTime('now'));

                $this->em->persist($user);
                $this->em->flush();

                $event = new ResettingEvent($user, $this->generateUrl(self::ROUTE_RESETTING, ['token' => $user->getConfirmationToken()], UrlGeneratorInterface::ABSOLUTE_URL));
                $this->eventDispatcher->dispatch($event, ResettingEvent::RESETTING_EVENT);

                $emailSent = true;
            }

        }
        return $this->defaultRender([
            'form'      => $form->createView(),
            'emailSent' => $emailSent ?? false,
        ]);
    }

    public function resetting(Request $request, ParameterBagInterface $parameterBag, $token): Response
    {
        $user = $this->userRepository->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(ResettingType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encoded = $this->userPasswordEncoder->encodePassword($user, $user->getPlainPassword());

            $user->eraseCredentials();
            $user->setConfirmationToken(null);
            $this->userRepository->upgradePassword($user, $encoded);

            $this->addFlash('success', 'password_reset_success');
            return $this->redirectToRoute($parameterBag->get('wd_user.resetting.success_redirect_route'));
        }

        return $this->defaultRender([
            'form' => $form->createView(),
        ]);
    }
}
