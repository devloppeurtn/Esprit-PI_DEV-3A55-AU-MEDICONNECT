<?php

namespace App\Controller;

<<<<<<< HEAD
=======
<<<<<<< HEAD
use App\Entity\RoleUtilisateur;
use App\Entity\StatutCompte;
use App\Entity\Utilisateur;
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use App\Entity\Consultation;
use App\Entity\DocumentPatient;
use App\Entity\DossierMedical;
use App\Entity\Evenement;
use App\Entity\MedicamentActuel;
use App\Entity\Ordonnance;
use App\Entity\RapportMedical;
use App\Entity\RendezVous;
use App\Entity\RoleUtilisateur;
use App\Entity\StatutCompte;
use App\Entity\StatutRendezVous;
use App\Entity\Utilisateur;
use App\Enum\StatutEvenement;
use App\Repository\EvenementRepository;
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
<<<<<<< HEAD
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
=======
<<<<<<< HEAD
=======
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
<<<<<<< HEAD
        private EvenementRepository $evenementRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
=======
<<<<<<< HEAD
=======
        private EvenementRepository $evenementRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    ) {
    }

    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $userRepo = $this->entityManager->getRepository(Utilisateur::class);
        $totalUsers = $userRepo->count([]);
        $countPatients = $userRepo->count(['role' => RoleUtilisateur::PATIENT]);
        $countMedecins = $userRepo->count(['role' => RoleUtilisateur::MEDECIN]);
        $countSecretaires = $userRepo->count(['role' => RoleUtilisateur::SECRETAIRE]);
        $countAdmins = $userRepo->count(['role' => RoleUtilisateur::ADMIN]);
        $countParticipations = $userRepo->count(['role' => RoleUtilisateur::PARTICIPATION]);
<<<<<<< HEAD
        $countOrganisateurs = $userRepo->count(['role' => RoleUtilisateur::ORGANISATEUR]);
        $pendingEvenements = $this->evenementRepository->findPending();
=======
<<<<<<< HEAD
=======
        $countOrganisateurs = $userRepo->count(['role' => RoleUtilisateur::ORGANISATEUR]);
        $pendingEvenements = $this->evenementRepository->findPending();
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1

        return $this->render('admin/dashboard/index.html.twig', [
            'totalUsers' => $totalUsers,
            'countPatients' => $countPatients,
            'countMedecins' => $countMedecins,
            'countSecretaires' => $countSecretaires,
            'countAdmins' => $countAdmins,
            'countParticipations' => $countParticipations,
<<<<<<< HEAD
=======
<<<<<<< HEAD
        ]);
    }

