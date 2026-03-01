<?php

namespace App\Repository;

use App\Entity\WebauthnCredential;
use Doctrine\Persistence\ManagerRegistry;
use Webauthn\Bundle\Repository\PublicKeyCredentialSourceRepository as BasePublicKeyCredentialSourceRepository;

class WebauthnCredentialRepository extends BasePublicKeyCredentialSourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }
}

