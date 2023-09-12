<?php

namespace App\Admin;

use App\Entity\Association;
use App\Entity\Centre;
use App\Entity\CreatorCentre;
use App\EventSubscriber\AssociationCreationSubscriber;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\DoctrineORMAdminBundle\Filter\StringFilter;
use Sonata\Form\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CentreAdmin extends AbstractAdmin
{
    private EntityManagerInterface $entityManager;
    private UserManager $userManager;
    protected array $formOptions = [
        'validation_groups' => ['centre'],
    ];

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    /**
     * @param Centre $object
     */
    public function preUpdate($object = null): void
    {
        if (!$object->getAdresse()->getNom()) {
            $object->setAdresse();
        }
        parent::preUpdate($object);
    }

    /**
     * @param Centre $object
     */
    public function preRemove($object): void
    {
        $creators = $this->entityManager->getRepository(CreatorCentre::class)->findBy(['entity' => $object->getId()]);
        foreach ($creators as $creator) {
            $this->entityManager->remove($creator);
        }
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setUserManager(UserManager $userManager): void
    {
        $this->userManager = $userManager;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations')
            ->add('id', null, [
                'attr' => [
                    'read_only' => true,
                ],
                'disabled' => true,
            ])
            ->add('nom')
            ->add('region', ChoiceType::class, [
                'label' => 'Région',
                'choices' => array_combine(Centre::REGIONS, Centre::REGIONS),
                'required' => false,
            ])
            ->add('adresse', AdminType::class, [
                'btn_add' => false,
                'btn_delete' => false,
                'required' => false,
            ])
            ->add('test', CheckboxType::class, [
                'label' => 'Compte test',
                'required' => false,
            ])
            ->end();

        $subject = $this->getSubject();
        if (null === $subject->getId()) {
            $form
                ->with('Association *')
                ->add('association', EntityType::class, [
                    'class' => Association::class,
                    'mapped' => false,
                    'required' => false,
                ])
                ->add('newAssociationName', TextType::class, [
                    'label' => 'Ou nom de la nouvelle association',
                    'mapped' => false,
                    'required' => false,
                ])
                ->end();
        }

        $form
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
                    'admin_code' => 'reo_auth.admin.client_centre',
                ]
            )
            ->end();

        $form->getFormBuilder()->addEventSubscriber(new AssociationCreationSubscriber($this->entityManager, $this->userManager));
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('nom')
            ->add('region', StringFilter::class, [
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => array_combine(Centre::REGIONS, Centre::REGIONS),
                ],
            ])
            ->add('createdAt', null, ['label' => 'Date de création'])
            ->add('test', null, [
                'label' => 'Compte test',
            ])
            ->add('association', null, ['label' => 'Association'])
            ->add('canada', null, ['label' => 'Canada']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('nom', null, ['route' => ['name' => 'edit']])
            ->add('association', null, ['label' => 'Association'])
            ->add('createdAt', null, ['label' => 'Date de création'])
            ->add('canada', null, ['label' => 'Canada'])
            ->add('_action', 'actions', [
                'actions' => [
                    'edit' => [],
                ],
            ]);
    }

    protected function configureExportFields(): array
    {
        return [
            'id',
            'nom',
            'createdAt',
            'updatedAt',
            'test',
            'region',
        ];
    }
}
