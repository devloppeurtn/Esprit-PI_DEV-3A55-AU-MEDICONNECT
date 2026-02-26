<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webauthn\PublicKeyCredentialSource;

#[ORM\Entity]
#[ORM\Table(name: 'webauthn_credential')]
class WebauthnCredential extends PublicKeyCredentialSource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

