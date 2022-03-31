<?php
namespace WebEtDesign\UserBundle\Controller\Admin\User;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WebEtDesign\UserBundle\Form\Security\LoginFormType;

class SecurityController extends AbstractController
{
    private ParameterBagInterface $parameterBag;
    private ClientRegistry $clientRegistry;

    public function __construct(ParameterBagInterface $parameterBag, ClientRegistry $clientRegistry)
    {
        $this->parameterBag = $parameterBag;
        $this->clientRegistry = $clientRegistry;
    }

    /**
     * @Route ("/admin/login", name=WebEtDesign\UserBundle\Security\AdminLoginAuthenticator::LOGIN_ROUTE)
     */
    public function login(Request $request): RedirectResponse|Response
    {

        $form = $this->createForm(LoginFormType::class, null);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){ // Management of the redirection when entering the email
            $email = $form['username']->getData();
            $domain = explode("@",$email)[1];

            $clients = $this->parameterBag->get('wd_user.azure_connect.clients');

            foreach ($clients as $client){
                if ($client["domain"] == $domain){ // Si le domaine est celui d'un client azure
                    $request->getSession()->set('azure_client', $client["client_name"]); // Stores the name of the client used to retrieve it in the authenticator
                    return $this->clientRegistry->getClient($client["client_name"])->redirect(['openid','email','profile'],["login_hint" => $email]);
                }
            }

            $form = $this->createForm(LoginFormType::class, null, [
                'email' => $email
            ]);

            return $this->render('@WDUser/admin/security/email_login.html.twig',[
                'displayPassword' => true,
                'email' => $email, // Input hidden
                'form' => $form->createView()
            ]);

        }

        if($form->isSubmitted() && !$form->isValid() && !isset($form->getExtraData()['password'])){ // If the email format is incorrect
            $this->addFlash("error","Format de l'email incorecte !");
        }

        return $this->render('@WDUser/admin/security/email_login.html.twig',[ // Sending the first form
            'displayPassword' => false,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route ("/admin/connect/azure", name="admin_azure_connect")
     */
    public function azureCallBack(){
        // Just for set the redirection URI '/admin/connect/azure ,azure_connect'
    }

    /**
     * @Route("/admin/logout", name="admin_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
