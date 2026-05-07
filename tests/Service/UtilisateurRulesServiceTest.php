<?php

namespace App\Tests\Service;

use App\Service\UtilisateurRulesService;
use PHPUnit\Framework\TestCase;

class UtilisateurRulesServiceTest extends TestCase
{
    public function testPasswordLengthInvalidWhenTooShort(): void
    {
        $service = new UtilisateurRulesService();

        self::assertFalse($service->isPasswordLengthValid('1234567'));
    }

    public function testEmailUniquenessIsCaseInsensitive(): void
    {
        $service = new UtilisateurRulesService();

        self::assertFalse($service->isEmailUnique('Test@Example.com', ['test@example.com']));
        self::assertTrue($service->isEmailUnique('unique@example.com', ['test@example.com']));
    }
}
