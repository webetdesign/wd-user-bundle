<?php

declare(strict_types=1);

namespace WebEtDesign\UserBundle\Admin\User;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use WebEtDesign\UserBundle\Form\Type\SecurityRolesType;

class GroupAdmin extends AbstractAdmin
{
    protected array $formOptions = [
        'validation_groups' => 'Registration',
    ];

    protected function createNewInstance(): object
    {
        $class = $this->getClass();

        return new $class('', []);
    }


    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Général', ['class' => 'col-md-3'])
            ->add('name')
            ->end()
            ->with('Configuration des permissions', ['class' => 'col-md-9'])
            ->add('permissions', SecurityRolesType::class, [
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->end();
    }
    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        unset($this->getListModes()['mosaic']);

        $list
            ->addIdentifier('name')
            ->add('permissions', null, [
                'template' => '@WDUser/admin/CRUD/group/permissions_list_field.html.twig'
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit'   => [],
                    'delete' => [],
                ],
            ]);
    }


    protected function configureRoutes(RouteCollection|RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
        $collection->remove('show');
    }
}
