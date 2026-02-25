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
<<<<<<< HEAD
    description: 'Creates a volunteer admin user'
=======
    description: 'Crée un utilisateur volontaire (admin)'
>>>>>>> isramedi
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
<<<<<<< HEAD
        $admin->setEmail('voluntaire@mediconnect.fr');
        $admin->setNomComplet('Volontaire Admin');
        $admin->setStatut(StatutCompte::ACTIF);

        // Hash the password
=======
        $admin->setEmail('volontaire@mediconnect.fr');
        $admin->setNomComplet('Volontaire Admin');
        $admin->setStatut(StatutCompte::ACTIF);

>>>>>>> isramedi
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'password123');
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

<<<<<<< HEAD
        $output->writeln('✅ Utilisateur créé avec succès!');
        $output->writeln('📧 Email: voluntaire@mediconnect.fr');
        $output->writeln('🔑 Password: password123');
=======
        $output->writeln('✅ Utilisateur volontaire (admin) créé avec succès!');
        $output->writeln('📧 Email: volontaire@mediconnect.fr');
        $output->writeln('🔑 Mot de passe: password123');
>>>>>>> isramedi

        return Command::SUCCESS;
    }
}