=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            'countOrganisateurs' => $countOrganisateurs,
            'pendingEvenements' => $pendingEvenements,
        ]);
    }

    #[Route('/evenements', name: 'app_admin_evenements', methods: ['GET'])]
    public function evenements(): Response
    {
        return $this->render('admin/evenements/index.html.twig', []);
    }

    #[Route('/evenements/list', name: 'app_admin_evenements_list', methods: ['GET'])]
    public function evenementsList(Request $request): JsonResponse
    {
        $statut = $request->query->get('statut'); // '', 'EN_ATTENTE', 'VALIDE', 'REFUSE'
        $list = $this->evenementRepository->findAllForAdmin($statut === '' || $statut === null ? null : $statut);
        $data = [];
        foreach ($list as $e) {
            $o = $e->getOrganisateur();
            $data[] = [
                'id' => $e->getId(),
                'title' => $e->getTitle(),
                'contentExcerpt' => $e->getContent() ? mb_substr(strip_tags($e->getContent()), 0, 100) . (mb_strlen($e->getContent()) > 100 ? '...' : '') : '',
                'eventDate' => $e->getEventDate()?->format('d/m/Y') ?? '—',
                'createdAt' => $e->getCreatedAt()->format('d/m/Y H:i'),
                'statut' => $e->getStatut()->value,
                'tokenAccepter' => $this->csrfTokenManager->getToken('accepter' . $e->getId())->getValue(),
                'tokenRefuser' => $this->csrfTokenManager->getToken('refuser' . $e->getId())->getValue(),
                'organisateur' => $o ? [
                    'id' => $o->getId(),
                    'nomComplet' => $o->getNomComplet(),
                    'email' => $o->getEmail(),
                    'photo' => $o->getPhoto(),
                ] : null,
            ];
        }
        return new JsonResponse(['success' => true, 'events' => $data]);
    }

    #[Route('/evenements/{id}/accepter', name: 'app_admin_evenement_accepter', methods: ['POST'])]
    public function evenementAccepter(Request $request, Evenement $evenement): Response|JsonResponse
    {
        if ($evenement->getStatut() !== StatutEvenement::EN_ATTENTE) {
            if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
                return new JsonResponse(['success' => false, 'message' => 'Cet événement n\'est plus en attente.'], 400);
            }
            $this->addFlash('warning', 'Cet événement n\'est plus en attente.');
            return $this->redirectToRoute('app_admin_evenements');
        }
        if (!$this->isCsrfTokenValid('accepter'.$evenement->getId(), $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
                return new JsonResponse(['success' => false, 'message' => 'Jeton invalide.'], 400);
            }
            return $this->redirectToRoute('app_admin_evenements');
        }
        $evenement->setStatut(StatutEvenement::VALIDE);
        $evenement->setApprouvePar($this->getUser());
        $evenement->setApprouveAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
            return new JsonResponse(['success' => true, 'message' => 'Événement « ' . $evenement->getTitle() . ' » a été accepté et publié.']);
        }
        $this->addFlash('success', 'Événement « ' . $evenement->getTitle() . ' » a été accepté et est maintenant publié.');
        return $this->redirectToRoute('app_admin_evenements');
    }

    #[Route('/evenements/{id}/refuser', name: 'app_admin_evenement_refuser', methods: ['POST'])]
    public function evenementRefuser(Request $request, Evenement $evenement): Response|JsonResponse
    {
        if ($evenement->getStatut() !== StatutEvenement::EN_ATTENTE) {
            if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
                return new JsonResponse(['success' => false, 'message' => 'Cet événement n\'est plus en attente.'], 400);
            }
            $this->addFlash('warning', 'Cet événement n\'est plus en attente.');
            return $this->redirectToRoute('app_admin_evenements');
        }
        if (!$this->isCsrfTokenValid('refuser'.$evenement->getId(), $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
                return new JsonResponse(['success' => false, 'message' => 'Jeton invalide.'], 400);
            }
            return $this->redirectToRoute('app_admin_evenements');
        }
        $evenement->setStatut(StatutEvenement::REFUSE);
        $evenement->setApprouvePar($this->getUser());
        $evenement->setApprouveAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        if ($request->isXmlHttpRequest() || $request->headers->get('Accept') === 'application/json') {
            return new JsonResponse(['success' => true, 'message' => 'Événement « ' . $evenement->getTitle() . ' » a été refusé.']);
        }
        $this->addFlash('success', 'Événement « ' . $evenement->getTitle() . ' » a été refusé.');
        return $this->redirectToRoute('app_admin_evenements');
    }

