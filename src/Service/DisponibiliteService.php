<?php

namespace App\Service;

use App\Entity\Medecin;
use App\Entity\PlanningMedecin;
use App\Repository\RendezVousRepository;
use App\Entity\StatutRendezVous;

class DisponibiliteService
{
    public function __construct(private RendezVousRepository $rdvRepo) {}

    /**
     * Retourne la grille complète pour un jour donné (libres et occupés).
     */
    public function getAgendaGrid(Medecin $m, \DateTimeInterface $d, ?PlanningMedecin $p = null): array
    {
        $p = $p ?? $m->getPlanning();
        if (!$p) return [];

        // Protection contre les jours non travaillés
        $dayNum = (int)$d->format('N'); 
        $map = [1=>'lundi', 2=>'mardi', 3=>'mercredi', 4=>'jeudi', 5=>'vendredi', 6=>'samedi', 7=>'dimanche'];
        $currentDayFr = $map[$dayNum] ?? '';
        
        $joursOuverts = array_map('strtolower', $p->getJoursOuverture());
        if (!in_array($currentDayFr, $joursOuverts)) {
            return [];
        }

        $theory = $this->genTheoretic($p, $d);
        $rdvs = $this->rdvRepo->findByMedecinAndDate($m, $d);
        
        $grid = [];
        foreach ($theory as $slot) {
            $rdv = $this->findOverlappingRdv($slot, $rdvs);
            $slot['rdv'] = $rdv;
            $slot['type'] = $rdv ? 'occupe' : 'libre';
            $grid[] = $slot;
        }
        return $grid;
    }

    /**
     * @deprecated Utilisez getAgendaGrid pour la vue agenda.
     */
    public function findDispoByDate(Medecin $m, \DateTimeInterface $d, ?PlanningMedecin $p = null): array
    {
        $grid = $this->getAgendaGrid($m, $d, $p);
        return array_filter($grid, fn($s) => $s['type'] === 'libre');
    }

    public function getOccupesForDate(Medecin $m, \DateTimeInterface $d): array 
    { 
        return $this->rdvRepo->findByMedecinAndDate($m, $d); 
    }

    private function genTheoretic(PlanningMedecin $p, \DateTimeInterface $d): array
    {
        $c = []; 
        $dur = $p->getDureeConsultation();
        if ($dur <= 0) $dur = 30;

        if ($p->getHeureDebutMatin() && $p->getHeureFinMatin()) {
            $this->fill($c, $d, $p->getHeureDebutMatin(), $p->getHeureFinMatin(), $dur);
        }
        if ($p->getHeureDebutApresMidi() && $p->getHeureFinApresMidi()) {
            $this->fill($c, $d, $p->getHeureDebutApresMidi(), $p->getHeureFinApresMidi(), $dur);
        }
        return $c;
    }

    private function fill(array &$c, \DateTimeInterface $d, \DateTimeInterface $start, \DateTimeInterface $end, int $dur): void
    {
        $baseDate = clone $d;
        $curr = (clone $baseDate)->setTime((int)$start->format('H'), (int)$start->format('i'), 0);
        $limit = (clone $baseDate)->setTime((int)$end->format('H'), (int)$end->format('i'), 0);

        while ($curr < $limit) {
            $next = (clone $curr)->modify("+$dur minutes");
            if ($next > $limit) break;
            
            $c[] = [
                'heure' => $curr->format('H:i'),
                'de' => clone $curr,
                'a' => clone $next
            ];
            $curr = $next;
        }
    }

    private function findOverlappingRdv(array $slot, array $rdvs): ?\App\Entity\RendezVous
    {
        foreach ($rdvs as $r) {
            if ($r->getStatut() === StatutRendezVous::ANNULE) continue;
            
            if ($r->getDateDebut() < $slot['a'] && $r->getDateFin() > $slot['de']) {
                return $r;
            }
        }
        return null;
    }
}