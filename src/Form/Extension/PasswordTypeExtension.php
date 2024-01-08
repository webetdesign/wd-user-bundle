<?php
declare(strict_types=1);

namespace WebEtDesign\UserBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class PasswordTypeExtension extends AbstractTypeExtension
{

    public static function getExtendedTypes(): iterable
    {
        yield PasswordType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$options['use_strength']) {
            return;
        }

        $icon_show_password = $options['icon_show_password'] ?? ($options['sonata_admin'] ? 'fas fa-eye' : 'bi bi-eye');
        $icon_hide_password = $options['icon_hide_password'] ?? ($options['sonata_admin'] ? 'fas fa-eye-slash' : 'bi bi-eye-slash');

        if ($options['sonata_admin']) {
            array_splice($view->vars['block_prefixes'], -1, 0, 'wd_user_password_admin');
        } else {
            array_splice($view->vars['block_prefixes'], -1, 0, 'wd_user_password');
        }

        $view->vars['is_admin']           = $options['sonata_admin'];
        $view->vars['min_strength']       = $options['min_strength'];
        $view->vars['icon_show_password'] = $icon_show_password;
        $view->vars['icon_hide_password'] = $icon_hide_password;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'use_strength' => false,
            'sonata_admin' => false,
            'min_strength' => PasswordStrength::STRENGTH_MEDIUM,
        ]);

        $resolver->setDefined([
            'icon_show_password',
            'icon_hide_password',
        ]);
    }

}
