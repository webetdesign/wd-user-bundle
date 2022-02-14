<?php

declare(strict_types=1);

namespace WebEtDesign\UserBundle\Admin\User;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use WebEtDesign\CmsBundle\Form\Type\SecurityRolesType;

class UserAdmin extends AbstractAdmin
{
    protected string $translationDomain = 'UserAdmin';

    private UserPasswordHasherInterface $userPasswordEncoder;

    /**
     * UserAdmin constructor.
     * @param $code
     * @param $name
     * @param null $controller
     * @param UserPasswordHasherInterface $userPasswordEncoder
     */
    public function __construct(
        $code,
        $name,
        $controller = null,
        UserPasswordHasherInterface $userPasswordEncoder
    ) {
        parent::__construct($code, $name, $controller);
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        unset($this->getListModes()['mosaic']);

        $list
            ->addIdentifier('username')
            ->add('email')
            ->add('groups')
            ->add('enabled', null, ['editable' => true])
            ->add('createdAt', null, ['format' => 'd/m/Y',]);

        $actions = [
            'edit'        => [],
            'impersonate' => [
                'template' => '@WDUser/admin/CRUD/user/list__action_impersonate.html.twig',
            ],
        ];

        $actions['delete'] = [];

        $list
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
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add(
                'email',
                null,
                [
                    'advanced_filter' => false,
                ]
            );

        $filter
            ->add(
                'groups',
                null,
                [
                    'advanced_filter' => false,
                ]
            );

        $filter
            ->add(
                'enabled',
                null,
                [
                    'choices' => [
                        'Oui' => true,
                        'Non' => false,
                    ],
                ]
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
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->tab('Utilisateur')
            ->with('General', ['class' => 'col-md-6'])->end()
            ->with('Profil', ['class' => 'col-md-6'])->end()
            ->end()
            ->tab('Sécurité')
            ->with('Permissions individuelles', ['class' => 'col-md-8'])->end()
            ->with('Statut', ['class' => 'col-md-4'])->end();
        if (property_exists($this->getSubject(), 'groups')) {
            $form
                ->with('Groupes', ['class' => 'col-md-4'])->end();
        }
        $form
            ->end();

        $form
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
            $form
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


        $form
            ->with('Permissions individuelles')
            ->add(
                'roles',
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

        $form->getFormBuilder()->addEventListener(
            FormEvents::SUBMIT,
            function (SubmitEvent $event) {
                $user = $event->getData();
                if ($user->getPlainPassword()) {
                    $encoded = $this->userPasswordEncoder->hashPassword($user,
                        $user->getPlainPassword());

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
