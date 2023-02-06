<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

class Rappel implements \JsonSerializable
{
    public const EVENEMENT_RAPPEL_SMS = 'SMS';
    public const EVENEMENT_RAPPEL_MAIL = 'Mail';

    /**
     * @var int
     *
     * @Groups({"read-personal-data", "read-personal-data-v2"})
     */
    private $id;
    /**
     * @var \DateTime
     *
     * @Groups({"read-personal-data", "write-personal-data", "read-personal-data-v2", "write-personal-data-v2"})
     */
    private $date;

    /**
     * @Groups({"read-personal-data", "write-personal-data", "read-personal-data-v2", "write-personal-data-v2"})
     */
    private ?string $timezone = null;

    /** @var Evenement */
    private $evenement;
    /** @var bool */
    private $bEnvoye = false;
    /** @var SMS */
    private $sms;
    /** @var array */
    private $types;
    /** @var bool */
    private $archive = false;

    public function __construct()
    {
        $this->types[] = self::EVENEMENT_RAPPEL_SMS;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        $timezone = $this->timezone ?? 'Europe/Paris';
        $date = $this->date ?? new \DateTime();

        return new \DateTime($date->format('Y-m-d H:i:s'), new \DateTimeZone($timezone));
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return Rappel
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get evenement.
     *
     * @return Evenement
     */
    public function getEvenement()
    {
        return $this->evenement;
    }

    /**
     * Set evenement.
     *
     * @return Rappel
     */
    public function setEvenement(Evenement $evenement = null)
    {
        $this->evenement = $evenement;

        return $this;
    }

    /**
     * Get bEnvoye.
     *
     * @return bool
     */
    public function getBEnvoye()
    {
        return $this->bEnvoye;
    }

    /**
     * Set bEnvoye.
     *
     * @param bool $bEnvoye
     *
     * @return Rappel
     */
    public function setBEnvoye($bEnvoye)
    {
        $this->bEnvoye = $bEnvoye;

        return $this;
    }

    /**
     * Get sms.
     *
     * @return SMS|null
     */
    public function getSms()
    {
        return $this->sms;
    }

    /**
     * Set sms.
     *
     * @return Rappel
     */
    public function setSms(SMS $sms = null)
    {
        $this->sms = $sms;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->getDate()->format(\DateTime::W3C),
        ];
    }

    /**
     * Get types.
     *
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Set types.
     *
     * @param array $types
     *
     * @return Rappel
     */
    public function setTypes($types)
    {
        $this->types = $types;

        return $this;
    }

    /**
     * Get archive.
     *
     * @return bool
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Set archive.
     *
     * @param bool $archive
     *
     * @return Rappel
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
//            $this->sms = null === $this->sms ? null : clone $this->sms;
            $this->sms = null;
            $this->evenement = null;
        }
    }
}
