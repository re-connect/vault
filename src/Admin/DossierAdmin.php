<?php

namespace App\Admin;

use App\Entity\Centre;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class DossierAdmin extends AbstractAdmin
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
            ->add('beneficiaire.user.username', null, ['attr' => ['read_only' => true], 'disabled' => true])
            ->add('beneficiaire.user.id', null, ['attr' => ['read_only' => true], 'disabled' => true])
            ->end();
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('beneficiaire.user.username')
            ->add('beneficiaire.id')
            ->add('beneficiaire.user.id')
            ->add('nom')
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
            ->add('createdAt')
            ->add('beneficiaire.user.canada', null, ['label' => 'Canada']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('beneficiaire', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('nom', null, ['route' => ['name' => 'edit']])
            ->add('isPrivate', null, ['label' => 'Accès'])
            ->addIdentifier('createdAt', null, ['route' => ['name' => 'edit']])
            ->add('dossierParent.id', null, ['label' => 'Id dossier parent'])
            ->add('beneficiaire.user.canada', null, ['label' => 'Canada']);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
    }
}
