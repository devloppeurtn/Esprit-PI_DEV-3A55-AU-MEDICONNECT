<?php

namespace App\Tests\Service;

use App\Entity\CoursEducatif;
use App\Enum\StatutQuestion;
use App\Service\QuizAIGenerator;
use PHPUnit\Framework\TestCase;

class QuizAIGeneratorTest extends TestCase
{
    public function testGenererQuestionsUsesMockAndSetsStatus(): void
    {
        unset($_ENV['GEMINI_API_KEY']);

        $cours = (new CoursEducatif())
            ->setTitre('Diabete')
            ->setContenu('Le diabete est une maladie chronique avec des complications.');

        $generator = new QuizAIGenerator();
        $questions = $generator->genererQuestions($cours, 3, 'facile');

        $this->assertCount(3, $questions);
        foreach ($questions as $question) {
            $this->assertSame(StatutQuestion::IA_PROPOSE, $question->getStatut());
            $this->assertCount(4, $question->getOptionsReponsesArray());
            $this->assertNotEmpty($question->getReponseCorrecte());
        }
    }
}
