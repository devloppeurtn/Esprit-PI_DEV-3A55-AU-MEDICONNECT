# 📋 Category Business Rules Implementation

## Overview

This document explains the business rules implemented for category management in MediConnect.

## 🎯 Business Rules

### Rule 1: Category Name Cannot Be Empty
**Description**: A category must have a valid name before it can be approved/published.

**Implementation**:
- Entity validation: `@Assert\NotBlank` constraint on `$nom` property
- Service validation: `CategoryManager::validateForApproval()` checks for empty names
- Controller validation: Checks before approval in `SavoirMedicalController::approuverCategorie()`
- UI validation: Form validation prevents submission without name
- UI feedback: Disabled approve button and red warning for categories without names

**Exception Thrown**: `InvalidArgumentException`

**Example**:
```php
$category = new CategorieSante();
$category->setNom(''); // Empty name

$categoryManager->approve($category, $admin);
// Throws: InvalidArgumentException: "Category name is required..."
```

### Rule 2: Category Cannot Be Approved Twice
**Description**: Once a category is approved, it cannot be approved again.

**Implementation**:
- Service validation: `CategoryManager::validateForApproval()` checks if already approved
- Controller validation: Checks status before approval
- UI feedback: Disabled approve button with "Déjà approuvée" label for approved categories
- Visual indicator: Green header and success alert showing approval date and admin

**Exception Thrown**: `LogicException`

**Example**:
```php
$category = new CategorieSante();
$category->setNom('Cardiologie');
$category->setStatut(StatutCategorie::APPROUVE);
$category->setDateApprobation(new \DateTime('2024-01-15'));

$categoryManager->approve($category, $admin);
// Throws: LogicException: "Category cannot be approved twice..."
```

### Rule 3: Cannot Reject an Approved Category
**Description**: Once a category is approved and visible to patients, it cannot be rejected.

**Implementation**:
- Service validation: `CategoryManager::reject()` checks if already approved
- Controller validation: Checks status before rejection
- UI feedback: Disabled reject button for approved categories

**Exception Thrown**: `LogicException`

**Example**:
```php
$category = new CategorieSante();
$category->setNom('Pédiatrie');
$category->setStatut(StatutCategorie::APPROUVE);

$categoryManager->reject($category, $admin);
// Throws: LogicException: "Cannot reject an already approved category..."
```

## 📁 File Structure

```
MediConnect-lastisra/
├── src/
│   ├── Entity/
│   │   └── CategorieSante.php          # Entity with validation constraints
│   ├── Service/
│   │   └── CategoryManager.php         # Business logic service
│   ├── Controller/
│   │   └── SavoirMedicalController.php # Controller with validation
│   └── Enum/
│       └── StatutCategorie.php         # Status enum
├── templates/
│   └── savoir_medical/
│       └── admin_categories_en_attente.html.twig  # UI with visual feedback
└── tests/
    └── Service/
        └── CategoryManagerTest.php     # Unit tests for business rules
```

## 🔧 Implementation Details

### 1. Entity Level (CategorieSante.php)

```php
#[ORM\Column(type: Types::STRING, length: 255)]
#[Assert\NotBlank(message: 'Le nom de la catégorie est obligatoire')]
#[Assert\Length(
    min: 3,
    max: 255,
    minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
    maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
)]
private ?string $nom = null;

// Helper methods
public function isApproved(): bool
public function isPending(): bool
public function isRejected(): bool
public function hasValidName(): bool
```

### 2. Service Level (CategoryManager.php)

```php
class CategoryManager
{
    // Validates business rules before approval
    public function validateForApproval(CategorieSante $category): bool
    
    // Approves category after validation
    public function approve(CategorieSante $category, Admin $admin): bool
    
    // Rejects category with validation
    public function reject(CategorieSante $category, Admin $admin): bool
    
    // Helper methods
    public function isApproved(CategorieSante $category): bool
    public function canBeApproved(CategorieSante $category): bool
    public function getApprovalBlockReason(CategorieSante $category): ?string
    public function getStatistics(CategorieSante $category): array
}
```

### 3. Controller Level (SavoirMedicalController.php)

```php
public function approuverCategorie(string $id): Response
{
    // Validation 1: Check for empty name
    if (empty($categorie->getNom()) || trim($categorie->getNom()) === '') {
        $this->addFlash('error', 'Impossible d\'approuver une catégorie sans nom...');
        return $this->redirectToRoute('app_admin_categories_en_attente');
    }

    // Validation 2: Check if already approved
    if ($categorie->getStatut() === StatutCategorie::APPROUVE) {
        $this->addFlash('warning', 'Cette catégorie a déjà été approuvée...');
        return $this->redirectToRoute('app_admin_categories_en_attente');
    }
    
    // Proceed with approval...
}
```

### 4. UI Level (admin_categories_en_attente.html.twig)

