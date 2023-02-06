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
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EvenementAdmin extends AbstractAdmin
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
            ->add('id', null, ['attr' => ['read_only' => true], 'disabled' => true])
            ->add('nom')
            ->add('date', 'datetime')
            ->add('lieu')
            ->add('commentaire')
            ->add('bEnvoye', null, ['label' => 'Déjà envoyé', 'required' => false])
            ->add('heureRappel', null, ['label' => "Nombre d'heures avant le rappel"])
            ->add('beneficiaire.user.username', null, ['attr' => ['read_only' => true], 'disabled' => true])
            ->add('beneficiaire.user.id', null, ['attr' => ['read_only' => true], 'disabled' => true])
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
            ->add('creatorUser', CallbackFilter::class, [
                'label' => 'Créé par (utilisateur)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();
                    $query
                        ->join(CreatorUser::class, 'creator_user', 'WITH', $alias.'.id = creator_user.evenement')
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
                        ->join(CreatorCentre::class, 'creator_centre', 'WITH', $alias.'.id = creator_centre.evenement')
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
                        ->join(CreatorClient::class, 'creator_client', 'WITH', $alias.'.id = creator_client.evenement')
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
            'Date' => 'date',
            'Lieu' => 'lieu',
            'Commentaire' => 'commentaire',
            'Envoyé' => 'bEnvoyeToString',
            'Créé le' => 'created_at',
            'Modifié le' => 'updated_at',
            'Accès' => 'isPrivate',
            'Créé par (user)' => 'creatorUser',
            'Créé par (centre)' => 'creatorCentre',
            'Créé par (client)' => 'creatorClient',
        ];
    }
}
