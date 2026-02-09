<?php

namespace App\Command;

use App\Entity\CategorieSante;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-sample-data',
    description: 'Charge des données d\'exemple pour les catégories de santé',
)]
class LoadSampleDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si des catégories existent déjà
        $existingCount = $this->entityManager->getRepository(CategorieSante::class)->count([]);
        
        if ($existingCount > 0) {
            $io->warning("Des catégories existent déjà dans la base de données ($existingCount catégories).");
            if (!$io->confirm('Voulez-vous continuer et ajouter plus de catégories ?', false)) {
                return Command::SUCCESS;
            }
        }

        $categories = [
            [
                'nom' => 'Anatomie',
                'description' => 'Étude de la structure du corps humain et de ses différents systèmes.',
                'type' => 'Culture Générale'
            ],
        
            [
                'nom' => 'Physiologie',
                'description' => 'Comprendre le fonctionnement des organes et systèmes du corps humain.',
                'type' => 'Culture Générale'
            ],
            [
                'nom' => 'Cardiologie',
                'description' => 'Spécialité médicale qui traite des troubles du cœur et du système cardiovasculaire.',
                'type' => 'Spécialité Médicale'
            ],
            [
                'nom' => 'Pédiatrie',
                'description' => 'Médecine des enfants, de la naissance à l\'adolescence.',
                'type' => 'Spécialité Médicale'
            ],
            [
                'nom' => 'Neurologie',
                'description' => 'Étude et traitement des maladies du système nerveux.',
                'type' => 'Spécialité Médicale'
            ],
            [
                'nom' => 'Pharmacologie',
                'description' => 'Science des médicaments et de leur action sur l\'organisme.',
                'type' => 'Culture Générale'
            ],
            [
                'nom' => 'Dermatologie',
                'description' => 'Spécialité médicale qui traite des maladies de la peau.',
                'type' => 'Spécialité Médicale'
            ],
            [
                'nom' => 'Nutrition',
                'description' => 'Science de l\'alimentation et de son impact sur la santé.',
                'type' => 'Culture Générale'
            ],
        ];

        $count = 0;
        foreach ($categories as $catData) {
            $categorie = new CategorieSante();
            $categorie->setNom($catData['nom']);
            $categorie->setDescription($catData['description']);
            $categorie->setType($catData['type']);

            $this->entityManager->persist($categorie);
            $count++;
            
            $io->writeln(sprintf(' ✓ Catégorie "%s" créée', $catData['nom']));
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d catégories ont été créées avec succès !', $count));
        $io->info('Vous pouvez maintenant accéder à la page /savoir-medical pour voir les catégories.');

        return Command::SUCCESS;
    }
}
