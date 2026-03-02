<?php

namespace App\Repository;

use App\Entity\AvisMedecin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AvisMedecin>
 *
 * @method AvisMedecin|null find($id, $lockMode = null, $lockVersion = null)
 * @method AvisMedecin|null findOneBy(array $criteria, array $orderBy = null)
 * @method AvisMedecin[]    findAll()
 * @method AvisMedecin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AvisMedecinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AvisMedecin::class);
    }
}
