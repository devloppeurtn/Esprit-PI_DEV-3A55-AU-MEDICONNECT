<?php

namespace App\Command;

use App\Entity\Patient;
use App\Entity\DossierMedical;
use App\Entity\Consultation;
use App\Entity\Medecin;
use App\Repository\MedecinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-patient',
    description: 'Crée un patient de test avec un dossier médical pour tester les recommandations IA',
)]
class CreateTestPatientCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MedecinRepository $medecinRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Créer un patient de test
        $patient = new Patient();
        $patient->setEmail('patient.test@mediconnect.com');
        $patient->setNomComplet('Jean Diabétique');
        $patient->setTelephone('0612345678');
        $patient->setDateNaissance(new \DateTime('1975-05-15'));
        $patient->setAdresse('123 Rue de la Santé, Paris');
        
        $hashedPassword = $this->passwordHasher->hashPassword($patient, 'test123');
        $patient->setPassword($hashedPassword);
        $patient->setEmailVerified(true);

        // Créer un dossier médical avec des conditions
        $dossier = new DossierMedical();
        $dossier->setMaladiesChroniques('Diabète de type 2, Hypertension artérielle');
        $dossier->setAllergies('Pénicilline, Pollen');
        $dossier->setPatient($patient);

        // Trouver un médecin pour les consultations
        $medecin = $this->medecinRepository->findOneBy([]);
        
        if ($medecin) {
            // Ajouter des consultations récentes
            $consultation1 = new Consultation();
            $consultation1->setDate(new \DateTime('-2 months'));
            $consultation1->setDiagnostic('Contrôle du diabète - glycémie élevée');
            $consultation1->setResume('Patient présente une glycémie à jeun de 1.45g/L. Ajustement du traitement nécessaire.');
            $consultation1->setDossierMedical($dossier);
            $consultation1->setMedecin($medecin);

            $consultation2 = new Consultation();
            $consultation2->setDate(new \DateTime('-1 month'));
            $consultation2->setDiagnostic('Suivi hypertension - tension contrôlée');
            $consultation2->setResume('Tension artérielle: 135/85. Continuer le traitement actuel.');
            $consultation2->setDossierMedical($dossier);
            $consultation2->setMedecin($medecin);

            $this->entityManager->persist($consultation1);
            $this->entityManager->persist($consultation2);
        }

        $this->entityManager->persist($patient);
        $this->entityManager->persist($dossier);
        $this->entityManager->flush();

        $io->success('Patient de test créé avec succès!');
        $io->table(
            ['Champ', 'Valeur'],
            [
                ['Email', 'patient.test@mediconnect.com'],
                ['Mot de passe', 'test123'],
                ['Nom', 'Jean Diabétique'],
                ['Maladies', 'Diabète de type 2, Hypertension artérielle'],
                ['Allergies', 'Pénicilline, Pollen'],
                ['Consultations', $medecin ? '2 consultations récentes' : 'Aucune (pas de médecin trouvé)'],
            ]
        );

        $io->note('Connectez-vous avec ce compte pour voir les recommandations IA personnalisées!');

        return Command::SUCCESS;
    }
}
