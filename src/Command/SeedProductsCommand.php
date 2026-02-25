<?php

namespace App\Command;

use App\Entity\CategorieProduit;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-products', description: 'Seed sample products into the database')]
class SeedProductsCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repoCat = $this->em->getRepository(CategorieProduit::class);
        $existing = $repoCat->findOneBy(['nom' => 'Médicaments']);

        if ($existing) {
            $category = $existing;
            $output->writeln('Using existing category Médicaments');
        } else {
            $category = new CategorieProduit();
            $category->setNom('Médicaments');
            $category->setDescription('Produits pharmaceutiques et soins');
            $category->setImage(null);
            $this->em->persist($category);
            $output->writeln('Created category Médicaments');
        }

        // Product 1: RhinAction
        $p1Repo = $this->em->getRepository(Produit::class);
        $exists1 = $p1Repo->findOneBy(['nom' => 'RhinAction - Nez Bouché']);
        if (!$exists1) {
            $p1 = new Produit();
            $p1->setNom('RhinAction - Nez Bouché');
            $p1->setDescription('Spray nasal pour décongestionner le nez.');
            $p1->setPrix('12.50');
            $p1->setStock(50);
            $p1->setImage('products/product_6.svg');
            $p1->setCategorie($category);
            $this->em->persist($p1);
            $output->writeln('Added product: RhinAction - Nez Bouché');
        } else {
            $output->writeln('Product RhinAction already exists - ensuring image is set');
            $exists1->setImage('products/product_6.svg');
            $this->em->persist($exists1);
        }

        // Product 2: Zarbeil Sirop
        $exists2 = $p1Repo->findOneBy(['nom' => 'Zarbeil - Sirop Toux & Mal de gorge']);
        if (!$exists2) {
            $p2 = new Produit();
            $p2->setNom('Zarbeil - Sirop Toux & Mal de gorge');
            $p2->setDescription('Sirop naturel pour la toux et apaisement de la gorge.');
            $p2->setPrix('9.90');
            $p2->setStock(40);
            $p2->setImage('products/product_7.svg');
            $p2->setCategorie($category);
            $this->em->persist($p2);
            $output->writeln('Added product: Zarbeil - Sirop Toux & Mal de gorge');
        } else {
            $output->writeln('Product Zarbeil already exists - ensuring image is set');
            $exists2->setImage('products/product_7.svg');
            $this->em->persist($exists2);
        }

        $this->em->flush();

        $output->writeln('Seeding complete.');

        return Command::SUCCESS;
    }
}
