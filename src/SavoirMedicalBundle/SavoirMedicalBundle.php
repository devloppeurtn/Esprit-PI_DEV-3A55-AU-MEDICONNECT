<?php

namespace App\SavoirMedicalBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SavoirMedicalBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
