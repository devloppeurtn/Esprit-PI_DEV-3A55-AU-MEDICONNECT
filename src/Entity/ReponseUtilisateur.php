<?php

namespace App\Entity;

use App\Repository\ReponseUtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReponseUtilisateurRepository::class)]
#[ORM\Table(name: 'reponse_utilisateur')]
class ReponseUtilisateur
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: QuestionQuiz::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?QuestionQuiz $question = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $reponseChoisie = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $estCorrecte = false;

    #[ORM\Column(type: Types::INTEGER)]
    private int $pointsObtenus = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateReponse = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->dateReponse = new \DateTime();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getQuestion(): ?QuestionQuiz
    {
        return $this->question;
    }

    public function setQuestion(?QuestionQuiz $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getReponseChoisie(): ?string
    {
        return $this->reponseChoisie;
    }

    public function setReponseChoisie(string $reponseChoisie): self
    {
        $this->reponseChoisie = $reponseChoisie;

        return $this;
    }

    public function isEstCorrecte(): bool
    {
        return $this->estCorrecte;
    }

    public function setEstCorrecte(bool $estCorrecte): self
    {
        $this->estCorrecte = $estCorrecte;

        return $this;
    }

    public function getPointsObtenus(): int
    {
        return $this->pointsObtenus;
    }

    public function setPointsObtenus(int $pointsObtenus): self
    {
        $this->pointsObtenus = $pointsObtenus;

        return $this;
    }

    public function getDateReponse(): ?\DateTimeInterface
    {
        return $this->dateReponse;
    }

    public function setDateReponse(\DateTimeInterface $dateReponse): self
    {
        $this->dateReponse = $dateReponse;

        return $this;
    }
}
