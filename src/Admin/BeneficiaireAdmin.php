<?php

namespace App\Admin;

use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\CreatorCentre;
use App\Entity\CreatorClient;
use App\Entity\CreatorUser;
use App\Manager\UserManager;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BeneficiaireAdmin extends AbstractAdmin
{
    protected function configureFormOptions(array &$formOptions): void
    {
        $formOptions['validation_groups'] = ['password-beneficiaire', 'beneficiaire'];
        parent::configureFormOptions($formOptions);
    }
    private UserManager $userManager;

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    /**
     * @param Beneficiaire $object
     */
    public function preUpdate($object): void
    {
        /* @var BeneficiaireCentre $beneficiaireCentre */
        foreach ($object->getExternalLinks() as $externalLink) {
            if (null !== $externalLink->getBeneficiaireCentre()) {
                $beneficiaireCentre = $object->getBeneficiairesCentres()->filter(static function (BeneficiaireCentre $element) use ($externalLink) {
                    return $element->getId() === $externalLink->getBeneficiaireCentre()->getId();
                })->first();
                if (false !== $beneficiaireCentre && null === $beneficiaireCentre->getExternalLink()) {
                    $externalLink->setBeneficiaireCentre(null);
                }
            }
        }

        if ($object->getUser()->getPlainPassword()) {
            $this->userManager->updatePassword($object->getUser());
        }
    }

    public function setUserManager(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    protected function prePersist(object $object): void
    {
        $this->userManager->updatePassword($object->getUser());

        parent::prePersist($object);
    }

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
                'required' => false,
            ])
            ->add('reponseSecrete', null, [
                'label' => 'Réponse secrète',
                'required' => false,
            ])
            ->add('isCreating', null, [
                'label' => 'En cours de création',
            ])
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
    }

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
                        ->innerJoin($alias.'.beneficiairesCentres', 'bc')
                        ->innerJoin('bc.centre', 'c')
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
                        ->innerJoin($alias.'.beneficiairesCentres', 'bc')
                        ->innerJoin('bc.centre', 'c')
                        ->andWhere('c.id IN (:c)')
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
                        ->where('client.id IN (:client)')
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
                        ->where('u.username like :value')
                        ->orWhere('u.nom like :value')
                        ->orWhere('u.prenom like :value')
                        ->setParameter('value', '%'.$value.'%');

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
                        ->where('centre IN (:value)')
                        ->setParameter('value', $value);

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
                        ->where('client IN (:value)')
                        ->setParameter('value', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => ['class' => Client::class, 'multiple' => true],
            ])
            ->add('user.derniereConnexionAt', DateRangeFilter::class, ['label' => 'Dernière connexion'])
            ->add('createdAt', DateRangeFilter::class, ['label' => 'Créé le'])
            ->add('user.test', null, [
                'label' => 'Compte test',
            ])
            ->add('user.email', null, ['label' => 'Email'])
            ->add('user.canada', null, ['label' => 'Canada']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('user.username', null, ['label' => "Nom d'utilisateur", 'route' => ['name' => 'edit']])
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
            ->add('user.canada', null, ['label' => 'Canada'])
            ->add('_action', 'actions', [
                'actions' => [
                    'edit' => [],
                ],
            ]);
    }

    public function configureExportFields(): array
    {
        return [
            'id' => 'id',
            "Nom d'utilisateur" => 'user.username',
            'Nom' => 'user.nom',
            'Prénom' => 'user.prenom',
            'Email' => 'user.email',
            'Télephone' => 'user.telephone',
            'Date de naissance' => 'dateNaissanceStr',
            'Question secrète' => 'questionSecrete',
            'Réponse secrète' => 'reponseSecrete',
            'En cours de création' => 'isCreating',
            'Dernière Connexion' => 'user.derniereConnexionAt',
            'Création' => 'user.created_at',
            'Centres' => 'getBeneficiairesCentresStr',
            'Créé par (user)' => 'user.creatorUser',
            'Créé par (centre)' => 'user.creatorCentre',
            'Créé par (client)' => 'user.creatorClient',
            'Compte test' => 'user.testToString',
            'Nombre de documents' => 'nbDocuments',
            'Région' => 'getRegionToString',
        ];
    }
}
