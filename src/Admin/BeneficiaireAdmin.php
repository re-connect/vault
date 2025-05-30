<?php

namespace App\Admin;

use App\Entity\Attributes\Beneficiaire;
use App\Entity\Attributes\BeneficiaireCentre;
use App\Entity\Attributes\Centre;
use App\Entity\Attributes\Client;
use App\Entity\Attributes\CreatorCentre;
use App\Entity\Attributes\CreatorClient;
use App\Entity\Attributes\CreatorUser;
use App\ManagerV2\UserManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\AdminType;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Sonata\Form\Type\CollectionType;
use Sonata\Form\Type\DatePickerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BeneficiaireAdmin extends AbstractAdmin
{
    public function __construct(
        ?string $code = null,
        ?string $class = null,
        ?string $baseControllerName = null,
        private readonly ?RouterInterface $router = null,
        private readonly ?AuthorizationCheckerInterface $authorizationChecker = null,
    ) {
        parent::__construct($code, $class, $baseControllerName);
    }

    #[\Override]
    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = ['password-admin', 'beneficiaire', 'Default'];
        parent::configureFormOptions($formOptions);
    }
    private UserManager $userManager;

    #[\Override]
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    /**
     * @param Beneficiaire $object
     */
    #[\Override]
    public function preUpdate($object): void
    {
        /* @var BeneficiaireCentre $beneficiaireCentre */
        foreach ($object->getExternalLinks() as $externalLink) {
            if (null !== $externalLink->getBeneficiaireCentre()) {
                $beneficiaireCentre = $object->getBeneficiairesCentres()->filter(static fn (BeneficiaireCentre $element) => $element->getId() === $externalLink->getBeneficiaireCentre()->getId())->first();
                if (false !== $beneficiaireCentre && null === $beneficiaireCentre->getExternalLink()) {
                    $externalLink->setBeneficiaireCentre(null);
                }
            }
        }

        if ($object->getUser()->getPlainPassword()) {
            $this->userManager->updatePasswordWithPlain($object->getUser());
        }
    }

    public function setUserManager(UserManager $userManager): void
    {
        $this->userManager = $userManager;
    }

    #[\Override]
    protected function prePersist(object $object): void
    {
        $this->userManager->updatePasswordWithPlain($object->getUser());

        parent::prePersist($object);
    }

    #[\Override]
    protected function configureFormFields(FormMapper $formMapper): void
    {
        /** @var ?Beneficiaire $subject */
        $subject = $this->getSubject();

        $description = '';
        if (null !== $subject && null !== $subject->getId() && null !== $user = $subject->getUser()) {
            $descriptionCreatorCentre = (!$creatorCentre = $user->getCreatorCentre()) ? '' : '<h4>Créé par (centre)</h4><p>'.$creatorCentre->getEntity()->__toString().'</p>';
            $descriptionCreatorClient = (!$creatorClient = $user->getCreatorClient()) ? '' : '<h4>Créé par (client)</h4><p>'.$creatorClient->getEntity()->__toString().'</p>';
            $descriptionCreatorUser = (!$creatorUser = $user->getCreatorUser()) ? '' : '<h4>Créé par (utilisateur)</h4><p>'.$creatorUser->getEntity()->toSonataString().'</p>';

            $description = $descriptionCreatorCentre.$descriptionCreatorClient.$descriptionCreatorUser;
        }

        $formMapper
            ->with('Informations', ['description' => $description])
            ->add('id', null, [
                'attr' => ['read_only' => true],
                'disabled' => true,
            ])
            ->add(
                'user',
                AdminType::class,
                ['label' => false, 'btn_add' => false, 'btn_delete' => false],
                ['admin_code' => 'sonata.admin.user_simple']
            )
            ->add('dateNaissance', BirthdayType::class, [
                'label' => 'Date de naissance',
                'years' => range(1900, date('Y')), ])
            ->add('questionSecrete', null, [
                'label' => 'Question secrète',
                'disabled' => true,
                'required' => false,
            ])
            ->add('reponseSecrete', null, [
                'label' => 'Réponse secrète',
                'required' => false,
            ]);
        if ($this->isCurrentRoute('edit')) {
            $formMapper
                ->add('user.personalAccountDataRequestedAt', DatePickerType::class, [
                    'label' => 'Demande de récupération des données',
                    'required' => false,
                    'help' => 'Date à laquelle la demande a été faite. Lorsque cette demande a été traitée, veuillez effacer cette date.',
                ]);
        }
        if ($subject && $subject->getCreationProcess()) {
            $formMapper
                ->add('isCreating', CheckboxType::class, [
                    'label' => 'En cours de création',
                    'required' => false,
                    'property_path' => 'creationProcess.isCreating',
                ]);
        }
        $formMapper
            ->end()
            ->with('Centres')
            ->add(
                'beneficiairesCentres',
                CollectionType::class,
                [
                    'label' => false,
                    'required' => false,
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                    'sortable' => 'position',
                ]
            )
            ->end()
            ->with('Liaisons externe')
            ->add(
                'externalLinks',
                CollectionType::class,
                [
                    'label' => false,
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                    'admin_code' => 'reo_auth.admin.client_beneficiaire',
                ]
            )
            ->end();

        if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $formMapper
                ->with('Exporter les données du compte')
                ->add('exportBeneficiaryData', null, [
                    'mapped' => false,
                    'label' => "L'export comprend l'ensemble des notes, contacts, et événements au format csv, ainsi que la totalité des documents et dossiers",
                    'required' => false,
                    'disabled' => true,
                    'help' => $this->getExportButton(),
                    'help_html' => true,
                    'attr' => ['read_only' => true, 'style' => 'display:none'],
                ])
                ->end();
        }
    }

    #[\Override]
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('region', CallbackFilter::class, [
                'label' => 'Région (centre - lié à)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->innerJoin($alias.'.beneficiairesCentres', 'bc1')
                        ->innerJoin('bc1.centre', 'c1')
                        ->innerJoin('c1.region', 'r')
                        ->andWhere('r.name IN (:regions)')
                        ->setParameter('regions', $value);

                    return true;
                },
                'field_type' => ChoiceType::class,
                'field_options' => [
                    'choices' => array_combine(Centre::REGIONS, Centre::REGIONS),
                    'multiple' => true,
                ],
            ])
            ->add('user.id', null, ['label' => 'User id'])
            ->add('user.username', null, ['label' => "Nom d'utilisateur"])
            ->add('user.nom', null, ['label' => 'Nom'])
            ->add('user.prenom', null, ['label' => 'Prénom'])
            ->add('user.telephone', null, ['label' => 'Téléphone portable'])
            ->add('centre', CallbackFilter::class, [
                'label' => 'Lié à (centre)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->innerJoin($alias.'.beneficiairesCentres', 'bc2')
                        ->innerJoin('bc2.centre', 'c2')
                        ->andWhere('c2.id IN (:c)')
                        ->setParameter('c', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => [
                    'class' => Centre::class, 'multiple' => true,
                ],
            ])
            ->add('externalLinks', CallbackFilter::class, [
                'label' => 'Lié à (client)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->join($alias.'.externalLinks', 'external_link')
                        ->join('external_link.client', 'client')
                        ->andWhere('client.id IN (:client)')
                        ->setParameter('client', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => ['class' => Client::class, 'multiple' => true],
            ])
            ->add('user.creatorUser', CallbackFilter::class, [
                'label' => 'Créé par (utilisateur)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->join(CreatorUser::class, 'creator_user', 'WITH', $alias.'.id = creator_user.user')
                        ->join('creator_user.entity', 'u')
                        ->andWhere('u.username like :creatorUserName')
                        ->orWhere('u.nom like :creatorUserName')
                        ->orWhere('u.prenom like :creatorUserName')
                        ->setParameter('creatorUserName', '%'.$value.'%');

                    return true;
                },
                'field_type' => TextType::class,
            ])
            ->add('user.creatorCentre', CallbackFilter::class, [
                'label' => 'Créé par (centre)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->join(CreatorCentre::class, 'creator_centre', 'WITH', $alias.'.id = creator_centre.user')
                        ->join('creator_centre.entity', 'centre')
                        ->andWhere('centre IN (:centreName)')
                        ->setParameter('centreName', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => [
                    'class' => Centre::class, 'multiple' => true,
                ],
            ])
            ->add('user.creatorClient', CallbackFilter::class, [
                'label' => 'Créé par (client)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->join(CreatorClient::class, 'creator_client', 'WITH', $alias.'.id = creator_client.user')
                        ->join('creator_client.entity', 'client')
                        ->andWhere('client IN (:creatorClientName)')
                        ->setParameter('creatorClientName', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => ['class' => Client::class, 'multiple' => true],
            ])
            ->add('user.lastLogin', DateRangeFilter::class, ['label' => 'Dernière connexion'])
            ->add('createdAt', DateRangeFilter::class, ['label' => 'Créé le'])
            ->add('user.test', null, [
                'label' => 'Compte test',
            ])
            ->add('user.email', null, ['label' => 'Email'])
            ->add('user.canada', null, ['label' => 'Canada']);
    }

    #[\Override]
    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('user.username', null, ['label' => "Nom d'utilisateur", 'route' => ['name' => 'edit']])
            ->add('user.lastLogin', null, ['label' => 'Dernière connexion'])
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
            ->add('user.cgsAcceptedAt', null, ['label' => 'Acceptation CGS'])
            ->add('user.canada', null, ['label' => 'Canada'])
            ->add('_action', 'actions', [
                'actions' => [
                    'edit' => [],
                ],
            ]);
    }

    private function getExportButton(): string
    {
        /** @var Beneficiaire $subject */
        $subject = $this->getSubject();
        if (!$subject?->getId()) {
            return '';
        }

        $exportUrl = $this->router->generate('beneficiary_export', ['id' => $subject->getId()]);

        return sprintf('<a class="btn btn-success" href="%s">Exporter</a>', $exportUrl);
    }

    #[\Override]
    public function configureExportFields(): array
    {
        return [
            'id' => 'id',
            'Email' => 'user.email',
            'Télephone' => 'user.telephone',
            'Date de naissance' => 'dateNaissanceStr',
            'Question secrète' => 'questionSecrete',
            'Réponse secrète' => 'reponseSecrete',
            'En cours de création' => 'isCreatingToString',
            'Dernière Connexion' => 'user.lastLoginToString',
            'Création' => 'user.created_at',
            'Centres' => 'getBeneficiairesCentresStr',
            'Créé par (user)' => 'user.creatorUser',
            'Créé par (centre)' => 'user.creatorCentre',
            'Créé par (client)' => 'user.creatorClient',
            'Compte test' => 'user.testToString',
            'Nombre de documents' => 'documentsCount',
            "Nombre d'événements" => 'eventsCount',
            'Nombre de contacts' => 'contactsCount',
            'Nombre de notes' => 'notesCount',
            'Région' => 'getRegionToString',
        ];
    }
}
