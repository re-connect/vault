<?php

namespace App\Admin;

use App\Entity\Attributes\BeneficiaireCentre;
use App\Entity\Attributes\ClientBeneficiaire;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class BeneficiaireCentreAdmin extends AbstractAdmin
{
    #[\Override]
    protected function configureFormFields(FormMapper $form): void
    {
        /** @var BeneficiaireCentre $beneficiaireCentre */
        $beneficiaireCentre = $this->getSubject();
        $beneficiaireId = $beneficiaireCentre->getBeneficiaire()?->getId();

        $form
            ->add('centre', ModelType::class, [
                'label' => 'Centre',
                'btn_add' => false,
                'btn_delete' => false,
            ])
            ->add('externalLink', EntityType::class, [
                'label' => 'Liaison externe',
                'class' => ClientBeneficiaire::class,
                'query_builder' => static fn (EntityRepository $er) => $er->createQueryBuilder('cb')
                    ->andWhere('cb.entity = :beneficiaireId')
                    ->andWhere('cb.entity_name = :entityName')
                    ->setParameters([
                        'beneficiaireId' => $beneficiaireId,
                        'entityName' => 'ClientBeneficiaire',
                    ]),
                'required' => false,
            ])
            ->add('bValid', null, ['label' => 'Accepté']);
    }
}
