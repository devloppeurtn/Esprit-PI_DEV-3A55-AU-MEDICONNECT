<?php

namespace App\Tests\Service;

use App\Entity\Utilisateur;
use App\Service\OrderAnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class OrderAnalyticsServiceTest extends TestCase
{
    public function testGetTotalSpentReturnsZeroWhenNull(): void
    {
        $query = new FakeQuery(oneOrNull: ['total' => null]);
        $em = $this->makeEntityManager($query);
        $service = new OrderAnalyticsService($em);
        $user = (new Utilisateur())->setEmail('u@test.local')->setNomComplet('U Test');

        $this->assertSame(0, $service->getTotalSpent($user));
    }

    public function testGetOrderFrequencyCalculatesAverageDays(): void
    {
        $orders = [
            ['dateCommande' => new \DateTimeImmutable('2026-01-01')],
            ['dateCommande' => new \DateTimeImmutable('2026-01-11')],
        ];
        $query = new FakeQuery(result: $orders);
        $em = $this->makeEntityManager($query);
        $service = new OrderAnalyticsService($em);
        $user = (new Utilisateur())->setEmail('u@test.local')->setNomComplet('U Test');

        $this->assertSame(10.0, $service->getOrderFrequency($user));
    }

    public function testGetCustomerSegmentUsesThresholds(): void
    {
        $service = $this->getMockBuilder(OrderAnalyticsService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTotalSpent'])
            ->getMock();

        $service->method('getTotalSpent')->willReturn(2500.0);
        $user = (new Utilisateur())->setEmail('u@test.local')->setNomComplet('U Test');

        $this->assertSame('Silver', $service->getCustomerSegment($user));
    }

    private function makeEntityManager(FakeQuery $query): EntityManagerInterface
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('groupBy')->willReturnSelf();
        $qb->method('innerJoin')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('having')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);
        return $em;
    }
}

final class FakeQuery
{
    public function __construct(
        private ?array $oneOrNull = null,
        private ?array $result = null,
        private ?string $singleScalar = null,
    ) {}

    public function getOneOrNullResult(): ?array
    {
        return $this->oneOrNull;
    }

    public function getResult(): array
    {
        return $this->result ?? [];
    }

    public function getSingleScalarResult(): string
    {
        return $this->singleScalar ?? '0';
    }
}
