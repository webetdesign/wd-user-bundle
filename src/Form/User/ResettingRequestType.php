<?php


namespace WebEtDesign\UserBundle\Form\User;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResettingRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
           'translation_domain' => 'wd_user',
        ]);
    }


}
