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
    description: 'Creates an organizer user'
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

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($organizer, 'password123');
        $organizer->setPassword($hashedPassword);

        $this->entityManager->persist($organizer);
        $this->entityManager->flush();

        $output->writeln('✅ Compte Organisateur créé avec succès!');
        $output->writeln('📧 Email: organisateur@mediconnect.fr');
        $output->writeln('🔑 Password: password123');

        return Command::SUCCESS;
    }
}
