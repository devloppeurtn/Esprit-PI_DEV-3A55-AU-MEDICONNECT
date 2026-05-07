<?php

namespace App\Tests\Entity;

use App\Entity\Consultation;
use App\Entity\Ordonnance;
use PHPUnit\Framework\TestCase;

class OrdonnanceAggregationTest extends TestCase
{
    public function testConsultationAggregatesOrdonnances(): void
    {
        $consultation = new Consultation();

        $ord1 = (new Ordonnance())
            ->setMedicament('Doliprane')
            ->setMethodeUtilisation('Matin et soir');
        $ord2 = (new Ordonnance())
            ->setMedicament('Aftagel')
            ->setMethodeUtilisation('Matin');

        $consultation->addOrdonnance($ord1);
        $consultation->addOrdonnance($ord2);

        $this->assertCount(2, $consultation->getOrdonnances());
        $this->assertSame($consultation, $ord1->getConsultation());
        $this->assertSame($consultation, $ord2->getConsultation());

        $summary = array_map(function (Ordonnance $o) {
            return [
                'medicament' => $o->getMedicament(),
                'usage' => $o->getMethodeUtilisation(),
            ];
        }, $consultation->getOrdonnances()->toArray());

        $this->assertSame([
            ['medicament' => 'Doliprane', 'usage' => 'Matin et soir'],
            ['medicament' => 'Aftagel', 'usage' => 'Matin'],
        ], $summary);
    }
}
