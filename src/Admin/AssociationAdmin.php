<?php

namespace App\Admin;

use App\EventSubscriber\AssociationCreationSubscriber;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class AssociationAdmin extends AbstractAdmin
{
    protected array $formOptions = [
        'validation_groups' => ['association', 'username'],
    ];
    private UserManager $userManager;
    private EntityManagerInterface $em;

    public function setUserManager(UserManager $userManager): void
    {
        $this->userManager = $userManager;
    }

    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;
    }

    protected function prePersist(object $object): void
    {
        $this->userManager->updatePasswordWithPlain($object->getUser());

        parent::prePersist($object);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $formOptions = $this->getSubject()->getId()
            ? ['property_path' => 'user.test']
            : ['mapped' => false];

        $form
            ->with('Informations')
            ->add('id', null, ['attr' => [
                'read_only' => true, ], 'disabled' => true])
            ->add('nom')
            ->add('categorieJuridique')
            ->add('siren')
            ->add('urlSite')
            ->add('test', CheckboxType::class, [
                ...$formOptions,
                'required' => false,
                'label' => 'Compte test',
            ])
            ->end();

        $form->getFormBuilder()->addEventSubscriber(new AssociationCreationSubscriber($this->em, $this->userManager));
    }

    protected function configureDatagridFilters(DatagridMapper $filters): void
    {
        $filters
            ->add('id')
            ->add('nom')
            ->add('user.username', null, ['label' => "Nom d'utilisateur"])
            ->add('createdAt', null, ['label' => 'Date de création'])
            ->add('updatedAt', null, ['label' => 'Modifié le'])
            ->add('user.test', null, [
                'label' => 'Compte test',
            ])
            ->add('user.canada', null, ['label' => 'Canada']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('nom', null, ['label' => 'Nom', 'route' => ['name' => 'edit']])
            ->add('user.username', null, ['label' => "Nom d'utilisateur"])
            ->add('createdAt', null, ['label' => 'Date de création'])
            ->add('user.canada', null, ['label' => 'Canada'])
            ->add('_action', 'actions', [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ], ]);
    }
}
