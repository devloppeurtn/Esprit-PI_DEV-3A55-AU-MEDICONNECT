<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
<<<<<<< HEAD
=======
<<<<<<< HEAD
use App\Entity\Evenement;
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[ORM\Table(name: 'participant')]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Evenement $evenement = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
<<<<<<< HEAD
=======
<<<<<<< HEAD

=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
<<<<<<< HEAD
=======
<<<<<<< HEAD

=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
<<<<<<< HEAD
=======
<<<<<<< HEAD

=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(Evenement $evenement): self
    {
        $this->evenement = $evenement;
<<<<<<< HEAD
        return $this;
    }

=======
<<<<<<< HEAD

        return $this;
    }

    // Backwards compatibility
    public function getModuleFour(): ?Evenement
    {
        return $this->getEvenement();
    }

    public function setModuleFour(Evenement $moduleFour): self
    {
        return $this->setEvenement($moduleFour);
    }

=======
        return $this;
    }

>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
