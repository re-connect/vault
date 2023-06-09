<?php

namespace App\Menu;

use App\Entity\Beneficiaire;
use App\Entity\User;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class REUserMenuBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private FactoryInterface $factory;
    private TokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;

    public function __construct(FactoryInterface $factory, TokenStorageInterface $tokenStorage, TranslatorInterface $translator)
    {
        $this->factory = $factory;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    public function beneficiaireMenu(array $options): ItemInterface
    {
        /** @var Beneficiaire $beneficiaire */
        $beneficiaire = $options['beneficiaire'];
        $beneficiaireId = $beneficiaire->getId();

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $menuTrad = 'menu';

        if (!$user->isBeneficiaire()) {
            $menuTrad = 'menu2';
        }

        $menu = $this->factory->createItem('root');

        if (array_key_exists('small', $options)) {
            $arRoutes = [
                'beneficiaire.'.$menuTrad.'.mesDocuments' => ['extras' => ['width' => 35, 'title' => $this->translator->trans('benef_new_guide_which_info_icon_1'), 'color' => 'pink', 'image' => 'build/images/icons/docs_bleu.png', 'imageAct' => 'build/images/icons/docs_blanc.png'], 'route' => 'list_documents', 'routeParameters' => ['id' => $beneficiaireId]],
                'beneficiaire.'.$menuTrad.'.monCalendrier' => ['extras' => ['width' => 30, 'title' => $this->translator->trans('benef_new_guide_which_info_icon_2'), 'color' => 'purple', 'image' => 'build/images/icons/rappels_bleu.png', 'imageAct' => 'build/images/icons/rappels_blanc.png'], 'route' => 'list_events', 'routeParameters' => ['id' => $beneficiaireId]],
                'beneficiaire.'.$menuTrad.'.mesContacts' => ['extras' => ['width' => 30, 'title' => $this->translator->trans('benef_new_guide_which_info_icon_3'), 'color' => 'orange', 'image' => 'build/images/icons/contacts_bleu.png', 'imageAct' => 'build/images/icons/contacts_blanc.png'], 'route' => 'list_contacts', 'routeParameters' => ['id' => $beneficiaireId]],
                'beneficiaire.'.$menuTrad.'.mesNotes' => ['extras' => ['width' => 35, 'title' => $this->translator->trans('benef_new_guide_which_info_icon_4'), 'color' => 'lightPink', 'image' => 'build/images/icons/notes_bleu.png', 'imageAct' => 'build/images/icons/notes_blanc.png'], 'route' => 'list_notes', 'routeParameters' => ['id' => $beneficiaireId]],
            ];
            $menu->setExtras(['small' => $options['small']]);
        } else {
            $arRoutes = [
                /*                "beneficiaire." . $menuTrad . ".mesDocuments" => ["extras" => ["color" => "pink", 'image' => "build/images/icons/documents.png", 'imageAct' => "build/images/icons/documents_act2.png"], "route" => "re_app_document_list", "routeParameters" => ["id" => $beneficiaireId]], */
                'beneficiaire.'.$menuTrad.'.mesDocuments' => ['extras' => ['color' => 'white', 'image' => 'build/images/icons/docs_bleu.png', 'imageAct' => 'build/images/icons/docs_blanc.png'], 'route' => 'list_documents', 'routeParameters' => ['id' => $beneficiaireId]],
                'beneficiaire.'.$menuTrad.'.monCalendrier' => ['extras' => ['color' => 'purple', 'image' => 'build/images/icons/rappels_bleu.png', 'imageAct' => 'build/images/icons/rappels_blanc.png'], 'route' => 'list_events', 'routeParameters' => ['id' => $beneficiaireId]],
                'beneficiaire.'.$menuTrad.'.mesContacts' => ['extras' => ['color' => 'orange', 'image' => 'build/images/icons/contacts_bleu.png', 'imageAct' => 'build/images/icons/contacts_blanc.png'], 'route' => 'list_contacts', 'routeParameters' => ['id' => $beneficiaireId]],
                'beneficiaire.'.$menuTrad.'.mesNotes' => ['extras' => ['color' => 'lightPink', 'image' => 'build/images/icons/notes_bleu.png', 'imageAct' => 'build/images/icons/notes_blanc.png'], 'route' => 'list_notes', 'routeParameters' => ['id' => $beneficiaireId]],
            ];

            if ($user->isBeneficiaire()) {
                $arRoutes['beneficiaire.'.$menuTrad.'.mesRelaisReconnect'] = ['extras' => ['color' => 'blue', 'image' => 'build/images/icons/relais_bleu.png', 'imageAct' => 'build/images/icons/relais_blanc.png'], 'route' => 'list_relays', 'routeParameters' => ['id' => $beneficiaireId]];
            }

            if (array_key_exists('itemPerLine', $options)) {
                $menu->setExtras(['itemPerLine' => $options['itemPerLine']]);
            }
        }

        foreach ($arRoutes as $title => $route) {
            $menu->addChild($title, $route);
        }

        return $menu;
    }

    public function membreMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $arRoutes = [
            'membre.menu.gestionBeneficiaires' => ['extras' => ['color' => 'green', 'image' => 'build/images/icons/profil.png', 'imageAct' => 'build/images/icons/profil_act.png'], 'route' => 'list_beneficiaries'],
            'membre.menu.gestionRelais' => ['extras' => ['color' => 'blue', 'image' => 'build/images/icons/relai.png', 'imageAct' => 'build/images/icons/relai_act2.png'], 'route' => 're_membre_centres'],
        ];

        foreach ($arRoutes as $title => $route) {
            $menu->addChild($title, $route);
        }

        if (array_key_exists('itemPerLine', $options)) {
            $menu->setExtras(['itemPerLine' => $options['itemPerLine']]);
        }

        return $menu;
    }

    public function gestionnaireMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $arRoutes = [
            'gestionnaire.menu.gestionBeneficiaires' => ['extras' => ['color' => 'green', 'image' => 'build/images/icons/avatar-blue.png', 'imageAct' => 'build/images/icons/avatar_white.png'], 'route' => 're_gestionnaire_beneficiaires'],
        ];

        foreach ($arRoutes as $title => $route) {
            $menu->addChild($title, $route);
        }

        if (array_key_exists('itemPerLine', $options)) {
            $menu->setExtras(['itemPerLine' => $options['itemPerLine']]);
        }

        return $menu;
    }
}
