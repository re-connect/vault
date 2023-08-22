<?php

namespace App\Admin;

use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\CreatorCentre;
use App\Entity\CreatorClient;
use App\Entity\CreatorUser;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ContactAdmin extends AbstractAdmin
{
    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->with('Informations')
            ->add('id', null, ['read_only' => true, 'disabled' => true])
            ->add('nom')
            ->add('telephone')
            ->add('email')
            ->add('commentaire')
            ->add('beneficiaire.user.username', null, ['read_only' => true, 'disabled' => true])
            ->add('beneficiaire.user.id', null, ['read_only' => true, 'disabled' => true])
            ->end();
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('beneficiaire.user.username', null, ['label' => "Nom d'utilisateur"])
            ->add('beneficiaire.id', null, ['label' => 'Bénéficiaire (id)'])
            ->add('beneficiaire.user.id', null, ['label' => 'Utilisateur (id)'])
            ->add('nom', null, ['label' => 'Nom du document'])
            ->add('region', CallbackFilter::class, [
                'label' => 'Région (centre - lié à)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->innerJoin($alias.'.beneficiaire', 'b')
                        ->innerJoin('b.beneficiairesCentres', 'bc')
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
            ->add('creatorUser', CallbackFilter::class, [
                'label' => 'Créé par (utilisateur)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();
                    $query
                        ->join(CreatorUser::class, 'creator_user', 'WITH', $alias.'.id = creator_user.contact')
                        ->join('creator_user.entity', 'u')
                        ->where('u.username like :value')
                        ->orWhere('u.nom like :value')
                        ->orWhere('u.prenom like :value')
                        ->setParameter('value', '%'.$value.'%');

                    return true;
                },
                'field_type' => TextType::class,
            ])
            ->add('creatorCentre', CallbackFilter::class, [
                'label' => 'Créé par (centre)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();
                    $query
                        ->join(CreatorCentre::class, 'creator_centre', 'WITH', $alias.'.id = creator_centre.contact')
                        ->join('creator_centre.entity', 'centre')
                        ->where('centre IN (:value)')
                        ->setParameter('value', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => ['class' => Centre::class, 'multiple' => true],
            ])
            ->add('creatorClient', CallbackFilter::class, [
                'label' => 'Créé par (client)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();
                    $query
                        ->join(CreatorClient::class, 'creator_client', 'WITH', $alias.'.id = creator_client.contact')
                        ->join('creator_client.entity', 'client')
                        ->where('client IN (:value)')
                        ->setParameter('value', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => ['class' => Client::class, 'multiple' => true],
            ])
            ->add('createdAt', null, ['label' => 'Créé le'])
            ->add('beneficiaire.user.canada', null, ['label' => 'Canada']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->add('beneficiaire', null, ['label' => 'Bénéficiaire'])
            ->add('nom')
            ->add('creatorUser', null, [
                'label' => 'Créé par (utilisateur)',
            ])
            ->add('creatorCentre', null, [
                'label' => 'Créé par (centre)',
            ])
            ->add('creatorClient', null, [
                'label' => 'Créé par (client)',
            ])
            ->add('createdAt', null, ['label' => 'Créé le'])
            ->add('isPrivate', null, ['label' => 'Accès'])
            ->add('beneficiaire.user.canada', null, ['label' => 'Canada']);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
    }

    public function configureExportFields(): array
    {
        return [
            'Id' => 'id',
            'Bénéficiaire' => 'beneficiaire.user.username',
            'Nom' => 'nom',
            'Prénom' => 'prenom',
            'Téléphone' => 'telephone',
            'Courriel' => 'email',
            'Commentaire' => 'commentaire',
            'Association' => 'association',
            'Créé le' => 'created_at',
            'Modifié le' => 'updated_at',
            'Accès' => 'isPrivate',
            'Créé par (user)' => 'creatorUser',
            'Créé par (centre)' => 'creatorCentre',
            'Créé par (client)' => 'creatorClient',
        ];
    }
}
