<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FaqQuestionRepository")
 */
class FaqQuestion
{
    private int $id;
    private string $text;
    private string $answer;
    /**
     * @Assert\PositiveOrZero()
     */
    private ?int $position = null;
    private \DateTime $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt->format('Y-m-d');
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
