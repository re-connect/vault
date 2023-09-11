<?php

namespace App\Admin;

use App\Entity\Centre;
use App\Entity\CreatorUser;
use App\ManagerV2\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\Form\Type\CollectionType;
use Sonata\Form\Type\DateTimePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class MembreAdmin extends AbstractAdmin
{
    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = ['password-membre', 'membre', 'Default'];
        parent::configureFormOptions($formOptions);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    private EntityManagerInterface $entityManager;
    private UserManager $userManager;

    public function preUpdate($object): void
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('mc')
            ->from('App:MembreCentre', 'mc')
            ->innerJoin('mc.membre', 'm')
            ->where('m.id = '.$object->getId())
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();
        foreach ($result as $membreCentre) {
            if (!$object->getMembresCentres()->contains($membreCentre)) {
                $this->entityManager->remove($membreCentre);
            }
        }

        foreach ($object->getMembresCentres() as $bc) {
            if (null === $bc->getMembre()) {
                $bc->setMembre($object);
            }
        }
        if ($object->getUser()->getPlainPassword()) {
            $this->userManager->updatePasswordWithPlain($object->getUser());
        }
        parent::preUpdate($object);
    }

    protected function prePersist(object $object): void
    {
        $this->userManager->updatePasswordWithPlain($object->getUser());

        parent::prePersist($object);
    }

    public function preRemove($object): void
    {
        $creators = $this->entityManager->getRepository(CreatorUser::class)->findBy(['entity' => $object->getUser()->getId()]);
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
            ->add('user', AdminType::class, [
                'btn_add' => false, 'btn_delete' => false, ], [
                'admin_code' => 'sonata.admin.user_simple',
                'admin_code_external_links' => 'reo_auth.admin.client_membre',
            ])
            ->end();

        if ($this->isCurrentRoute('edit')) {
            $form
                ->with('Statut du membre')
                ->add('user.enabled', ChoiceType::class, [
                    'label' => 'enabled',
                    'choices' => [
                        'yes' => true,
                        'no' => false,
                    ],
                ])
                ->add('user.disabledBy', null, [
                    'attr' => [
                        'read_only' => true,
                    ],
                    'disabled' => true,
                    'label' => 'disabledBy',
                ],
                    [
                        'admin_code' => 'sonata.admin.user_simple',
                    ])
                ->add('user.disabledAt', DateTimePickerType::class, [
                    'attr' => [
                        'read_only' => true,
                    ],
                    'disabled' => true,
                    'label' => 'disabledAt',
                    ],
                    [
                        'admin_code' => 'sonata.admin.user_simple',
                    ])
                ->end();
        }
        $form
            ->with('Centres')
            ->add('membresCentres', CollectionType::class, [
                        'by_reference' => false,
                        'label' => 'Centres',
                        'required' => false,
                    ], [
                        'edit' => 'inline',
                        'inline' => 'table',
                        'sortable' => 'position',
                    ])
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
                    'admin_code' => 'reo_auth.admin.client_membre',
                ]
            )
            ->end();

        $form->getFormBuilder()->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();

            if ($this->isCurrentRoute('create') && !$form->get('user')->get('plainPassword')->getData()) {
                $form->addError(new FormError('Le mot de passe ne doit pas être vide'));
            }
        });
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('user.username', null, ['label' => "Nom d'utilisateur"])
            ->add('user.nom', null, ['label' => 'Nom'])
            ->add('user.prenom', null, ['label' => 'Prénom'])
            ->add('user.telephone', null, ['label' => 'Téléphone portable'])
            ->add('createdAt', null, ['label' => 'Dernière connexion'])
            ->add('user.test', null, [
                'label' => 'Compte test',
            ])
            ->add('centre', CallbackFilter::class, [
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->innerJoin($alias.'.membresCentres', 'bc')
                        ->innerJoin('bc.centre', 'c')
                        ->andWhere('c.id = :c')
                        ->setParameter('c', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => ['class' => Centre::class],
            ])
            ->add('user.email', null, ['label' => 'Email'])
            ->add('region', CallbackFilter::class, [
                'label' => 'Région',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->innerJoin($alias.'.membresCentres', 'mc')
                        ->innerJoin('mc.centre', 'c')
                        ->andWhere('c.region IN (:regions)')
                        ->setParameter('regions', $value);

                    return true;
                },
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => array_combine(Centre::REGIONS, Centre::REGIONS),
                    'multiple' => true,
                ],
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
            ->add('user.creatorUser', null, [
                'label' => 'Créé par (utilisateur)',
            ])
            ->add('user.creatorCentre', null, [
                'label' => 'Créé par (centre)',
            ])
            ->add('user.creatorClient', null, [
                'label' => 'Créé par (client)',
            ])
            ->add('createdAt', null, ['label' => 'Date de création'])
            ->add('user.enabled', null, ['label' => 'enabled'])
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
            'Centres' => 'getCentresToString',
            'Date de création' => 'user.createdAt',
            'Dernière connexion' => 'user.derniereConnexionAt',
            'Compte test' => 'user.testToString',
            'Region' => 'getRegionToString',
        ];
    }
}
