<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Form\User;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResettingRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'form.request.email.label',
            'attr' => [
                'placeholder' => 'form.request.email.placeholder'
            ]
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'form.request.submit.label',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
           'translation_domain' => 'wd_user',
        ]);
    }


}
