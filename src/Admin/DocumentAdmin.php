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
use Sonata\DoctrineORMAdminBundle\Filter\DateRangeFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DocumentAdmin extends AbstractAdmin
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
            ->add('beneficiaire.user.username', null, [
                'label' => "Nom d'utilisateur",
                'attr' => ['read_only' => true], 'disabled' => true, ])
            ->add('beneficiaire.id', null, [
                'label' => 'Bénéficiaire (Id)',
                'attr' => ['read_only' => true], 'disabled' => true, ])
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
                'label' => 'Déposé par (utilisateur)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->join(CreatorUser::class, 'creator_user', 'WITH', $alias.'.id = creator_user.document')
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
                'label' => 'Déposé par (centre)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->join(CreatorCentre::class, 'creator_centre', 'WITH', $alias.'.id = creator_centre.document')
                        ->join('creator_centre.entity', 'centre')
                        ->where('centre IN (:value)')
                        ->setParameter('value', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => ['class' => Centre::class, 'multiple' => true],
            ])
            ->add('creatorClient', CallbackFilter::class, [
                'label' => 'Déposé par (client)',
                'callback' => static function (ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool {
                    if (!$data->hasValue()) {
                        return false;
                    }
                    $value = $data->getValue();

                    $query
                        ->join(CreatorClient::class, 'creator_client', 'WITH', $alias.'.id = creator_client.document')
                        ->join('creator_client.entity', 'client')
                        ->where('client IN (:value)')
                        ->setParameter('value', $value);

                    return true;
                },
                'field_type' => EntityType::class,
                'field_options' => ['class' => Client::class, 'multiple' => true],
            ])
            ->add('createdAt', DateRangeFilter::class, ['label' => 'Créé le'])
            ->add('beneficiaire.user.canada', null, ['label' => 'Canada']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('id')
            ->add('beneficiaire', null, ['label' => 'Bénéficiaire'])
            ->add('nom', 'string', [
                'template' => 'admin/list_nom.html.twig',
            ])
            ->add('creatorUser', null, [
                'label' => 'Déposé par (utilisateur)',
            ])
            ->add('creatorCentre', null, [
                'label' => 'Déposé par (centre)',
            ])
            ->add('creatorClient', null, [
                'label' => 'Déposé par (client)',
            ])
            ->add('createdAt', null, ['label' => 'Créé le'])
            ->add('isPrivate', null, ['label' => 'Accès'])
            ->add('dossier.nom', null, ['label' => 'Nom du dossier'])
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
            'Déposé par' => 'deposePar',
            'Créé le' => 'created_at',
            'Modifié le' => 'updated_at',
            'Accès' => 'isPrivate',
            'Nom du dossier' => 'dossier.nom',
            'Déposé par (utilisateur)' => 'creatorUser',
            'Déposé par (centre)' => 'creatorCentre',
            'Déposé par (client)' => 'creatorClient',
        ];
    }
}
