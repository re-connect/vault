<?php

namespace App\Listener;

use Gedmo\Loggable\LoggableListener;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\Security\Core\Security;

class DoctrineExtensionListener
{
    private Security $security;
    private TranslatableListener $translatableListener;
    private LoggableListener $loggableListener;

    public function __construct(
        Security $security,
        TranslatableListener $translatableListener,
        LoggableListener $loggableListener
    ) {
        $this->security = $security;
        $this->translatableListener = $translatableListener;
        $this->loggableListener = $loggableListener;
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
