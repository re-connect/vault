<?php

namespace App\Form\Type;

use App\Entity\Centre;
use App\Entity\PrixCentre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CentreType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prixCentres = $this->em->getRepository(PrixCentre::class)->findAll();
        $arPrix = [];
        foreach ($prixCentres as $prixCentre) {
            $arPrix[$prixCentre->getBudget()] = $this->translator->trans('devenirUnRelaiReconnect.accueil.budget'.$prixCentre->getBudget());
        }

        $builder
            ->add('nom', null, ['label' => 'name'])
            ->add('siret', null, ['label' => 'devenirUnRelaiReconnect.inscriptionCentres.siretLabel'])
            ->add('finess', null, ['label' => 'devenirUnRelaiReconnect.inscriptionCentres.finessLabel'])
            ->add('telephone', null, ['label' => 'devenirUnRelaiReconnect.inscriptionCentres.telephoneLabel'])
            ->add('typeCentre', null, ['label' => 'devenirUnRelaiReconnect.inscriptionCentres.typeCentreLabel'])
            ->add('budgetAnnuel', ChoiceType::class, ['label' => 'devenirUnRelaiReconnect.inscriptionCentres.budgetAnnuelLabel', 'choices' => $arPrix]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Centre::class,
            'validation_groups' => ['centre'],
        ]);
    }

    public function getName(): string
    {
        return 're_form_centre';
    }
}
