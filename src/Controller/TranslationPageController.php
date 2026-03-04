<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/translation')]
#[IsGranted('ROLE_USER')]
class TranslationPageController extends AbstractController
{
    #[Route('/', name: 'app_translation_index')]
    public function index()
    {
        return $this->render('translation/index.html.twig');
    }
}
