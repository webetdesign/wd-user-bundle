<?php

declare(strict_types=1);

namespace WebEtDesign\UserBundle\Admin\User;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\BooleanFilter;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\ChoiceFilter;
use Sonata\Exporter\Source\SourceIteratorInterface;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use WebEtDesign\CmsBundle\Form\Type\SecurityRolesType;

class UserAdmin extends AbstractAdmin
{
    private ?UserPasswordHasherInterface $userPasswordHasher = null;

    public function __construct(
        ?string $code = null,
        ?string $class = null,
        ?string $baseControllerName = null
    ) {
        parent::__construct($code, $class, $baseControllerName);
    }

    /**
     * @param UserPasswordHasherInterface|null $userPasswordHasher
     * @return UserAdmin
     */
    public function setUserPasswordHasher(?UserPasswordHasherInterface $userPasswordHasher
    ): UserAdmin {
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
                        string $alias,
                        string $field,
                        array $data
                    ): bool {
                        if (!$data['value']) {
                            return false;
                        }
                        $query
                            ->andWhere(
                                '(' . $alias . '.email like :email or ' . $alias . '.firstname = :firstname or ' . $alias . '.lastname = :lastname)'
                            )
                            ->setParameter('email', '%' . $data['value'] . '%')
                            ->setParameter('firstname', $data['value'])
                            ->setParameter('lastname', $data['value']);

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
            ->tab('Sécurité')
            ->with('Permissions individuelles', ['class' => 'col-md-8'])->end()
            ->with('Statut', ['class' => 'col-md-4'])->end();
        if (property_exists($this->getSubject(), 'groups')) {
            $formMapper
                ->with('Groupes', ['class' => 'col-md-4'])->end();
        }
        $formMapper
            ->end();

        $formMapper
            ->tab('Utilisateur')
            ->with('General')
            ->add('username')
            ->add('email')
            ->add(
                'plainPassword',
                TextType::class,
                [
                    'required' => (!$this->getSubject() || null === $this->getSubject()->getId()),
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
                SecurityRolesType::class,
                [
                    'label'    => false,
                    'expanded' => true,
                    'multiple' => true,
                    'required' => false,
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
