<?php
require __DIR__.'/vendor/autoload.php';
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\PlanningMedecin;
use App\Entity\Medecin;

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$service = $container->get(App\Service\DisponibiliteService::class);

$plannings = $em->getRepository(PlanningMedecin::class)->findAll();
echo "COUNT: " . count($plannings) . "\n";

foreach ($plannings as $p) {
    echo "ID: " . $p->getId() . "\n";
    echo "Medecin: " . $p->getMedecin()->getNomComplet() . " (ID: " . $p->getMedecin()->getId() . ")\n";
    echo "Duree: " . $p->getDureeConsultation() . "\n";
    echo "Jours: " . implode(', ', $p->getJoursOuverture()) . "\n";
    echo "Matin: " . ($p->getHeureDebutMatin() ? $p->getHeureDebutMatin()->format('H:i') : 'null') . " - " . ($p->getHeureFinMatin() ? $p->getHeureFinMatin()->format('H:i') : 'null') . "\n";
    echo "Apres-midi: " . ($p->getHeureDebutApresMidi() ? $p->getHeureDebutApresMidi()->format('H:i') : 'null') . " - " . ($p->getHeureFinApresMidi() ? $p->getHeureFinApresMidi()->format('H:i') : 'null') . "\n";
    
    $date = new \DateTime('2026-02-23'); // Lundi
    echo "Testing Lundi 2026-02-23...\n";
    $dispos = $service->findDispoByDate($p->getMedecin(), $date);
    echo "DISPOS FOUND: " . count($dispos) . "\n";
    foreach ($dispos as $d) echo " - " . $d['heure'] . "\n";
}