<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[Route('/stats', name: 'app_admin_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $userRepo = $this->entityManager->getRepository(Utilisateur::class);
<<<<<<< HEAD
=======
<<<<<<< HEAD
        return new JsonResponse([
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $em = $this->entityManager;

        $rdvRepo = $em->getRepository(RendezVous::class);
        $now = new \DateTimeImmutable('now');
        $startOfDay = $now->setTime(0, 0, 0);
        $endOfDay = $now->setTime(23, 59, 59);

        // Rendez-vous aujourd'hui et à venir
        $qbToday = $rdvRepo->createQueryBuilder('r_today')
            ->select('COUNT(r_today.id)')
            ->andWhere('r_today.dateDebut BETWEEN :start AND :end')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay);
        $rdvToday = (int) $qbToday->getQuery()->getSingleScalarResult();

        $qbUpcoming = $rdvRepo->createQueryBuilder('r_up')
            ->select('COUNT(r_up.id)')
            ->andWhere('r_up.dateDebut > :endNow')
            ->setParameter('endNow', $endOfDay);
        $rdvUpcoming = (int) $qbUpcoming->getQuery()->getSingleScalarResult();

        return new JsonResponse([
            // Utilisateurs
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            'totalUsers' => $userRepo->count([]),
            'countPatients' => $userRepo->count(['role' => RoleUtilisateur::PATIENT]),
            'countMedecins' => $userRepo->count(['role' => RoleUtilisateur::MEDECIN]),
            'countSecretaires' => $userRepo->count(['role' => RoleUtilisateur::SECRETAIRE]),
            'countAdmins' => $userRepo->count(['role' => RoleUtilisateur::ADMIN]),
            'countParticipations' => $userRepo->count(['role' => RoleUtilisateur::PARTICIPATION]),
<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1

            // Dossier médical / consultations
            'countDossiers' => $em->getRepository(DossierMedical::class)->count([]),
            'countConsultations' => $em->getRepository(Consultation::class)->count([]),
            'countOrdonnances' => $em->getRepository(Ordonnance::class)->count([]),
            'countMedicamentsActuels' => $em->getRepository(MedicamentActuel::class)->count([]),
            'countRapportsMedicaux' => $em->getRepository(RapportMedical::class)->count([]),
            'countDocumentsPatient' => $em->getRepository(DocumentPatient::class)->count([]),

            // Rendez-vous
            'countRendezVous' => $rdvRepo->count([]),
            'countRdvToday' => $rdvToday,
            'countRdvUpcoming' => $rdvUpcoming,
            'countRdvEnAttente' => $rdvRepo->count(['statut' => StatutRendezVous::EN_ATTENTE]),
            'countRdvConfirmes' => $rdvRepo->count(['statut' => StatutRendezVous::CONFIRME]),
            'countRdvAnnules' => $rdvRepo->count(['statut' => StatutRendezVous::ANNULE]),
            'countRdvTermines' => $rdvRepo->count(['statut' => StatutRendezVous::TERMINE]),
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        ]);
    }

    #[Route('/charts', name: 'app_admin_charts', methods: ['GET'])]
    public function charts(Request $request): JsonResponse
    {
        $period = $request->query->get('period', '7d');
        $isMonth = $period === '30d';

        $conn = $this->entityManager->getConnection();
        $daysCount = $isMonth ? 30 : 7;
        $startDate = (new \DateTimeImmutable("-{$daysCount} days"))->format('Y-m-d');

        $sql = "SELECT DATE(date_creation) as day, COUNT(*) as count 
                FROM utilisateur 
                WHERE date_creation >= :start 
                GROUP BY DATE(date_creation) 
                ORDER BY day";
        $result = $conn->executeQuery($sql, ['start' => $startDate])->fetchAllAssociative();

        $daysMap = [];
        for ($i = $daysCount - 1; $i >= 0; $i--) {
            $d = (new \DateTimeImmutable("-{$i} days"))->format('Y-m-d');
            $daysMap[$d] = 0;
        }
        foreach ($result as $row) {
            $daysMap[$row['day']] = (int) $row['count'];
        }

        $labelFormat = $isMonth ? 'd/m' : 'd/m';
        $userGrowth = [
            'labels' => array_map(fn ($d) => (new \DateTimeImmutable($d))->format($labelFormat), array_keys($daysMap)),
            'data' => array_values($daysMap),
        ];

        // Répartition par type
        $userRepo = $this->entityManager->getRepository(Utilisateur::class);
        $distribution = [
            'labels' => ['Patients', 'Médecins', 'Secrétaires', 'Administrateurs', 'Participations'],
            'data' => [
                $userRepo->count(['role' => RoleUtilisateur::PATIENT]),
                $userRepo->count(['role' => RoleUtilisateur::MEDECIN]),
                $userRepo->count(['role' => RoleUtilisateur::SECRETAIRE]),
                $userRepo->count(['role' => RoleUtilisateur::ADMIN]),
                $userRepo->count(['role' => RoleUtilisateur::PARTICIPATION]),
            ],
        ];

        // Overview (courbes) - 7D, 30D, 90D, 1Y
        $overviewPeriod = $request->query->get('overviewPeriod', '1y');
        $overview = $this->getOverviewData($conn, $overviewPeriod);

        return new JsonResponse([
            'userGrowth' => $userGrowth,
            'distribution' => $distribution,
            'overview' => $overview,
        ]);
    }

    private function getOverviewData($conn, string $period): array
    {
        $now = new \DateTimeImmutable();
        $labels = [];
        $patientsData = [];
        $medecinsData = [];

        if ($period === '1y') {
            for ($m = 11; $m >= 0; $m--) {
                $d = $now->modify("-{$m} months");
                $start = $d->format('Y-m-01');
                $end = $d->format('Y-m-t');
                $labels[] = $d->format('M');

                $sql = "SELECT role, COUNT(*) as cnt FROM utilisateur 
                        WHERE date_creation >= :start AND date_creation <= :end 
                        GROUP BY role";
                $r = $conn->executeQuery($sql, ['start' => $start, 'end' => $end . ' 23:59:59'])->fetchAllAssociative();
                $byRole = ['PATIENT' => 0, 'MEDECIN' => 0];
                foreach ($r as $row) {
                    $byRole[$row['role']] = (int) $row['cnt'];
                }
                $patientsData[] = $byRole['PATIENT'];
                $medecinsData[] = $byRole['MEDECIN'];
            }
        } elseif ($period === '90d') {
            for ($w = 12; $w >= 0; $w--) {
                $start = $now->modify('-' . ($w + 1) . ' weeks')->format('Y-m-d');
                $end = $w === 0
                    ? $now->format('Y-m-d')
                    : $now->modify("-{$w} weeks")->modify('-1 day')->format('Y-m-d');
                $labels[] = 'S' . (13 - $w);

                $sql = "SELECT role, COUNT(*) as cnt FROM utilisateur 
                        WHERE date_creation >= :start AND date_creation <= :end 
                        GROUP BY role";
                $r = $conn->executeQuery($sql, ['start' => $start, 'end' => $end . ' 23:59:59'])->fetchAllAssociative();
                $byRole = ['PATIENT' => 0, 'MEDECIN' => 0];
                foreach ($r as $row) {
                    $byRole[$row['role']] = (int) $row['cnt'];
                }
                $patientsData[] = $byRole['PATIENT'];
                $medecinsData[] = $byRole['MEDECIN'];
            }
        } else {
            $days = $period === '7d' ? 7 : 30;
            for ($i = $days - 1; $i >= 0; $i--) {
                $d = $now->modify("-{$i} days")->format('Y-m-d');
                $labels[] = (new \DateTimeImmutable($d))->format('d/m');

                $sql = "SELECT role, COUNT(*) as cnt FROM utilisateur 
                        WHERE DATE(date_creation) = :day GROUP BY role";
                $r = $conn->executeQuery($sql, ['day' => $d])->fetchAllAssociative();
                $byRole = ['PATIENT' => 0, 'MEDECIN' => 0];
                foreach ($r as $row) {
                    $byRole[$row['role']] = (int) $row['cnt'];
                }
                $patientsData[] = $byRole['PATIENT'];
                $medecinsData[] = $byRole['MEDECIN'];
            }
        }

        return [
            'labels' => $labels,
            'patients' => $patientsData,
            'medecins' => $medecinsData,
        ];
    }

    #[Route('/users', name: 'app_admin_users', methods: ['GET'])]
    public function users(): Response
    {
        $userRepo = $this->entityManager->getRepository(Utilisateur::class);
        $users = $userRepo->findBy([], ['dateCreation' => 'DESC'], 200);

        $totalUsers = $userRepo->count([]);
        $actifs = $userRepo->count(['statut' => StatutCompte::ACTIF]);
        $now = new \DateTimeImmutable();
        $debutMois = $now->format('Y-m-01');
        $finMois = $now->format('Y-m-t');
        $newThisMonth = $userRepo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.dateCreation >= :start')
            ->andWhere('u.dateCreation <= :end')
            ->setParameter('start', $debutMois . ' 00:00:00')
            ->setParameter('end', $finMois . ' 23:59:59')
            ->getQuery()
            ->getSingleScalarResult();

        $roleCounts = [
            'PATIENT' => $userRepo->count(['role' => RoleUtilisateur::PATIENT]),
            'MEDECIN' => $userRepo->count(['role' => RoleUtilisateur::MEDECIN]),
            'SECRETAIRE' => $userRepo->count(['role' => RoleUtilisateur::SECRETAIRE]),
            'ADMIN' => $userRepo->count(['role' => RoleUtilisateur::ADMIN]),
            'PARTICIPATION' => $userRepo->count(['role' => RoleUtilisateur::PARTICIPATION]),
        ];

        $conn = $this->entityManager->getConnection();
        $sql = "SELECT DATE(date_creation) as day, COUNT(*) as cnt FROM utilisateur WHERE date_creation >= :start GROUP BY DATE(date_creation) ORDER BY day";
        $startDate = (new \DateTimeImmutable('-30 days'))->format('Y-m-d');
        $growthRows = $conn->executeQuery($sql, ['start' => $startDate])->fetchAllAssociative();
        $growthMap = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = (new \DateTimeImmutable("-{$i} days"))->format('Y-m-d');
            $growthMap[$d] = 0;
        }
        foreach ($growthRows as $r) {
            $growthMap[$r['day']] = (int) $r['cnt'];
        }
        $userGrowth = [
            'labels' => array_map(fn ($d) => (new \DateTimeImmutable($d))->format('d/m'), array_keys($growthMap)),
            'data' => array_values($growthMap),
        ];

        $usersData = array_map(fn (Utilisateur $u) => [
            'id' => $u->getId(),
            'nomComplet' => $u->getNomComplet(),
            'email' => $u->getEmail(),
            'role' => $u->getRole()?->value ?? '',
            'statut' => $u->getStatut()?->value ?? '',
<<<<<<< HEAD
=======
<<<<<<< HEAD
            'dateCreation' => $u->getDateCreation()?->format('Y-m-d'),
            'derniereConnexion' => $u->getDerniereConnexion()?->format('d/m/Y H:i') ?? '—',
            'lastActive' => $u->getDerniereConnexion() ? $this->formatLastActive($u->getDerniereConnexion()) : '—',
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            'emailVerified' => $u->isEmailVerified(),
            'dateCreation' => $u->getDateCreation()?->format('Y-m-d'),
            'derniereConnexion' => $u->getDerniereConnexion()?->format('d/m/Y H:i') ?? '—',
            'lastActive' => $u->getDerniereConnexion() ? $this->formatLastActive($u->getDerniereConnexion()) : '—',
            'photo' => $u->getPhoto(),
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            'avatar' => '', // sera remplacé par l'asset dans le template
        ], $users);

        $growth7d = $this->getGrowthData($conn, 7);
        $growth30d = $this->getGrowthData($conn, 30);
        $growth90d = $this->getGrowthData($conn, 90);

        $roleLabels = ['Patients', 'Médecins', 'Secrétaires', 'Admins', 'Participations'];
        $roleColors = ['#10b981', '#06b6d4', '#f59e0b', '#ef4444', '#64748b'];
        $departmentStats = [];
        $total = array_sum($roleCounts);
        foreach (['PATIENT', 'MEDECIN', 'SECRETAIRE', 'ADMIN', 'PARTICIPATION'] as $i => $r) {
            $c = $roleCounts[$r] ?? 0;
            $departmentStats[] = [
                'name' => $roleLabels[$i],
                'count' => $c,
                'percentage' => $total > 0 ? round(($c / $total) * 100) : 0,
                'color' => $roleColors[$i],
            ];
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'usersData' => $usersData,
            'userStats' => [
                'total' => $totalUsers,
                'actifs' => $actifs,
                'newThisMonth' => (int) $newThisMonth,
                'activePercentage' => $totalUsers > 0 ? round(($actifs / $totalUsers) * 100) : 0,
            ],
            'roleCounts' => $roleCounts,
            'departmentStats' => $departmentStats,
            'userGrowth' => $userGrowth,
            'userGrowth7d' => $growth7d,
            'userGrowth30d' => $growth30d,
            'userGrowth90d' => $growth90d,
            'recentActivities' => $this->getRecentActivities(),
            'systemAlerts' => $this->getSystemAlerts(),
            'pendingAdminCount' => $userRepo->count(['role' => RoleUtilisateur::ADMIN, 'statut' => StatutCompte::SUSPENDU]),
        ]);
    }

    #[Route('/users/{id}/profile', name: 'app_admin_user_profile', methods: ['GET'])]
    public function userProfile(int $id): Response
    {
        $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/users/profile.html.twig', [
            'user' => $user,
            'is_admin_view' => true,
        ]);
    }