```twig
{# Visual indicator for missing name #}
{% if categorie.nom is empty or categorie.nom|trim == '' %}
    <div class="alert alert-danger">
        <strong>Attention:</strong> Cette catégorie ne peut pas être approuvée 
        car elle n'a pas de nom.
    </div>
{% endif %}

{# Visual indicator for already approved #}
{% if categorie.statut.value == 'APPROUVE' %}
    <div class="alert alert-success">
        <strong>Déjà approuvée:</strong> Cette catégorie a été approuvée le 
        {{ categorie.dateApprobation|date('d/m/Y à H:i') }}
    </div>
{% endif %}

{# Disabled button for invalid categories #}
<button type="submit" 
        class="btn btn-success w-100" 
        {% if categorie.nom is empty or categorie.statut.value == 'APPROUVE' %}
            disabled
        {% endif %}>
    Approuver
</button>
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php bin/phpunit

# Run only CategoryManager tests
php bin/phpunit tests/Service/CategoryManagerTest.php

# Run with coverage
php bin/phpunit --coverage-html coverage/
```

### Test Coverage

The test suite covers:
- ✅ Business Rule 1: Cannot approve without name
- ✅ Business Rule 2: Cannot approve twice
- ✅ Business Rule 3: Cannot reject approved category
- ✅ Successful approval flow
- ✅ Successful rejection flow
- ✅ Helper methods validation
- ✅ Statistics generation
- ✅ Edge cases and error handling

### Test Results Expected

```
CategoryManagerTest
 ✓ Cannot approve category without name
 ✓ Cannot approve category twice
 ✓ Successful approval
 ✓ Can be approved
 ✓ Get approval block reason
 ✓ Cannot reject approved category
 ✓ Successful rejection
 ✓ Entity helper methods
 ✓ Get category statistics

Time: 00:00.123, Memory: 10.00 MB

OK (9 tests, 35 assertions)
```

## 🎓 Benefits of This Architecture

### 1. Separation of Concerns
- **Entity**: Data structure and basic validation
- **Service**: Business logic and rules
- **Controller**: HTTP handling and routing
- **Template**: User interface and feedback

### 2. Testability
- Business logic isolated in service
- Easy to unit test without HTTP layer
- Mock dependencies for fast tests

### 3. Reusability
- Service can be used from multiple controllers
- Business rules centralized in one place
- Easy to add new rules

### 4. Maintainability
- Clear documentation of business rules
- Single source of truth for validation
- Easy to modify rules without breaking code

### 5. User Experience
- Clear error messages
- Visual feedback for invalid states
- Disabled buttons prevent errors
- Helpful tooltips and alerts

## 📊 Status Flow Diagram

```
┌─────────────┐
│ EN_ATTENTE  │ ← Category created by doctor
└──────┬──────┘
       │
       ├─→ [Approve] → Check Business Rules
       │                     │
       │                     ├─→ ✓ Has name?
       │                     ├─→ ✓ Not already approved?
       │                     │
       │                     ├─→ YES → ┌──────────┐
       │                     │          │ APPROUVE │
       │                     │          └──────────┘
       │                     │
       │                     └─→ NO → Show Error
       │
       └─→ [Reject] → Check Business Rules
                            │
                            ├─→ ✓ Not already approved?
                            │
                            ├─→ YES → ┌─────────┐
                            │          │ REJETE  │
                            │          └─────────┘
                            │
                            └─→ NO → Show Error
```

## 🚀 Usage Examples

### Example 1: Approve a Valid Category

```php
use App\Service\CategoryManager;

// Inject the service
public function __construct(
    private CategoryManager $categoryManager
) {}

// In your controller
public function approveCategory(CategorieSante $category, Admin $admin): Response
{
    try {
        $this->categoryManager->approve($category, $admin);
        $this->addFlash('success', 'Category approved successfully!');
    } catch (\InvalidArgumentException $e) {
        $this->addFlash('error', 'Cannot approve: ' . $e->getMessage());
    } catch (\LogicException $e) {
        $this->addFlash('warning', 'Business rule violated: ' . $e->getMessage());
    }
    
    return $this->redirectToRoute('admin_categories');
}
```

### Example 2: Check Before Approval

```php
// Check if category can be approved
if ($categoryManager->canBeApproved($category)) {
    // Show approve button
} else {
    // Show reason why it cannot be approved
    $reason = $categoryManager->getApprovalBlockReason($category);
    echo "Cannot approve: " . $reason;
}
```

### Example 3: Get Category Statistics

```php
$stats = $categoryManager->getStatistics($category);

echo "Category: " . $stats['name'];
echo "Status: " . $stats['status'];
echo "Can be approved: " . ($stats['can_be_approved'] ? 'Yes' : 'No');
echo "Courses: " . $stats['courses_count'];
```

## 📝 Summary

This implementation demonstrates:
- ✅ Clean architecture with separation of concerns
- ✅ Comprehensive business rule validation
- ✅ Excellent test coverage
- ✅ Clear user feedback
- ✅ Professional error handling
- ✅ Maintainable and extensible code

Perfect for academic projects and real-world applications! 🎓✨
