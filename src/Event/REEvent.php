<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class REEvent extends Event
{
    public const RE_EVENT_BENEFICIAIRE = 're.event.beneficiaire';
    public const RE_EVENT_MEMBRE = 're.event.membre';
    public const RE_EVENT_GESTIONNAIRE = 're.event.gestionnaire';
    public const RE_EVENT_DONNEEPERSONNELLE = 're.event.donneepersonnelle';
    public const RE_EVENT_EVENEMENT = 're.event.evenement';
    public const RE_EVENT_CENTRE = 're.event.centre';

    protected $type;
    protected $context = [];

    public function getContext(): array
    {
        return $this->context;
    }

    public function addContextItem($key, $value): REEvent
    {
        $this->context[$key] = $value;

        return $this;
    }

    public function __toString(): string
    {
        /* @var string $string */
        if (!empty($this->type)) {
            return $this->getConstName($this->type);
        }

        return '';
    }

    protected function getConstName($cstName)
    {
        $fooClass = new \ReflectionClass(get_class($this));
        $constants = $fooClass->getConstants();

        $constName = null;
        foreach ($constants as $name => $value) {
            if ($value === $cstName) {
                $constName = $name;
                break;
            }
        }

        return $constName;
    }
}
