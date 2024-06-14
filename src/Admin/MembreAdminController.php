<?php

namespace App\Admin;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MembreAdminController extends CRUDController
{
    #[\Override]
    protected function preDelete(Request $request, object $object): ?Response
    {
        $errorMessage = $this->getErrorMessage($object);

        if ($errorMessage) {
            $request->getSession()->getFlashBag()->add('error', $errorMessage);

            return new RedirectResponse($this->admin->generateUrl('edit', ['id' => $object->getId()]));
        }

        return null;
    }

    private function getErrorMessage(object $object): ?string
    {
        $isAffiliated = $object->getCentres()->count() > 0;

        return $this->getUser()->isSuperAdmin()
            ? $this->getSuperAdminErrorMessage($isAffiliated)
            : $this->getAdminErrorMessage($isAffiliated, $object->getUser()->isTest());
    }

    public function getSuperAdminErrorMessage(bool $isAffiliated): ?string
    {
        return $isAffiliated ? 'Vous ne pouvez pas supprimer ce professionnel, vous devez d’abord le désaffilier de ses relais' : null;
    }

    public function getAdminErrorMessage(bool $isAffiliated, bool $isTest): ?string
    {
        return match (true) {
            !$isTest => 'Vous ne pouvez pas supprimer ce professionnel, vous devez le désactiver',
            $isTest && $isAffiliated => 'Vous ne pouvez pas supprimer ce professionnel test, vous devez d’abord le désaffilier de ses relais',
            default => null,
        };
    }
}
