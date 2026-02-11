<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\EvenementRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $events = $evenementRepository->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.eventDate', 'ASC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('home/index.html.twig', [
            'events' => $events,
        ]);
    }
}
