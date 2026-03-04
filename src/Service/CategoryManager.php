<?php

namespace App\Service;

use App\Entity\CategorieSante;
use App\Entity\Admin;
use App\Enum\StatutCategorie;
use Doctrine\ORM\EntityManagerInterface;

/**
 * CategoryManager Service
 * 
 * Centralizes business logic for category management
 * Implements business rules for category approval and publication
 */
class CategoryManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Validates if a category can be published/approved
     * 
     * Business Rules:
     * 1. Category name cannot be empty
     * 2. Category cannot be approved twice
     * 
     * @param CategorieSante $category The category to validate
     * @return bool True if validation passes
     * @throws \InvalidArgumentException When category name is empty (Business Rule 1)
     * @throws \LogicException When category is already approved (Business Rule 2)
     */
    public function validateForApproval(CategorieSante $category): bool
    {
        // BUSINESS RULE 1: Name cannot be empty
        if (empty($category->getNom()) || trim($category->getNom()) === '') {
            throw new \InvalidArgumentException(
                'Category name is required. A category cannot be published without a name. (Business Rule 1 violated)'
            );
        }

        // BUSINESS RULE 2: Cannot be approved twice
        if ($category->getStatut() === StatutCategorie::APPROUVE) {
            throw new \LogicException(
                'Category cannot be approved twice. This category was already approved on ' . 
                $category->getDateApprobation()?->format('Y-m-d H:i:s') . 
                '. (Business Rule 2 violated)'
            );
        }

        return true;
    }

    /**
     * Approves a category after validating business rules
     * 
     * @param CategorieSante $category The category to approve
     * @param Admin $admin The administrator approving the category
     * @return bool True if approval is successful
     * @throws \InvalidArgumentException When validation fails
     * @throws \LogicException When business rules are violated
     */
    public function approve(CategorieSante $category, Admin $admin): bool
    {
        // Validate business rules
        $this->validateForApproval($category);

        // Apply approval
        $category->setStatut(StatutCategorie::APPROUVE);
        $category->setApprouvePar($admin);
        $category->setDateApprobation(new \DateTime());

        // Persist changes
        $this->entityManager->flush();

        return true;
    }

    /**
     * Checks if a category is approved
     * 
     * @param CategorieSante $category The category to check
     * @return bool True if category is approved
     */
    public function isApproved(CategorieSante $category): bool
    {
        return $category->getStatut() === StatutCategorie::APPROUVE;
    }

    /**
     * Checks if a category can be approved
     * 
     * @param CategorieSante $category The category to check
     * @return bool True if category can be approved
     */
    public function canBeApproved(CategorieSante $category): bool
    {
        try {
            $this->validateForApproval($category);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Gets the reason why a category cannot be approved
     * 
     * @param CategorieSante $category The category to check
     * @return string|null The reason, or null if category can be approved
     */
    public function getApprovalBlockReason(CategorieSante $category): ?string
    {
        try {
            $this->validateForApproval($category);
            return null;
        } catch (\InvalidArgumentException $e) {
            return 'Missing name: ' . $e->getMessage();
        } catch (\LogicException $e) {
            return 'Already approved: ' . $e->getMessage();
        } catch (\Exception $e) {
            return 'Unknown error: ' . $e->getMessage();
        }
    }

    /**
     * Rejects a category
     * 
     * Business Rule: Cannot reject an already approved category
     * 
     * @param CategorieSante $category The category to reject
     * @param Admin $admin The administrator rejecting the category
     * @return bool True if rejection is successful
     * @throws \LogicException When trying to reject an approved category
     */
    public function reject(CategorieSante $category, Admin $admin): bool
    {
        // BUSINESS RULE: Cannot reject an already approved category
        if ($category->getStatut() === StatutCategorie::APPROUVE) {
            throw new \LogicException(
                'Cannot reject an already approved category. This category was approved on ' . 
                $category->getDateApprobation()?->format('Y-m-d H:i:s') . 
                ' and is visible to patients.'
            );
        }

        // Apply rejection
        $category->setStatut(StatutCategorie::REJETE);
        $category->setApprouvePar($admin);
        $category->setDateApprobation(new \DateTime());

        // Persist changes
        $this->entityManager->flush();

        return true;
    }

    /**
     * Gets category statistics
     * 
     * @param CategorieSante $category The category
     * @return array Statistics about the category
     */
    public function getStatistics(CategorieSante $category): array
    {
        return [
            'id' => $category->getId()->toRfc4122(),
            'name' => $category->getNom(),
            'status' => $category->getStatut()->value,
            'is_approved' => $this->isApproved($category),
            'can_be_approved' => $this->canBeApproved($category),
            'approval_block_reason' => $this->getApprovalBlockReason($category),
            'courses_count' => $category->getCoursEducatifs()->count(),
            'created_by' => $category->getCreePar()?->getNomComplet(),
            'approved_by' => $category->getApprouvePar()?->getNomComplet(),
            'approval_date' => $category->getDateApprobation()?->format('Y-m-d H:i:s'),
        ];
    }
}
