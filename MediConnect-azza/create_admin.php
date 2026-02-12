<?php

use App\Entity\Admin;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__) . '/medi_connect-main/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(dirname(__DIR__) . '/medi_connect-main/.env');

$kernel = new \App\Kernel($_ENV['APP_ENV'], (bool) $_ENV['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine')->getManager();
$passwordHasher = $container->get('security.password_hasher');

$admin = new Admin();
$admin->setNomComplet('Volontaire');
$admin->setEmail('voluntaire@mediconnect.fr');
$hashedPassword = $passwordHasher->hashPassword($admin, 'password123');
$admin->setPassword($hashedPassword);

$entityManager->persist($admin);
$entityManager->flush();

echo "✅ Admin user created!\n";
echo "📧 Email: voluntaire@mediconnect.fr\n";
echo "🔑 Password: password123\n";
