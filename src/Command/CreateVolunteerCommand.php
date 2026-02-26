<?php

namespace App\Command;

use App\Entity\Admin;
use App\Entity\StatutCompte;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-volunteer',
    description: 'Crée un utilisateur volontaire (admin)'
)]
class CreateVolunteerCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = new Admin();
        $admin->setEmail('volontaire@mediconnect.fr');
        $admin->setNomComplet('Volontaire Admin');
        $admin->setStatut(StatutCompte::ACTIF);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'password123');
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $output->writeln('✅ Utilisateur volontaire (admin) créé avec succès!');
        $output->writeln('📧 Email: volontaire@mediconnect.fr');
        $output->writeln('🔑 Mot de passe: password123');

        return Command::SUCCESS;
    }
}