<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[Route('/users/{id}/profile/data', name: 'app_admin_user_profile_data', methods: ['GET'])]
    public function userProfileData(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur introuvable'], 404);
        }

        $userData = [
            'id' => $user->getId(),
            'nomComplet' => $user->getNomComplet(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()?->value ?? '',
            'statut' => $user->getStatut()?->value ?? '',
            'photo' => $user->getPhoto(),
            'emailVerified' => $user->isEmailVerified(),
            'dateCreation' => $user->getDateCreation()?->format('d/m/Y H:i') ?? '',
            'derniereConnexion' => $user->getDerniereConnexion()?->format('d/m/Y H:i') ?? '—',
        ];

        // Données spécifiques selon le rôle
        if ($user instanceof Patient) {
            $userData['telephone'] = $user->getTelephone() ?? '';
            $userData['dateNaissance'] = $user->getDateNaissance()?->format('d/m/Y') ?? '';
            $userData['adresse'] = $user->getAdresse() ?? '';
        } elseif ($user instanceof Medecin) {
            $userData['specialite'] = $user->getSpecialite() ?? '';
            $userData['adresseCabinet'] = $user->getAdresseCabinet() ?? '';
            $userData['numeroLicence'] = $user->getNumeroLicence() ?? '';
            $userData['telephone'] = $user->getTelephone() ?? '';
        } elseif ($user instanceof Secretaire) {
            $userData['telephone'] = $user->getTelephone() ?? '';
        } elseif ($user instanceof \App\Entity\Organisateur) {
            $userData['telephone'] = $user->getTelephone() ?? '';
        }
        
        // Ajouter le téléphone pour Admin et Participation si nécessaire
        if ($user instanceof \App\Entity\Admin || $user instanceof \App\Entity\Participation) {
            $userData['telephone'] = $user->getTelephone() ?? '';
        } elseif ($user instanceof Participation) {
            $userData['roleDansEvenement'] = $user->getRoleDansEvenement()?->value ?? '';
            $userData['presenceConfirmee'] = $user->isPresenceConfirmee();
        }

        return new JsonResponse(['success' => true, 'user' => $userData]);
    }

