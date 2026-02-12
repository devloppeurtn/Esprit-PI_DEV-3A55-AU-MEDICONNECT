<?php

namespace App\Controller\Admin;

use App\Entity\PromoCode;
use App\Repository\PromoCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/promos')]
#[IsGranted('ROLE_ADMIN')]
class PromoAdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_promos', methods: ['GET'])]
    public function index(PromoCodeRepository $repo): Response
    {
        return $this->render('admin/promos/index.html.twig', [
            'promos' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_promos_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_admin_promos_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function form(Request $request, EntityManagerInterface $em, ?PromoCode $promo = null): Response
    {
        $promo = $promo ?? new PromoCode();

        if ($request->isMethod('POST')) {
            $promo->setCode($request->request->get('code', ''));
            $promo->setRate($request->request->get('rate', '0'));

            $start = $request->request->get('startAt');
            $end = $request->request->get('endAt');
            $promo->setStartAt($start ? new \DateTime($start) : null);
            $promo->setEndAt($end ? new \DateTime($end) : null);

            $usage = $request->request->get('usageLimit');
            $promo->setUsageLimit($usage !== '' ? (int)$usage : null);

            $promo->setActive($request->request->getBoolean('active', false));

            $em->persist($promo);
            $em->flush();

            $this->addFlash('success', 'Code promo enregistré.');
            return $this->redirectToRoute('app_admin_promos');
        }

        return $this->render('admin/promos/form.html.twig', [
            'promo' => $promo,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_promos_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(PromoCode $promo, EntityManagerInterface $em): Response
    {
        $em->remove($promo);
        $em->flush();
        $this->addFlash('success', 'Code promo supprimé.');
        return $this->redirectToRoute('app_admin_promos');
    }
}
