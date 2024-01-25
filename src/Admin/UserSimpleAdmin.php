<?php

namespace App\Admin;

use App\Entity\Annotations\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Sonata\Form\Type\DateTimePickerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserSimpleAdmin extends AbstractAdmin
{
    private EntityManagerInterface $entityManager;

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::PAGE] = 1;
        $sortValues[DatagridInterface::SORT_ORDER] = 'DESC';
        $sortValues[DatagridInterface::SORT_BY] = 'id';
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('nom')
            ->add('prenom', null, ['label' => 'Prénom'])
            ->add('email', null, ['required' => false, 'attr' => ['autocomplete' => 'off']])
            ->add('telephone', null, [
                'label' => 'Numéro de portable (attention il faut mettre un +33)',
                'required' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'required' => false,
                'type' => PasswordType::class,
                'first_options' => ['label' => 'password', 'attr' => ['autocomplete' => 'off']],
                'second_options' => ['label' => 'Confirmer le mot de passe', 'attr' => ['autocomplete' => 'off']],
                'invalid_message' => 'Les mots de passe ne sont pas identiques',
            ])
            ->add('createdAt', DateTimePickerType::class, [
                'label' => 'Date de création',
                'required' => false,
                'attr' => ['read_only' => true],
                'disabled' => true,
            ])
            ->add('derniereConnexionAt', DateTimePickerType::class, [
                'label' => 'Dernière connexion',
                'required' => false,
                'attr' => ['read_only' => true],
                'disabled' => true,
            ])
            ->add('test', CheckboxType::class, [
                'label' => 'Compte test',
                'required' => false,
            ])
            ->add('resetPasswordRequest', null, [
                'mapped' => false,
                'label' => 'Réinitialisation du mot de passe',
                'required' => false,
                'disabled' => true,
                'help' => $this->getResetPasswordText(),
                'help_html' => true,
                'attr' => ['read_only' => true, 'style' => 'display:none'],
            ])
            ->add('mfaEnabled');
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('username')
            ->add('nom')
            ->add('prenom')
            ->add('createdAt');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('nom', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('prenom', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('username', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('createdAt', null, ['route' => ['name' => 'edit']]);
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('create');
    }

    public function getResetPasswordText(): string
    {
        /** @var User $subject */
        $subject = $this->getSubject();
        if (!$subject) {
            return '';
        }
        $passwordRequests = $this->entityManager->getRepository(ResetPasswordRequest::class)->findBy(['user' => $subject]);
        $text = 'Statut : <h5 class="badge bg-blue text-white">Pas de réinitialisation en cours</h5>';

        if (0 < count($passwordRequests)) {
            $passwordRequest = $passwordRequests[0];
            $resetTokenPath = sprintf('/user/%s/unlock-password-reset', $subject->getId());
            $requestDate = $passwordRequest->getRequestedAt();
            $requestDateString = $requestDate->setTimezone(new \DateTimeZone('Europe/Paris'))->format('H\hi');
            $format = 'Statut : <span class="badge bg-green text-white">En cours de réinitialisation</span><p>Demande de réinitialisation effectuée à %s heure de Paris</p><a class="btn btn-success" href="%s">Permettre une nouvelle demande de réinitialisation</a>';

            $text = sprintf($format, $requestDateString, $resetTokenPath);
        }

        return $text;
    }
}
