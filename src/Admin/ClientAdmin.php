<?php

namespace App\Admin;

use App\Entity\Client;
use App\Entity\CreatorClient;
use App\Form\Type\CheckboxHierarchyType;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ClientAdmin extends AbstractAdmin
{
    private EntityManagerInterface $entityManager;

    public function preRemove($object): void
    {
        $creators = $this->entityManager->getRepository(CreatorClient::class)->findBy(['entity' => $object->getId()]);
        foreach ($creators as $creator) {
            $this->entityManager->remove($creator);
        }
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('id')
            ->add('nom')
            ->add('randomId')
            ->add('secret')
            ->add('redirectUris')
            ->add('allowedGrantTypes');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->addIdentifier('nom', null, ['route' => ['name' => 'edit']])
            ->add('publicId', null, ['label' => 'client_id'])
            ->add('secret', null, ['label' => 'client_secret'])
            ->add('allowedGrantTypes', 'string', [
                'template' => 'admin/client/list_grant_type.html.twig',
                'label' => 'grant_type', ])
            ->add('actif')
            ->add('newClientIdentifier');
    }

    protected function configureFormFields(FormMapper $form): void
    {
        /** @var Client $client */
        $client = $this->getSubject();
        $readOnly = !$client->isNew();
        $form
            ->add('actif', null, [
                'attr' => ['checked' => 'checked'],
            ])
            ->add('nom', TextType::class, [
                'required' => true,
                'attr' => ['read_only' => $readOnly],
            ])
            ->add('dossierNom', TextType::class, [
                'required' => true,
            ])
            ->add('newClientIdentifier')
            ->add('allowedGrantTypes', ChoiceType::class, [
                'label' => 'grant_type',
                'choices' => [
                    'client_credentials' => 'client_credentials',
                    'refresh_token' => 'refresh_token',
                    'password' => 'password',
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('access', CheckboxHierarchyType::class, [
                'choices' => [
                    Client::ACCESS_DOCUMENT_ALL => Client::ACCESS_DOCUMENT_ALL,
                    Client::ACCESS_DOCUMENT_ALL.'  sub-access' => [
                        Client::ACCESS_DOCUMENT_READ => Client::ACCESS_DOCUMENT_READ,
                        Client::ACCESS_DOCUMENT_WRITE => Client::ACCESS_DOCUMENT_WRITE,
                        Client::ACCESS_DOCUMENT_DELETE => Client::ACCESS_DOCUMENT_DELETE,
                    ],
                    Client::ACCESS_EVENEMENT_ALL => Client::ACCESS_EVENEMENT_ALL,
                    Client::ACCESS_EVENEMENT_ALL.'  sub-access' => [
                        Client::ACCESS_EVENEMENT_READ => Client::ACCESS_EVENEMENT_READ,
                        Client::ACCESS_EVENEMENT_WRITE => Client::ACCESS_EVENEMENT_WRITE,
                        Client::ACCESS_EVENEMENT_DELETE => Client::ACCESS_EVENEMENT_DELETE,
                    ],
                    Client::ACCESS_NOTE_ALL => Client::ACCESS_NOTE_ALL,
                    Client::ACCESS_NOTE_ALL.'  sub-access' => [
                        Client::ACCESS_NOTE_READ => Client::ACCESS_NOTE_READ,
                        Client::ACCESS_NOTE_WRITE => Client::ACCESS_NOTE_WRITE,
                        Client::ACCESS_NOTE_DELETE => Client::ACCESS_NOTE_DELETE,
                    ],
                    Client::ACCESS_CONTACT_ALL => Client::ACCESS_CONTACT_ALL,
                    Client::ACCESS_CONTACT_ALL.'  sub-access' => [
                        Client::ACCESS_CONTACT_READ => Client::ACCESS_CONTACT_READ,
                        Client::ACCESS_CONTACT_WRITE => Client::ACCESS_CONTACT_WRITE,
                        Client::ACCESS_CONTACT_DELETE => Client::ACCESS_CONTACT_DELETE,
                    ],
                    Client::ACCESS_BENEFICIAIRE_ALL => Client::ACCESS_BENEFICIAIRE_ALL,
                    Client::ACCESS_BENEFICIAIRE_ALL.'  sub-access' => [
                        Client::ACCESS_BENEFICIAIRE_READ => Client::ACCESS_BENEFICIAIRE_READ,
                        Client::ACCESS_BENEFICIAIRE_WRITE => Client::ACCESS_BENEFICIAIRE_WRITE,
                        Client::ACCESS_BENEFICIAIRE_WRITE_WITH_PASSWORD => Client::ACCESS_BENEFICIAIRE_WRITE_WITH_PASSWORD,
                        Client::ACCESS_BENEFICIAIRE_DELETE => Client::ACCESS_BENEFICIAIRE_DELETE,
                    ],
                    Client::ACCESS_USER_ALL => Client::ACCESS_USER_ALL,
                    Client::ACCESS_USER_ALL.'  sub-access' => [
                        Client::ACCESS_USER_READ => Client::ACCESS_USER_READ,
                        Client::ACCESS_USER_WRITE => Client::ACCESS_USER_WRITE,
                        Client::ACCESS_USER_DELETE => Client::ACCESS_USER_DELETE,
                    ],
                    Client::ACCESS_MEMBRE_ALL => Client::ACCESS_MEMBRE_ALL,
                    Client::ACCESS_MEMBRE_ALL.'  sub-access' => [
                        Client::ACCESS_MEMBRE_READ => Client::ACCESS_MEMBRE_READ,
                        Client::ACCESS_MEMBRE_WRITE => Client::ACCESS_MEMBRE_WRITE,
                        Client::ACCESS_MEMBRE_DELETE => Client::ACCESS_MEMBRE_DELETE,
                    ],
                    Client::ACCESS_CENTRE_ALL => Client::ACCESS_CENTRE_ALL,
                    Client::ACCESS_CENTRE_ALL.'  sub-access' => [
                        Client::ACCESS_CENTRE_READ => Client::ACCESS_CENTRE_READ,
                        Client::ACCESS_CENTRE_WRITE => Client::ACCESS_CENTRE_WRITE,
                        Client::ACCESS_CENTRE_DELETE => Client::ACCESS_CENTRE_DELETE,
                    ],
                ],
                'expanded' => true,
                'multiple' => true,
            ]);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('nom')
            ->add('dossierNom', null, [
                'label' => 'Nom du dossier',
            ])
            ->add('id', null, [
                'template' => 'admin/client/show_client_id.html.twig',
                'label' => 'client_id',
            ])
            ->add('secret', null, [
                'label' => 'client_secret',
            ])
            ->add('allowedGrantTypes', null, [
                'template' => 'admin/client/show_grant_type.html.twig',
                'label' => 'grant_type',
            ])
            ->add('actif');
    }
}
