<?php

namespace App\Command;

use App\Entity\Admin;
use App\Entity\StatutCompte;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Create the super admin user',
)]
class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if admin already exists
        $existingAdmin = $this->entityManager->getRepository(Admin::class)
            ->findOneBy(['email' => 'ahmedar@gmail.com']);

        if ($existingAdmin) {
            $io->warning('Super admin already exists!');
            return Command::SUCCESS;
        }

        // Create super admin
        $admin = new Admin();
        $admin->setEmail('ahmedar@gmail.com');
        $admin->setNomComplet('Ahmed');
        $admin->setTelephone('+216 00 000 000');
        $admin->setStatut(StatutCompte::ACTIF);
        $admin->setEmailVerified(true);
        
        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($admin, '123456');
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Super admin created successfully!');
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', 'ahmedar@gmail.com'],
                ['Name', 'Ahmed'],
                ['Password', '123456'],
                ['Role', 'ADMIN'],
            ]
        );

        return Command::SUCCESS;
    }
}
