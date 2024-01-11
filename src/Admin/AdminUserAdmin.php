<?php

namespace App\Admin;

use App\Entity\User;
use App\ManagerV2\UserManager;
use App\ServiceV2\ResettingService;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\Form\Type\DateTimePickerType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class AdminUserAdmin extends AbstractAdmin
{
    private UserManager $userManager;

    private ResettingService $resettingService;

    protected function prePersist(object $object): void
    {
        if ($object instanceof User) {
            $this->userManager->createRandomPassword($object);
        }
        parent::prePersist($object);
    }

    protected function postPersist(object $object): void
    {
        if ($object instanceof User) {
            $this->resettingService->sendPasswordResetEmail($object->getEmail());
        }
        parent::postPersist($object);
    }

    public function setResettingService(ResettingService $resettingService): void
    {
        $this->resettingService = $resettingService;
    }

    public function setUserManager(UserManager $userManager): void
    {
        $this->userManager = $userManager;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('id', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('email', null, ['route' => ['name' => 'edit']])
            ->addIdentifier('username', null, ['route' => ['name' => 'edit']])
            ->add('nom', null, ['label' => 'Nom'])
            ->add('prenom', null, ['label' => 'Prénom'])
            ->add('enabled', null, ['label' => 'Actif'])
            ->addIdentifier('createdAt', null, ['route' => ['name' => 'edit']]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form->add('email', EmailType::class, ['help' => 'L\'email doit être au format prenom.nom+admin@reconnect.fr'])
            ->add('nom', null, ['label' => 'lastname'])
            ->add('prenom', null, ['label' => 'firstname'])
            ->add('enabled', null, ['label' => 'enabled'])
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
            ->add('typeUser', HiddenType::class, ['data' => User::USER_TYPE_ADMINISTRATEUR]);
    }

    protected function configureQuery(ProxyQueryInterface $query): ProxyQueryInterface
    {
        $query = parent::configureQuery($query);

        $rootAlias = current($query->getRootAliases());

        $query->andWhere($rootAlias.'.roles LIKE :adminRole');
        $query->setParameter('adminRole', '%'.User::USER_TYPE_ADMINISTRATEUR.'%');

        return $query;
    }
}
