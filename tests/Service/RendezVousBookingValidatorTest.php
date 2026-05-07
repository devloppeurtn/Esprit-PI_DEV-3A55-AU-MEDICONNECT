<?php

namespace App\Tests\Service;

use App\Entity\Medecin;
use App\Entity\PlanningMedecin;
use App\Service\DisponibiliteService;
use App\Service\RendezVousBookingValidator;
use PHPUnit\Framework\TestCase;

class RendezVousBookingValidatorTest extends TestCase
{
    public function testRejectsWhenNoPlanning(): void
    {
        $dispo = $this->createMock(DisponibiliteService::class);
        $validator = new RendezVousBookingValidator($dispo);

        $medecin = new Medecin();
        $result = $validator->validateRequestedSlot($medecin, new \DateTimeImmutable('2026-03-04 10:00'));

        $this->assertFalse($result['ok']);
        $this->assertSame('Demande refusee: planning du medecin non configure.', $result['message']);
        $this->assertNull($result['endAt']);
    }

    public function testRejectsWhenDayClosedOrOutsideHours(): void
    {
        $dispo = $this->createMock(DisponibiliteService::class);
        $dispo->method('getAgendaGrid')->willReturn([]);

        $validator = new RendezVousBookingValidator($dispo);
        $medecin = new Medecin();
        $medecin->setPlanning(new PlanningMedecin());

        $result = $validator->validateRequestedSlot($medecin, new \DateTimeImmutable('2026-03-04 10:00'));

        $this->assertFalse($result['ok']);
        $this->assertSame('Demande refusee: jour non travaille ou hors horaires du medecin.', $result['message']);
        $this->assertNull($result['endAt']);
    }

    public function testRejectsWhenSlotOccupied(): void
    {
        $start = new \DateTimeImmutable('2026-03-04 10:00');
        $end = new \DateTimeImmutable('2026-03-04 10:30');

        $dispo = $this->createMock(DisponibiliteService::class);
        $dispo->method('getAgendaGrid')->willReturn([
            [
                'de' => $start,
                'a' => $end,
                'type' => 'occupe',
            ],
        ]);

        $validator = new RendezVousBookingValidator($dispo);
        $medecin = new Medecin();
        $medecin->setPlanning(new PlanningMedecin());

        $result = $validator->validateRequestedSlot($medecin, $start);

        $this->assertFalse($result['ok']);
        $this->assertSame('Demande refusee: medecin deja occupe sur ce creneau.', $result['message']);
        $this->assertNull($result['endAt']);
    }

    public function testAcceptsWhenSlotFree(): void
    {
        $start = new \DateTimeImmutable('2026-03-04 10:00');
        $end = new \DateTimeImmutable('2026-03-04 10:30');

        $dispo = $this->createMock(DisponibiliteService::class);
        $dispo->method('getAgendaGrid')->willReturn([
            [
                'de' => $start,
                'a' => $end,
                'type' => 'libre',
            ],
        ]);

        $validator = new RendezVousBookingValidator($dispo);
        $medecin = new Medecin();
        $medecin->setPlanning(new PlanningMedecin());

        $result = $validator->validateRequestedSlot($medecin, $start);

        $this->assertTrue($result['ok']);
        $this->assertSame('', $result['message']);
        $this->assertSame($end, $result['endAt']);
    }
}
