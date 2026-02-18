<?php

namespace App\Controller\Admin;

use App\Entity\RendezVous;
use App\Entity\StatutRendezVous;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rendezvous')]
#[IsGranted('ROLE_ADMIN')]
class RendezVousAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_rendezvous_index', methods: ['GET'])]
    public function index(Request $request, RendezVousRepository $repo): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $statut = $request->query->get('statut', '');
        $period = $request->query->get('periode', 'tous'); // tous, aujourd_hui, avenir, passes

        $qb = $repo->createQueryBuilder('r')
            ->leftJoin('r.patient', 'p')
            ->leftJoin('r.medecin', 'm')
            ->addSelect('p', 'm')
            ->orderBy('r.dateDebut', 'DESC');

        if ($search !== '') {
            $qb->andWhere('LOWER(p.nomComplet) LIKE :q OR LOWER(m.nomComplet) LIKE :q OR LOWER(r.note) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }

        if ($statut !== '') {
            $enum = StatutRendezVous::tryFrom($statut);
            if ($enum) {
                $qb->andWhere('r.statut = :statut')->setParameter('statut', $enum);
            }
        }

        $todayStart = new \DateTimeImmutable('today');
        $todayEnd = $todayStart->setTime(23, 59, 59);
        if ($period === 'aujourdhui') {
            $qb->andWhere('r.dateDebut BETWEEN :start AND :end')
               ->setParameter('start', $todayStart)
               ->setParameter('end', $todayEnd);
        } elseif ($period === 'avenir') {
            $qb->andWhere('r.dateDebut > :end')
               ->setParameter('end', $todayEnd);
        } elseif ($period === 'passes') {
            $qb->andWhere('r.dateDebut < :start')
               ->setParameter('start', $todayStart);
        }

        $rendezVous = $qb->getQuery()->getResult();

        return $this->render('admin/rendezvous/index.html.twig', [
            'rendezVous' => $rendezVous,
            'search' => $search,
            'statut' => $statut,
            'periode' => $period,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_rendezvous_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(RendezVous $rdv): Response
    {
        $patientName = '—';
        $medecinName = '—';

        try {
            $patient = $rdv->getPatient();
            if ($patient) {
                $patientName = $patient->getNomComplet();
            }
        } catch (\Throwable) {
            $patientName = '[patient supprimé]';
        }

        try {
            $medecin = $rdv->getMedecin();
            if ($medecin) {
                $medecinName = $medecin->getNomComplet();
            }
        } catch (\Throwable) {
            $medecinName = '[médecin supprimé]';
        }

        return $this->render('admin/rendezvous/show.html.twig', [
            'rdv' => $rdv,
            'patientName' => $patientName,
            'medecinName' => $medecinName,
        ]);
    }

    #[Route('/{id}/statut', name: 'app_admin_rendezvous_change_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function changeStatus(Request $request, RendezVous $rdv, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $statut = $request->request->get('statut', '');
        $enum = StatutRendezVous::tryFrom($statut);
        if ($enum) {
            $rdv->setStatut($enum);
            $em->flush();
            $this->addFlash('success', 'Statut du rendez-vous mis à jour.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_admin_rendezvous_show', ['id' => $rdv->getId()]);
    }
}

