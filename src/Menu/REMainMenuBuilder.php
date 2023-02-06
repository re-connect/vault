<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class REMainMenuBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private FactoryInterface $factory;

    /**
     * @param FactoryInterface $factory
     *
     * Add any other dependency you need
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function headerMenu(array $options)
    {
        $menu = $this->factory->createItem('root');
        $arRoutes = [
            'header.menu.commentCaMarche' => ['route' => 're_main_commentCaMarche'],
            'header.menu.lesRelaisReconnect' => ['route' => 're_main_lesRelaisReconnect'],
            'header.menu.ilsParlentDeReconnect' => ['route' => 're_main_ilsParlentDeReconnect'],
            'header.menu.quiSommesNous' => ['route' => 're_main_quiSommesNous'],
        ];

        foreach ($arRoutes as $title => $route) {
            $menu->addChild($title, $route);
        }

        return $menu;
    }

    public function devenirUnRelaiReconnectMenu(FactoryInterface $factory, array $options)
    {
        $menu = $this->factory->createItem('root');
        $arRoutes = [
            'devenirUnRelaiReconnect.menu.identification' => ['extras' => ['color' => 'green', 'image' => 'build/images/icons/profil.png', 'imageAct' => 'build/images/icons/profil_act.png'], 'route' => 're_main_devenirUnRelaiReconnect_identification'],
            'devenirUnRelaiReconnect.menu.inscriptionStructures' => ['extras' => ['color' => 'green', 'image' => 'build/images/icons/relai.png', 'imageAct' => 'build/images/icons/relai_act.png'], 'route' => 're_main_devenirUnRelaiReconnect_inscriptionStructures'],
            'devenirUnRelaiReconnect.menu.recapitulatif' => ['extras' => ['color' => 'green', 'image' => 'build/images/icons/recapitulatif.png', 'imageAct' => 'build/images/icons/recapitulatif_act.png'], 'route' => 're_main_devenirUnRelaiReconnect_recapitulatif'],
            'devenirUnRelaiReconnect.menu.paiement' => ['extras' => ['color' => 'green', 'image' => 'build/images/icons/cart.png', 'imageAct' => 'build/images/icons/cart_act.png'], 'route' => 're_main_devenirUnRelaiReconnect_paiement'],
            // "devenirUnRelaiReconnect.menu.confirmation" => array("route" => "re_main_devenirUnRelaiReconnect_confirmation"),
        ];
        foreach ($arRoutes as $title => $route) {
            $menu->addChild($title, $route);
        }

        return $menu;
    }
}
