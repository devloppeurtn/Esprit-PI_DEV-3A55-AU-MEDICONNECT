<?php

namespace App\Command;

use App\Entity\Organisateur;
use App\Entity\StatutCompte;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-organizer',
<<<<<<< HEAD
    description: 'Creates an organizer user'
=======
    description: 'Crée un utilisateur organisateur d\'événements'
>>>>>>> isramedi
)]
class CreateOrganizerCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $organizer = new Organisateur();
        $organizer->setEmail('organisateur@mediconnect.fr');
        $organizer->setNomComplet('Organisateur Events');
        $organizer->setStatut(StatutCompte::ACTIF);

<<<<<<< HEAD
        // Hash the password
=======
>>>>>>> isramedi
        $hashedPassword = $this->passwordHasher->hashPassword($organizer, 'password123');
        $organizer->setPassword($hashedPassword);

        $this->entityManager->persist($organizer);
        $this->entityManager->flush();

        $output->writeln('✅ Compte Organisateur créé avec succès!');
        $output->writeln('📧 Email: organisateur@mediconnect.fr');
<<<<<<< HEAD
        $output->writeln('🔑 Password: password123');
=======
        $output->writeln('🔑 Mot de passe: password123');
>>>>>>> isramedi

        return Command::SUCCESS;
    }
}
