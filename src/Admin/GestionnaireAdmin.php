<?php

namespace App\Admin;

use App\Entity\CreatorUser;
use App\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\Form\Type\CollectionType;

class GestionnaireAdmin extends AbstractAdmin
{
    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
        $collection->remove('edit');
    }

    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = ['password-membre', 'gestionnaire'];
        parent::configureFormOptions($formOptions);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    private UserManager $userManager;
    private EntityManagerInterface $entityManager;

    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function preUpdate($object): void
    {
        if ($object->getUser()->getPlainPassword()) {
            $this->userManager->updatePassword($object->getUser());
        }
    }

    public function preRemove($object): void
    {
        $creators = $this->entityManager->getRepository(CreatorUser::class)->findBy(['entity' => $object->getUser()->getId()]);
        foreach ($creators as $creator) {
            $this->entityManager->remove($creator);
        }
    }

    protected function prePersist(object $object): void
    {
        $this->userManager->updatePassword($object->getUser());

        parent::prePersist($object);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations')
            ->add('id', null, ['attr' => [
                'read_only' => true,
            ], 'disabled' => true])
            ->add('user', AdminType::class, [
                'btn_add' => false, 'btn_delete' => false, ], [
                'admin_code' => 'sonata.admin.user_simple',
                'validation_groups' => [null],
            ])
            ->add('association')
            ->add('centres')
            ->end()
            ->with('Liaisons externe')
            ->add(
                'externalLinks',
                CollectionType::class,
                [
                    'label' => 'Liaisons externe',
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                    'admin_code' => 'reo_auth.admin.client_gestionnaire',
                ]
            )
            ->end();

        $form->getFormBuilder()->get('user')->get('email')->setRequired(true);
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('user.username', null, ['label' => "Nom d'utilisateur"])
            ->add('user.nom', null, ['label' => 'Nom'])
            ->add('user.prenom', null, ['label' => 'Prénom'])
            ->add('user.derniereConnexionAt', null, ['label' => 'Dernière connexion'])
            ->add('createdAt', null, ['label' => 'Date de création'])
            ->add('user.test', null, [
                'label' => 'Compte test',
            ])
            ->add('user.canada', null, ['label' => 'Canada']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('user.username', null, ['label' => "Nom d'utilisateur", 'route' => ['name' => 'edit']])
            ->add('user.nom', null, ['label' => 'Nom'])
            ->add('user.prenom', null, ['label' => 'Prénom'])
            ->add('user.derniereConnexionAt', null, ['label' => 'Dernière connexion'])
            ->add('createdAt', null, ['label' => 'Date de création'])
            ->add('user.canada', null, ['label' => 'Canada'])
            ->add('_action', 'actions', [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ], ]);
    }

    public function configureExportFields(): array
    {
        return [
            'Id' => 'id',
            'Nom d\'utilisateur' => 'user.username',
            'Nom' => 'user.nom',
            'Prénom' => 'user.prenom',
            'Email' => 'user.email',
            'Téléphone' => 'user.telephone',
            'Centres' => 'getCentresIds',
            'Date de création' => 'user.createdAt',
            'Dernière connexion' => 'user.derniereConnexionAt',
            'Compte test' => 'user.testToString',
            'Association' => 'association.id',
            'User' => 'user.id',
        ];
    }
}
