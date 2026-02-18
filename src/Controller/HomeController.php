<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use App\Repository\MedecinRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        EvenementRepository $evenementRepository,
        MedecinRepository $medecinRepository
    ): Response
    {
        $events = array_slice($evenementRepository->findValides(), 0, 3);
        $medecinsActifs = $medecinRepository->findMedecinsActifsAvecFiltres(null, null, 'az');
        $homeDoctors = $this->buildHomeDoctors($medecinsActifs, 6);

        $specialites = [];
        foreach ($homeDoctors as $doctor) {
            $specialite = trim((string) $doctor->getSpecialite());
            if ($specialite !== '' && !in_array($specialite, $specialites, true)) {
                $specialites[] = $specialite;
            }
        }

        return $this->render('home/index.html.twig', [
            'events' => $events,
            'homeDoctors' => $homeDoctors,
            'homeDoctorSpecialites' => $specialites,
            'staffImages' => ['staff-2', 'staff-6', 'staff-4', 'staff-8', 'staff-11', 'staff-14', 'staff-1', 'staff-3'],
        ]);
    }

    #[Route('/medecins', name: 'app_medecins_public', methods: ['GET'])]
    public function medecinsPublic(Request $request, MedecinRepository $medecinRepository): Response
    {
        $nom = trim((string) $request->query->get('nom', ''));
        $specialite = trim((string) $request->query->get('specialite', ''));
        $tri = (string) $request->query->get('tri', 'az');
        if (!in_array($tri, ['az', 'za'], true)) {
            $tri = 'az';
        }

        $medecins = $medecinRepository->findMedecinsActifsAvecFiltres(
            $nom === '' ? null : $nom,
            $specialite === '' ? null : $specialite,
            $tri
        );

        return $this->render('home/medecins_public.html.twig', [
            'medecins' => $medecins,
            'specialites' => $medecinRepository->findSpecialitesDistinctes(),
            'staffImages' => ['staff-1', 'staff-2', 'staff-3', 'staff-4', 'staff-5', 'staff-6', 'staff-7', 'staff-8', 'staff-10', 'staff-11', 'staff-14'],
            'filtres' => ['nom' => $nom, 'specialite' => $specialite, 'tri' => $tri],
        ]);
    }

    private function buildHomeDoctors(array $medecins, int $limit): array
    {
        $selected = [];
        $fallback = [];
        $specialites = [];

        foreach ($medecins as $medecin) {
            $specialite = strtolower(trim((string) $medecin->getSpecialite()));

            if ($specialite !== '' && !isset($specialites[$specialite])) {
                $specialites[$specialite] = true;
                $selected[] = $medecin;

                if (count($selected) >= $limit) {
                    return $selected;
                }
                continue;
            }

            $fallback[] = $medecin;
        }

        foreach ($fallback as $medecin) {
            $selected[] = $medecin;
            if (count($selected) >= $limit) {
                break;
            }
        }

        return $selected;
    }
}
