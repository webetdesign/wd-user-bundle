<?php


namespace WebEtDesign\UserBundle\Form\User;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\UserBundle\Entity\WDUser;

class ResettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type'            => PasswordType::class,
            'options'         => [
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
            ],
            'first_options'   => ['label' => 'form.resetting.new_password.label'],
            'second_options'  => ['label' => 'form.resetting.new_password_confirmation.label'],
            'invalid_message' => 'form.resetting.error.new_password.mismatch',
        ]);

        $builder->add('submit', SubmitType::class, [
            'label' => 'form.resetting.submit.label',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => WDUser::class,
            'translation_domain' => 'resetting',
            'validation_groups' => ['ResetPassword']
        ]);
    }


}
