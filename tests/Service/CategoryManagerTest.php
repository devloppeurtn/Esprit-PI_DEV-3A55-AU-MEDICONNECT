<?php

namespace App\Tests\Service;

use App\Entity\CategorieSante;
use App\Entity\Admin;
use App\Enum\StatutCategorie;
use App\Service\CategoryManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit Tests for CategoryManager Service
 * 
 * Tests all business rules:
 * 1. Category name cannot be empty
 * 2. Category cannot be approved twice
 * 3. Cannot reject an already approved category
 */
class CategoryManagerTest extends TestCase
{
    private CategoryManager $categoryManager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        // Mock the EntityManager
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        // Create the service
        $this->categoryManager = new CategoryManager($this->entityManager);
    }

    /**
     * TEST 1: Business Rule 1 - Category name cannot be empty
     * 
     * Expected: Should throw InvalidArgumentException
     */
    public function testCannotApproveCategoryWithoutName(): void
    {
        // Arrange: Create a category without a name
        $category = new CategorieSante();
        $category->setNom(''); // Empty name
        $category->setDescription('Test description');
        $category->setType('Culture Générale');
        $category->setStatut(StatutCategorie::EN_ATTENTE);

        $admin = new Admin();

        // Assert: Expect InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name is required');

        // Act: Try to approve
        $this->categoryManager->approve($category, $admin);
    }

    /**
     * TEST 2: Business Rule 2 - Category cannot be approved twice
     * 
     * Expected: Should throw LogicException
     */
    public function testCannotApproveCategoryTwice(): void
    {
        // Arrange: Create an already approved category
        $category = new CategorieSante();
        $category->setNom('Cardiologie');
        $category->setDescription('Test description');
        $category->setType('Spécialité Médicale');
        $category->setStatut(StatutCategorie::APPROUVE); // Already approved
        $category->setDateApprobation(new \DateTime('2024-01-01'));

        $admin = new Admin();

        // Assert: Expect LogicException
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Category cannot be approved twice');

        // Act: Try to approve again
        $this->categoryManager->approve($category, $admin);
    }

    /**
     * TEST 3: Successful approval with valid data
     * 
     * Expected: Should return true and update category status
     */
    public function testSuccessfulApproval(): void
    {
        // Arrange: Create a valid pending category
        $category = new CategorieSante();
        $category->setNom('Pédiatrie');
        $category->setDescription('Médecine des enfants');
        $category->setType('Spécialité Médicale');
        $category->setStatut(StatutCategorie::EN_ATTENTE);

        $admin = new Admin();

        // Mock EntityManager to expect flush
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act: Approve the category
        $result = $this->categoryManager->approve($category, $admin);

        // Assert: Check results
        $this->assertTrue($result);
        $this->assertTrue($category->isApproved());
        $this->assertEquals(StatutCategorie::APPROUVE, $category->getStatut());
        $this->assertNotNull($category->getDateApprobation());
        $this->assertEquals($admin, $category->getApprouvePar());
    }

    /**
     * TEST 4: Check if category can be approved
     * 
     * Expected: Should return true for valid category, false otherwise
     */
    public function testCanBeApproved(): void
    {
        // Test 1: Valid category
        $validCategory = new CategorieSante();
        $validCategory->setNom('Neurologie');
        $validCategory->setStatut(StatutCategorie::EN_ATTENTE);
        
        $this->assertTrue($this->categoryManager->canBeApproved($validCategory));

        // Test 2: Category without name
        $noNameCategory = new CategorieSante();
        $noNameCategory->setNom('');
        $noNameCategory->setStatut(StatutCategorie::EN_ATTENTE);
        
        $this->assertFalse($this->categoryManager->canBeApproved($noNameCategory));

        // Test 3: Already approved category
        $approvedCategory = new CategorieSante();
        $approvedCategory->setNom('Dermatologie');
        $approvedCategory->setStatut(StatutCategorie::APPROUVE);
        
        $this->assertFalse($this->categoryManager->canBeApproved($approvedCategory));
    }

    /**
     * TEST 5: Get approval block reason
     * 
     * Expected: Should return specific reason why category cannot be approved
     */
    public function testGetApprovalBlockReason(): void
    {
        // Test 1: Valid category (no block reason)
        $validCategory = new CategorieSante();
        $validCategory->setNom('Oncologie');
        $validCategory->setStatut(StatutCategorie::EN_ATTENTE);
        
        $this->assertNull($this->categoryManager->getApprovalBlockReason($validCategory));

        // Test 2: Category without name
        $noNameCategory = new CategorieSante();
        $noNameCategory->setNom('');
        $noNameCategory->setStatut(StatutCategorie::EN_ATTENTE);
        
        $reason = $this->categoryManager->getApprovalBlockReason($noNameCategory);
        $this->assertStringContainsString('Missing name', $reason);

        // Test 3: Already approved category
        $approvedCategory = new CategorieSante();
        $approvedCategory->setNom('Psychiatrie');
        $approvedCategory->setStatut(StatutCategorie::APPROUVE);
        $approvedCategory->setDateApprobation(new \DateTime());
        
        $reason = $this->categoryManager->getApprovalBlockReason($approvedCategory);
        $this->assertStringContainsString('Already approved', $reason);
    }

    /**
     * TEST 6: Business Rule 3 - Cannot reject an approved category
     * 
     * Expected: Should throw LogicException
     */
    public function testCannotRejectApprovedCategory(): void
    {
        // Arrange: Create an approved category
        $category = new CategorieSante();
        $category->setNom('Radiologie');
        $category->setDescription('Imagerie médicale');
        $category->setType('Spécialité Médicale');
        $category->setStatut(StatutCategorie::APPROUVE);
        $category->setDateApprobation(new \DateTime('2024-01-15'));

        $admin = new Admin();

        // Assert: Expect LogicException
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot reject an already approved category');

        // Act: Try to reject
        $this->categoryManager->reject($category, $admin);
    }

    /**
     * TEST 7: Successful rejection of pending category
     * 
     * Expected: Should return true and update category status
     */
    public function testSuccessfulRejection(): void
    {
        // Arrange: Create a pending category
        $category = new CategorieSante();
        $category->setNom('Test Category');
        $category->setDescription('Test description');
        $category->setType('Culture Générale');
        $category->setStatut(StatutCategorie::EN_ATTENTE);

        $admin = new Admin();

        // Mock EntityManager to expect flush
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act: Reject the category
        $result = $this->categoryManager->reject($category, $admin);

        // Assert: Check results
        $this->assertTrue($result);
        $this->assertTrue($category->isRejected());
        $this->assertEquals(StatutCategorie::REJETE, $category->getStatut());
        $this->assertNotNull($category->getDateApprobation());
        $this->assertEquals($admin, $category->getApprouvePar());
    }

    /**
     * TEST 8: Entity helper methods
     * 
     * Expected: Helper methods should work correctly
     */
    public function testEntityHelperMethods(): void
    {
        $category = new CategorieSante();
        
        // Test hasValidName()
        $category->setNom('');
        $this->assertFalse($category->hasValidName());
        
        $category->setNom('   ');
        $this->assertFalse($category->hasValidName());
        
        $category->setNom('Valid Name');
        $this->assertTrue($category->hasValidName());

        // Test status checks
        $category->setStatut(StatutCategorie::EN_ATTENTE);
        $this->assertTrue($category->isPending());
        $this->assertFalse($category->isApproved());
        $this->assertFalse($category->isRejected());

        $category->setStatut(StatutCategorie::APPROUVE);
        $this->assertFalse($category->isPending());
        $this->assertTrue($category->isApproved());
        $this->assertFalse($category->isRejected());

        $category->setStatut(StatutCategorie::REJETE);
        $this->assertFalse($category->isPending());
        $this->assertFalse($category->isApproved());
        $this->assertTrue($category->isRejected());
    }

    /**
     * TEST 9: Get category statistics
     * 
     * Expected: Should return complete statistics array
     */
    public function testGetStatistics(): void
    {
        // Arrange
        $category = new CategorieSante();
        $category->setNom('Chirurgie');
        $category->setDescription('Chirurgie générale');
        $category->setType('Spécialité Médicale');
        $category->setStatut(StatutCategorie::APPROUVE);
        $category->setDateApprobation(new \DateTime('2024-02-01 10:30:00'));

        // Act
        $stats = $this->categoryManager->getStatistics($category);

        // Assert
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('id', $stats);
        $this->assertArrayHasKey('name', $stats);
        $this->assertArrayHasKey('status', $stats);
        $this->assertArrayHasKey('is_approved', $stats);
        $this->assertArrayHasKey('can_be_approved', $stats);
        $this->assertArrayHasKey('courses_count', $stats);
        
        $this->assertEquals('Chirurgie', $stats['name']);
        $this->assertEquals('APPROUVE', $stats['status']);
        $this->assertTrue($stats['is_approved']);
        $this->assertFalse($stats['can_be_approved']); // Already approved
    }
}
