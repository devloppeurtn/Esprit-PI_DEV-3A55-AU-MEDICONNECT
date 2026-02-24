<?php
require __DIR__.'/vendor/autoload.php';
use App\Kernel;
use App\Entity\Medecin;
use App\Entity\PlanningMedecin;

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$service = $container->get(App\Service\DisponibiliteService::class);

$planning = $em->getRepository(PlanningMedecin::class)->findOneBy([]);
if (!$planning) { echo "NO PLANNING FOUND\n"; exit; }

$medecin = $planning->getMedecin();
echo "MEDECIN: " . $medecin->getNomComplet() . "\n";
echo "JOURS: " . json_encode($planning->getJoursOuverture()) . "\n";
echo "MATIN: " . ($planning->getHeureDebutMatin() ? $planning->getHeureDebutMatin()->format('H:i') : 'null') . "\n";
echo "DUREE: " . $planning->getDureeConsultation() . "\n";

$date = new \DateTime('2026-03-23'); // Lundi
echo "DATE: " . $date->format('l Y-m-d') . "\n";

$day = strtolower($date->format('l'));
echo "FORMATTED DAY: $day\n";

$dispos = $service->findDispoByDate($medecin, $date, $planning);
echo "DISPOS: " . count($dispos) . "\n";
foreach ($dispos as $d) echo " - " . $d['heure'] . "\n";