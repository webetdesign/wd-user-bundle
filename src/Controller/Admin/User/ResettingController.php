<?php

namespace WebEtDesign\UserBundle\Controller\Admin\User;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebEtDesign\UserBundle\Event\Mail\AdminResettingEvent;
use WebEtDesign\UserBundle\Form\User\ResettingRequestType;
use WebEtDesign\UserBundle\Form\User\ResettingType;
use WebEtDesign\UserBundle\Repository\WDUserRepository;
use WebEtDesign\UserBundle\Security\AdminLoginAuthenticator;
use WebEtDesign\UserBundle\Security\FrontLoginAuthenticator;

class ResettingController extends AbstractController
{
//    public function __construct(
//        private EntityManagerInterface $em,
//        private WDUserRepository $userRepository,
//        private TokenGeneratorInterface $tokenGenerator,
//        private EventDispatcherInterface $eventDispatcher,
//        private UserPasswordHasherInterface $userPasswordHasher
//    ) {}
//
//    /**
//     * @param Request $request
//     * @return Response
//     * @Route("/admin/resetting/request", name="admin_reset_password_request")
//     */
//    public function request(Request $request): Response
//    {
//        $form = $this->createForm(ResettingRequestType::class);
//
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted()) {
//
//
//            $user = $this->userRepository->findOneBy([
//                'email'   => $form->getData()['email'],
//                'enabled' => true
//            ]);
//
//            if (!$user || $user->getAzureId() !== null) {
//                $form->addError(new FormError(null, 'user_not_found', [
//                    'email' => $form->getData()['email']
//                ]));
//                return $this->redirectToRoute(AdminLoginAuthenticator::LOGIN_ROUTE);
//            }
//
//            if ($form->isValid()) {
//                $token = $this->tokenGenerator->generateToken();
//
//                $user->setConfirmationToken($token);
//                $user->setPasswordRequestedAt(new DateTime('now'));
//
//                $this->em->persist($user);
//                $this->em->flush();
//
//                $event = new AdminResettingEvent($user);
//                $this->eventDispatcher->dispatch($event, AdminResettingEvent::NAME);
//
//                $this->addFlash('success',
//                    'Nous venons de vous envoyer un mail pour réinitialiser votre mot de passe.');
//
//                return $this->redirectToRoute(AdminLoginAuthenticator::LOGIN_ROUTE);
//            }
//
//        }
//
//
//        return $this->render('@WDUser/admin/security/resetting_request.html.twig', [
//            'form' => $form->createView()
//        ]);
//    }
//
//    /**
//     * @param Request $request
//     * @param $token
//     * @return Response
//     * @Route("/admin/resetting/request/{token}", name="admin_reset_password")*
//     */
//    public function resetting(Request $request, $token): Response
//    {
//        $user = $this->userRepository->findOneBy(['confirmationToken' => $token]);
//
//        if (!$user) {
//            throw new NotFoundHttpException();
//        }
//
//        $form = $this->createForm(ResettingType::class, $user);
//
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $encoded = $this->userPasswordHasher->hashPassword($user, $user->getPlainPassword());
//
//            $user->eraseCredentials();
//            $user->setConfirmationToken(null);
//            $this->userRepository->upgradePassword($user, $encoded);
//
//            $this->addFlash('success', 'password_reset_success');
//            return $this->redirectToRoute(AdminLoginAuthenticator::LOGIN_ROUTE);
//        }
//
//        return $this->render('@WDUser/admin/security/resetting.html.twig', [
//            'form' => $form->createView()
//        ]);
//    }
}