<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[Route('/users/export', name: 'app_admin_users_export', methods: ['GET'])]
    public function exportUsers(Request $request): StreamedResponse
    {
        $userRepo = $this->entityManager->getRepository(Utilisateur::class);
        $users = $userRepo->findBy([], ['dateCreation' => 'DESC']);

        $response = new StreamedResponse(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['ID', 'Nom', 'Email', 'Rôle', 'Statut', 'Date inscription', 'Dernière connexion'], ';');
            foreach ($users as $u) {
                fputcsv($handle, [
                    $u->getId(),
                    $u->getNomComplet(),
                    $u->getEmail(),
                    $u->getRole()?->value ?? '',
                    $u->getStatut()?->value ?? '',
                    $u->getDateCreation()?->format('d/m/Y') ?? '',
                    $u->getDerniereConnexion()?->format('d/m/Y H:i') ?? '',
                ], ';');
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="utilisateurs-' . date('Y-m-d') . '.csv"');
        return $response;
    }

<<<<<<< HEAD
=======
<<<<<<< HEAD
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[Route('/users/export/status', name: 'app_admin_users_export_status', methods: ['GET'])]
    public function exportUsersStatus(): JsonResponse
    {
        $userRepo = $this->entityManager->getRepository(Utilisateur::class);
        $totalUsers = $userRepo->count([]);
        
        return new JsonResponse([
            'success' => true,
            'totalUsers' => $totalUsers,
            'exportUrl' => $this->generateUrl('app_admin_users_export'),
            'message' => 'Export prêt. Le téléchargement va commencer...'
        ]);
    }

<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
    #[Route('/users/create', name: 'app_admin_users_create', methods: ['GET', 'POST'])]
    public function createUser(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        if (empty($data)) {
            return new JsonResponse(['success' => false, 'error' => 'Données invalides'], 400);
        }

<<<<<<< HEAD
        $email = is_string($data['email'] ?? '') ? trim($data['email']) : '';
        $nomComplet = is_string($data['nomComplet'] ?? '') ? strip_tags(trim($data['nomComplet'])) : '';
=======
<<<<<<< HEAD
        $email = $data['email'] ?? '';
        $nomComplet = $data['nomComplet'] ?? '';
=======
        $email = is_string($data['email'] ?? '') ? trim($data['email']) : '';
        $nomComplet = is_string($data['nomComplet'] ?? '') ? strip_tags(trim($data['nomComplet'])) : '';
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $roleValue = $data['role'] ?? 'PATIENT';
        $statutValue = $data['statut'] ?? 'ACTIF';
        $password = $data['password'] ?? bin2hex(random_bytes(8));

<<<<<<< HEAD
=======
<<<<<<< HEAD
        if (!$email || !$nomComplet) {
            return new JsonResponse(['success' => false, 'error' => 'Email et nom requis'], 400);
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        if ($email === '') {
            return new JsonResponse(['success' => false, 'error' => 'Email requis'], 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['success' => false, 'error' => 'Email invalide'], 400);
        }
        if (mb_strlen($email) > 180) {
            return new JsonResponse(['success' => false, 'error' => 'Email trop long'], 400);
        }
        if ($nomComplet === '') {
            return new JsonResponse(['success' => false, 'error' => 'Nom requis'], 400);
        }
        if (mb_strlen($nomComplet) < 2 || mb_strlen($nomComplet) > 255) {
            return new JsonResponse(['success' => false, 'error' => 'Nom invalide (2 à 255 caractères)'], 400);
        }
        if (is_string($password) && mb_strlen($password) > 0 && mb_strlen($password) < 6) {
            return new JsonResponse(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        }

        $existing = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return new JsonResponse(['success' => false, 'error' => 'Cet email est déjà utilisé'], 400);
        }

        try {
            $role = RoleUtilisateur::from($roleValue);
            $statut = StatutCompte::from($statutValue);
        } catch (\ValueError) {
            return new JsonResponse(['success' => false, 'error' => 'Rôle ou statut invalide'], 400);
        }

        $user = match ($role) {
            RoleUtilisateur::ADMIN => new \App\Entity\Admin(),
            RoleUtilisateur::PATIENT => new \App\Entity\Patient(),
            RoleUtilisateur::MEDECIN => new \App\Entity\Medecin(),
            RoleUtilisateur::SECRETAIRE => new \App\Entity\Secretaire(),
            RoleUtilisateur::PARTICIPATION => new \App\Entity\Participation(),
        };

        $user->setEmail($email);
        $user->setNomComplet($nomComplet);
        $user->setRole($role);
        $user->setStatut($statut);
<<<<<<< HEAD
        $user->setEmailVerified(true); // Créé par admin = email considéré vérifié
=======
<<<<<<< HEAD
=======
        $user->setEmailVerified(true); // Créé par admin = email considéré vérifié
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'id' => $user->getId()]);
    }

    #[Route('/users/{id}/update', name: 'app_admin_users_update', methods: ['POST'])]
    public function updateUser(int $id, Request $request): Response
    {
        $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        if (isset($data['nomComplet'])) {
<<<<<<< HEAD
=======
<<<<<<< HEAD
            $user->setNomComplet($data['nomComplet']);
        }
        if (isset($data['email'])) {
            $other = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $data['email']]);
            if ($other && $other->getId() !== $id) {
                return new JsonResponse(['success' => false, 'error' => 'Cet email est déjà utilisé'], 400);
            }
            $user->setEmail($data['email']);
        }
        if (isset($data['role'])) {
            $user->setRole(RoleUtilisateur::from($data['role']));
        }
        if (isset($data['statut'])) {
            $user->setStatut(StatutCompte::from($data['statut']));
        }
        if (!empty($data['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
=======
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
            $nom = is_string($data['nomComplet']) ? strip_tags(trim($data['nomComplet'])) : '';
            if ($nom === '' || mb_strlen($nom) < 2 || mb_strlen($nom) > 255) {
                return new JsonResponse(['success' => false, 'error' => 'Nom invalide (2 à 255 caractères)'], 400);
            }
            $user->setNomComplet($nom);
        }
        if (isset($data['email'])) {
            $email = is_string($data['email']) ? trim($data['email']) : '';
            if ($email === '') {
                return new JsonResponse(['success' => false, 'error' => 'Email requis'], 400);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
                return new JsonResponse(['success' => false, 'error' => 'Email invalide'], 400);
            }
            $other = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if ($other && $other->getId() !== $id) {
                return new JsonResponse(['success' => false, 'error' => 'Cet email est déjà utilisé'], 400);
            }
            $user->setEmail($email);
        }
        if (isset($data['role'])) {
            try {
                $user->setRole(RoleUtilisateur::from($data['role']));
            } catch (\ValueError) {
                return new JsonResponse(['success' => false, 'error' => 'Rôle invalide'], 400);
            }
        }
        if (isset($data['statut'])) {
            try {
                $user->setStatut(StatutCompte::from($data['statut']));
            } catch (\ValueError) {
                return new JsonResponse(['success' => false, 'error' => 'Statut invalide'], 400);
            }
        }
        if (!empty($data['password'])) {
            $pw = is_string($data['password']) ? $data['password'] : '';
            if (mb_strlen($pw) < 6) {
                return new JsonResponse(['success' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
            }
            $user->setPassword($this->passwordHasher->hashPassword($user, $pw));
<<<<<<< HEAD
=======
>>>>>>> isramedi
>>>>>>> 4f714e473f4c6306d8cd13fadaae1828cd26d7f1
        }

        $this->entityManager->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/users/bulk-update', name: 'app_admin_users_bulk_update', methods: ['POST'])]
    public function bulkUpdate(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();
        $ids = $data['ids'] ?? [];
        $action = $data['action'] ?? '';
        if (empty($ids) || !in_array($action, ['activate', 'deactivate'], true)) {
            return new JsonResponse(['success' => false, 'error' => 'Requête invalide'], 400);
        }
        $repo = $this->entityManager->getRepository(Utilisateur::class);
        foreach ($ids as $id) {
            $user = $repo->find((int) $id);
            if ($user) {
                $user->setStatut($action === 'activate' ? StatutCompte::ACTIF : StatutCompte::SUSPENDU);
            }
        }
        $this->entityManager->flush();
        return new JsonResponse(['success' => true]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function deleteUser(int $id): Response
    {
        $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Utilisateur introuvable'], 404);
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        return new JsonResponse(['success' => true]);
    }

    private function formatLastActive(\DateTimeImmutable $date): string
    {
        $diff = (new \DateTimeImmutable())->getTimestamp() - $date->getTimestamp();
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . ' h';
        if ($diff < 604800) return floor($diff / 86400) . ' j';
        return $date->format('d/m/Y');
    }

    private function getGrowthData($conn, int $days): array
    {
        $startDate = (new \DateTimeImmutable("-{$days} days"))->format('Y-m-d');
        $sql = "SELECT DATE(date_creation) as day, COUNT(*) as cnt FROM utilisateur WHERE date_creation >= :start GROUP BY DATE(date_creation) ORDER BY day";
        $rows = $conn->executeQuery($sql, ['start' => $startDate])->fetchAllAssociative();
        $map = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = (new \DateTimeImmutable("-{$i} days"))->format('Y-m-d');
            $map[$d] = 0;
        }
        foreach ($rows as $r) {
            $map[$r['day']] = (int) $r['cnt'];
        }
        return [
            'labels' => array_map(fn ($d) => (new \DateTimeImmutable($d))->format('d/m'), array_keys($map)),
            'data' => array_values($map),
        ];
    }

    private function getRecentActivities(): array
    {
        $repo = $this->entityManager->getRepository(Utilisateur::class);
        $recent = $repo->findBy([], ['dateCreation' => 'DESC'], 5);
        $activities = [];
        foreach ($recent as $u) {
            $activities[] = [
                'id' => $u->getId(),
                'user' => $u->getNomComplet(),
                'action' => ' s\'est inscrit',
                'time' => $u->getDateCreation() ? 'Il y a ' . $this->formatLastActive($u->getDateCreation()) : '—',
                'type' => 'register',
                'icon' => 'person-plus',
                'details' => 'Inscription sur MediConnect',
            ];
        }
        return $activities;
    }

    private function getSystemAlerts(): array
    {
        return [
            ['id' => 1, 'title' => 'Système opérationnel', 'message' => 'Tous les services fonctionnent normalement', 'type' => 'success', 'time' => 'Maintenant'],
        ];
    }
}
