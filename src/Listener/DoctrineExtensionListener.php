<?php

namespace App\Listener;

use Gedmo\Loggable\LoggableListener;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Bundle\SecurityBundle\Security;

class DoctrineExtensionListener
{
    public function __construct(
        private readonly Security $security,
        private readonly TranslatableListener $translatableListener,
        private readonly LoggableListener $loggableListener
    ) {
    }

    public function onLateKernelRequest($event)
    {
        $this->translatableListener->setTranslatableLocale($event->getRequest()->getLocale());
    }

    public function onKernelRequest()
    {
        $user = $this->security->getUser();
        if (null !== $user) {
            $this->loggableListener->setUsername($user->getUserIdentifier());
        }
    }
}
