<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['email'] != null){
            $builder
                ->add('username', EmailType::class, [
                    'data' => $options['email'],
                    'disabled' => 'true',
                    'attr' => [
                        'placeholder' => 'security.login.email'
                    ],
                    'constraints' => [
                        new Email(),
                        new NotBlank()
                    ]
                ])
                ->add('password', PasswordType::class, [
                    'attr' => [
                        'placeholder' => 'security.login.password'
                    ],
                    'constraints' => [
                        new NotBlank()
                    ]
                ])


            ;
        }else{
            $builder
                ->add('username', EmailType::class, [
                    'attr' => [
                        'placeholder' => 'security.login.email'
                    ],
                    'constraints' => [
                        new Email(),
                        new NotBlank()
                    ]
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           'translation_domain' => 'wd_user',
            'email' => null
        ]);
    }


}
