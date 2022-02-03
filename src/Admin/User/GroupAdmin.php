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
    protected $translationDomain = 'GroupAdmin';

    /**
     * {@inheritdoc}
     */
    protected $formOptions = [
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
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->tab('Informations')
            ->with('General', ['class' => 'col-md-6'])
            ->add('name')
            ->end()
            ->end()
            ->tab('Sécurité')
            ->with('Roles', ['class' => 'col-md-12'])
            ->add('roles', SecurityRolesType::class, [
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
    protected function configureListFields(ListMapper $listMapper): void
    {
        unset($this->getListModes()['mosaic']);

        $listMapper
            ->addIdentifier('name')
            ->add('roles');
    }


    protected function configureRoutes(RouteCollection|RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
        $collection->remove('show');
    }
}
