<?php

declare(strict_types=1);

namespace WebEtDesign\UserBundle\Admin\User;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use WebEtDesign\CmsBundle\Form\Type\SecurityRolesType;

class UserAdmin extends AbstractAdmin
{
    protected ?UserPasswordHasherInterface $userPasswordHasher = null;

    /**
     * @param UserPasswordHasherInterface|null $userPasswordHasher
     * @return UserAdmin
     */
    public function setUserPasswordHasher(?UserPasswordHasherInterface $userPasswordHasher
    ): UserAdmin
    {
        $this->userPasswordHasher = $userPasswordHasher;

        return $this;
    }

    /**
     * @return UserPasswordHasherInterface|null
     */
    public function getUserPasswordHasher(): ?UserPasswordHasherInterface
    {
        return $this->userPasswordHasher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->addIdentifier('username')
            ->add('email');
        if (property_exists($this->getClass(), 'groups')) {
            $listMapper
                ->add('groups');
        }
        $listMapper
            ->add('enabled', null, ['editable' => true])
            ->add('createdAt', null, ['format' => 'd/m/Y',]);

        $actions = [
            'edit'        => [],
            'impersonate' => [
                'template' => '@WDUser/admin/CRUD/user/list__action_impersonate.html.twig',
            ],
        ];

        $actions['delete'] = [];

        $listMapper
            ->add(
                ListMapper::NAME_ACTIONS,
                null,
                [
                    'actions' => $actions,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper): void
    {
        $filterMapper
            ->add(
                'email',
                null,
                [
                    'advanced_filter' => false,
                ]
            );

        if (property_exists($this->getClass(), 'groups')) {
            $filterMapper
                ->add(
                    'groups',
                    null,
                    [
                        'advanced_filter' => false,
                    ]
                );
        }

        $filterMapper
            ->add(
                'enabled',
                BooleanFilter::class,
            )
            ->add(
                'search',
                CallbackFilter::class,
                [
                    'callback'        => static function (
                        ProxyQueryInterface $query,
                        string              $alias,
                        string              $field,
                        FilterData          $data
                    ): bool {
                        if (!$data->getValue()) {
                            return false;
                        }
                        $query
                            ->andWhere(
                                '(' . $alias . '.email like :email or ' . $alias . '.username like :username or ' . $alias . '.firstname = :firstname or ' . $alias . '.lastname = :lastname)'
                            )
                            ->setParameter('email', '%' . $data->getValue() . '%')
                            ->setParameter('username', '%' . $data->getValue() . '%')
                            ->setParameter('firstname', $data->getValue())
                            ->setParameter('lastname', $data->getValue());

                        return true;
                    },
                    'field_type'      => TextType::class,
                    'field_options'   => [
                        'attr' => [
                            'placeholder' => 'Recherche par nom, prénom ou email',

                        ],
                    ],
                    'advanced_filter' => false,
                    'show_filter'     => true,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->tab('Utilisateur')
            ->with('General', ['class' => 'col-md-6'])->end()
            ->with('Profil', ['class' => 'col-md-6'])->end()
            ->end()
            ->tab('Sécurité');
        if (property_exists($this->getSubject(), 'groups')) {
            $formMapper
                ->with('Groupes', ['class' => 'col-md-8'])->end();
        }
        $formMapper
            ->with('Statut', ['class' => 'col-md-4'])->end()
            ->with('Permissions individuelles', ['class' => 'col-md-4'])->end();
        $formMapper
            ->end();

        $formMapper
            ->tab('Utilisateur')
            ->with('General')
            ->add('username')
            ->add('email')
            ->add(
                'plainPassword',
                PasswordType::class,
                [
                    'use_strength' => true,
                    'sonata_admin' => true,
                    'required'     => false,
                ]
            )
            ->end()
            ->with('Profil')
            ->add('firstname', null, ['required' => false])
            ->add('lastname', null, ['required' => false])
            ->end()
            ->end()
            ->tab('Sécurité')
            ->with('Statut')
            ->add('enabled', null, ['required' => false])
            ->end();

        if (property_exists($this->getSubject(), 'groups')) {
            $formMapper
                ->with('Groupes')
                ->add(
                    'groups',
                    ModelType::class,
                    [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => true,
                    ]
                )
                ->end();
        }

        $formMapper
            ->with('Permissions individuelles')
            ->add(
                'permissions',
                CollectionType::class,
                [
                    'label'        => false,
                    //                    'expanded' => true,
                    //                    'multiple' => true,
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'required'     => false,
                ]
            )
            ->end()
            ->end();

        $formMapper->getFormBuilder()->addEventListener(
            FormEvents::SUBMIT,
            function (SubmitEvent $event) {
                $user = $event->getData();
                if ($user->getPlainPassword()) {
                    $encoded = $this->userPasswordHasher
                        ->hashPassword($user, $user->getPlainPassword());

                    $user->setPassword($encoded);
                    $user->setLastUpdatePassword(new \DateTime('now'));
                    $event->setData($user);
                }
            }
        );
    }

    public function getExportFormats(): array
    {
        return ['csv', 'xls'];
    }
}
