<?php
// bin/create_volunteer.php

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

require_once __DIR__ . '/../vendor/autoload_runtime.php';

return function (array $context) {
    $app = new Application($context['kernel']);
    $app->setAutoExit(false);

    // First delete if exists
    $output = new BufferedOutput();
    $app->run(new StringInput('dbal:run-sql "DELETE FROM utilisateur WHERE email=\'voluntaire@mediconnect.fr\'"'), $output);
    echo $output->fetch();

    // Create new user with proper hash
    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    $query = sprintf(
        "INSERT INTO utilisateur (email, mot_de_passe_hash, nom_complet, role, statut, date_creation, discr) VALUES ('voluntaire@mediconnect.fr', '%s', 'Volontaire Admin', 'ADMIN', 'ACTIF', NOW(), 'admin')",
        addslashes($passwordHash)
    );
    
    $output = new BufferedOutput();
    $app->run(new StringInput('dbal:run-sql "' . addslashes($query) . '"'), $output);
    echo $output->fetch();
    
    echo "\n✅ Utilisateur créé avec succès!\n";
    echo "Email: voluntaire@mediconnect.fr\n";
    echo "Password: password123\n";
};
