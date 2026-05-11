<?php

namespace App\Controller\Admin;

use App\Entity\PromoCode;
use App\Repository\PromoCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/promos')]
class PromoAdminController extends AbstractController
{
    #[Route('/', name: 'admin_promos')]
    public function index(PromoCodeRepository $repo): Response
    {
        return $this->render('admin/promos/index.html.twig', [
            'promos' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_promos_new')]
    #[Route('/{id}/edit', name: 'admin_promos_edit', requirements: ['id' => '\d+'])]
    public function form(Request $request, EntityManagerInterface $em, ?PromoCode $promo = null): Response
    {
        $promo = $promo ?? new PromoCode();

        if ($request->isMethod('POST')) {
            $promo->setCode($request->request->get('code', ''));
            $promo->setRate($request->request->get('rate', '0'));
            $promo->setDescription($request->request->get('description') ?: null);
            $promo->setTypeReduction($request->request->get('typeReduction', $promo->getTypeReduction()));
            $promo->setMontantMinimum($request->request->get('montantMinimum', $promo->getMontantMinimum() ?? '0.00'));

            $start = $request->request->get('startAt');
            $end = $request->request->get('endAt');
            $promo->setStartAt($start ? new \DateTime($start) : new \DateTime());
            $promo->setEndAt($end ? new \DateTime($end) : new \DateTime('+1 year'));

            $usage = $request->request->get('usageLimit');
            $promo->setUsageLimit($usage !== '' ? (int)$usage : null);

            $promo->setActive($request->request->getBoolean('active', false));

            $em->persist($promo);
            $em->flush();

            $this->addFlash('success', 'Code promo enregistré.');
            return $this->redirectToRoute('admin_promos');
        }

        return $this->render('admin/promos/form.html.twig', [
            'promo' => $promo,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_promos_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(PromoCode $promo, EntityManagerInterface $em): Response
    {
        $em->remove($promo);
        $em->flush();
        $this->addFlash('success', 'Code promo supprimé.');
        return $this->redirectToRoute('admin_promos');
    }
}
