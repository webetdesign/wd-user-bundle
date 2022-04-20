<?php

declare(strict_types=1);

namespace WebEtDesign\UserBundle\Admin\User;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use WebEtDesign\CmsBundle\Form\Type\SecurityRolesType;

class GroupAdmin extends AbstractAdmin
{
    protected string $translationDomain = 'GroupAdmin';

    /**
     * {@inheritdoc}
     */
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
            ->tab('Informations')
            ->with('General', ['class' => 'col-md-6'])
            ->add('name')
            ->end()
            ->end()
            ->tab('Sécurité')
            ->with('Roles', ['class' => 'col-md-12'])
            ->add('permissions', SecurityRolesType::class, [
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
            ->end()
            ->end()
        ;
    }
    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $list): void
    {
        unset($this->getListModes()['mosaic']);

        $list
            ->addIdentifier('name')
            ->add('permissions');
    }


    protected function configureRoutes(RouteCollection|RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
        $collection->remove('show');
    }
}
