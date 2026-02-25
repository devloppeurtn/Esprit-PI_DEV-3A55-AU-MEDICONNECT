<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotAiService
{
    private const OUT_OF_SCOPE_REPLY = 'Ce n\'est pas mon role. Je peux seulement vous aider a trouver un medecin et prendre un rendez-vous.';

    private const ALLOWED_ACTIONS = [
        'UNKNOWN',
        'OUT_OF_SCOPE',
        'LIST_DOCTORS',
        'EXPLORE',
        'BOOK',
        'PICK_DOCTOR',
        'SET_DATE',
        'SET_TIME',
        'CHANGE_DATE',
        'CHANGE_TIME',
        'CONFIRM',
        'NONE',
    ];

    private ?string $apiKey;
    private string $model;

    public function __construct(
        private HttpClientInterface $httpClient,
        private ?LoggerInterface $logger = null,
    ) {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? $_SERVER['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?: null;
        $this->apiKey = is_string($this->apiKey) ? trim($this->apiKey) : null;
        if ($this->apiKey === '') {
            $this->apiKey = null;
        }

        $model = $_ENV['OPENAI_MODEL'] ?? $_SERVER['OPENAI_MODEL'] ?? getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';
        $this->model = is_string($model) && trim($model) !== '' ? trim($model) : 'gpt-4o-mini';
    }

    public function isEnabled(): bool
    {
        return $this->apiKey !== null;
    }

    /**
     * @param array<string,mixed> $context
     *
     * @return array{
     *   action:string,
     *   reply:?string,
     *   doctorQuery:?string,
     *   specialityQuery:?string,
     *   date:?string,
     *   time:?string
     * }
     */
    public function interpretMessage(string $message, string $step, array $context = []): array
    {
        if (!$this->isEnabled()) {
            return $this->sanitizeResult($this->interpretLocally($message, $step, $context));
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'temperature' => 0.2,
                    'max_tokens' => 260,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => $this->buildMessages($message, $step, $context),
                ],
            ]);

            $payload = $response->toArray(false);
            if (isset($payload['error']['message']) && is_string($payload['error']['message'])) {
                $this->logger?->warning('Chatbot OpenAI returned error payload', [
                    'error' => $payload['error']['message'],
                ]);

                return $this->sanitizeResult($this->interpretLocally($message, $step, $context));
            }

            $content = (string) ($payload['choices'][0]['message']['content'] ?? '');
            $decoded = $this->decodeJsonObject($content);

            return $this->sanitizeResult($decoded);
        } catch (\Throwable $e) {
            $this->logger?->warning('Chatbot OpenAI interpret failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->sanitizeResult($this->interpretLocally($message, $step, $context));
        }
    }

    /**
     * Basic local fallback when OpenAI is unavailable:
     * detect intents from keywords and current step.
     *
     * @param array<string,mixed> $context
     *
     * @return array<string,mixed>
     */
    private function interpretLocally(string $message, string $step, array $context): array
    {
        $message = trim($message);
        $step = strtoupper(trim($step));
        $normalizedMessage = $this->normalizeText($message);

        $result = [
            'action' => 'UNKNOWN',
            'reply' => null,
            'doctorQuery' => null,
            'specialityQuery' => null,
            'date' => $this->extractDateFromText($message),
            'time' => $this->extractTimeFromText($message),
        ];

        if ($message === '') {
            $result['reply'] = 'Je n\'ai pas compris votre message.';

            return $result;
        }

        if (preg_match('/\b(bonjour|salut|bonsoir|hello)\b/', $normalizedMessage) === 1) {
            $result['action'] = 'NONE';
            $result['reply'] = 'Bonjour. Je suis votre assistant MediConnect. Je peux vous aider a trouver un medecin et prendre un rendez-vous.';

            return $result;
        }

        if (preg_match('/\b(merci|thank you|thanks)\b/', $normalizedMessage) === 1) {
            $result['action'] = 'NONE';
            $result['reply'] = 'Avec plaisir. Je reste disponible pour vous aider avec un rendez-vous medical.';

            return $result;
        }

        $medicalKeywords = [
            'medecin',
            'docteur',
            'dr',
            'specialite',
            'specialiste',
            'rdv',
            'rendez vous',
            'rendez-vous',
            'consultation',
            'patient',
            'hopital',
            'clinique',
            'date',
            'heure',
            'horaire',
            'creneau',
            'disponible',
            'agenda',
            'disponibilite',
            'appointment',
            'book',
        ];
        $wantsListDoctors = $this->wantsAllDoctorsRequest($normalizedMessage);
        $wantsBooking = $this->containsAny($normalizedMessage, ['rendez vous', 'rendez-vous', 'rdv', 'prendre un rendez', 'reserver', 'planifier', 'book']);
        $wantsConfirm = preg_match('/\b(oui|confirmer|confirme|valider|validation|ok)\b/', $normalizedMessage) === 1;
        $wantsCancel = preg_match('/\b(non|annuler|stop|abandonner)\b/', $normalizedMessage) === 1;
        $wantsChangeDate = preg_match('/\b(modifier|changer|decaler|reporter)\b.*\b(date|jour)\b/', $normalizedMessage) === 1;
        $wantsChangeTime = preg_match('/\b(modifier|changer|decaler|reporter)\b.*\b(heure|horaire)\b/', $normalizedMessage) === 1;
        $wantsChange = preg_match('/\b(modifier|changer)\b/', $normalizedMessage) === 1;
        $mentionsDate = preg_match('/\b(date|jour|demain|aujourdhui)\b/', $normalizedMessage) === 1;
        $mentionsTime = preg_match('/\b(heure|horaire)\b/', $normalizedMessage) === 1;
        $specialityQuery = $this->extractSpecialityQuery($message, $context);
        $doctorQuery = $this->extractDoctorQuery($message, $context);

        $isStructuredStep = in_array($step, ['RDV_DOCTOR', 'EXPLORE_RESULT', 'RDV_DATE', 'RDV_TIME', 'CONFIRM'], true);
        $isMedical = $this->containsAny($normalizedMessage, $medicalKeywords)
            || $wantsListDoctors
            || $wantsBooking
            || $specialityQuery !== null
            || $doctorQuery !== null
            || $result['date'] !== null
            || $result['time'] !== null
            || $isStructuredStep;

        if (!$isMedical) {
            if ($this->isOutOfScopeMessage($normalizedMessage) || strlen($normalizedMessage) >= 4) {
                $result['action'] = 'OUT_OF_SCOPE';
                $result['reply'] = self::OUT_OF_SCOPE_REPLY;

                return $result;
            }
        }

        if ($wantsCancel) {
            $result['action'] = 'UNKNOWN';
            $result['reply'] = 'Demande annulee. Je peux vous aider a prendre un nouveau rendez-vous.';

            return $result;
        }

        if ($wantsConfirm && $step === 'CONFIRM') {
            $result['action'] = 'CONFIRM';

            return $result;
        }

        // Priorite aux intentions explicites "changer date/heure".
        if ($wantsChangeDate) {
            $result['action'] = 'CHANGE_DATE';

            return $result;
        }

        if ($wantsChangeTime) {
            $result['action'] = 'CHANGE_TIME';

            return $result;
        }

        // Intention "changer" ambigue: on deduit selon l'etape courante.
        if ($wantsChange && $step === 'RDV_TIME') {
            $result['action'] = ($mentionsDate || $result['date'] !== null) ? 'CHANGE_DATE' : 'CHANGE_TIME';

            return $result;
        }

        if ($wantsChange && $step === 'RDV_DATE') {
            $result['action'] = ($mentionsTime || $result['time'] !== null) ? 'CHANGE_TIME' : 'CHANGE_DATE';

            return $result;
        }

        if ($result['time'] !== null && ($step === 'RDV_TIME' || $mentionsTime)) {
            $result['action'] = 'SET_TIME';

            return $result;
        }

        if ($result['date'] !== null && ($step === 'RDV_DATE' || $mentionsDate)) {
            $result['action'] = 'SET_DATE';

            return $result;
        }

        if ($wantsListDoctors) {
            $result['action'] = 'LIST_DOCTORS';

            return $result;
        }

        if ($wantsBooking) {
            $result['action'] = 'BOOK';

            return $result;
        }

        if ($doctorQuery !== null) {
            $result['action'] = 'PICK_DOCTOR';
            $result['doctorQuery'] = $doctorQuery;

            return $result;
        }

        if ($specialityQuery !== null) {
            $result['action'] = 'EXPLORE';
            $result['specialityQuery'] = $specialityQuery;

            return $result;
        }

        if ($step === 'RDV_DOCTOR' || $step === 'EXPLORE_RESULT') {
            $result['action'] = 'PICK_DOCTOR';
            $result['doctorQuery'] = $message;

            return $result;
        }

        if ($step === 'RDV_DATE') {
            if ($result['date'] !== null) {
                $result['action'] = 'SET_DATE';
            } else {
                $result['reply'] = 'Donnez la date souhaitee (ex: 25/03/2026).';
            }

            return $result;
        }

        if ($step === 'RDV_TIME') {
            if ($result['time'] !== null) {
                $result['action'] = 'SET_TIME';
            } else {
                $result['reply'] = 'Donnez l\'heure souhaitee (ex: 14h30).';
            }

            return $result;
        }

        if ($step === 'CONFIRM' && $wantsConfirm) {
            $result['action'] = 'CONFIRM';

            return $result;
        }

        $result['reply'] = 'Je peux vous aider a trouver un medecin ou prendre un rendez-vous.';

        return $result;
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        $transliterated = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)
            : false;
        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }

        $value = strtolower($value);

        $value = preg_replace('/[^a-z0-9\s\-:\/]/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (!is_string($needle)) {
                continue;
            }

            $needle = trim($needle);
            if ($needle === '') {
                continue;
            }

            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isOutOfScopeMessage(string $normalizedMessage): bool
    {
        if ($normalizedMessage === '') {
            return false;
        }

        $outOfScopeKeywords = [
            'meteo',
            'temperature',
            'pluie',
            'vent',
            'climat',
            'football',
            'foot',
            'match',
            'score',
            'ligue',
            'basket',
            'nba',
            'crypto',
            'bitcoin',
            'politique',
            'election',
            'cinema',
            'serie',
            'musique',
            'restaurant',
            'recette',
            'cuisine',
            'capital',
            'president',
            'histoire',
            'programmation',
            'coder',
            'traduire',
        ];

        if ($this->containsAny($normalizedMessage, $outOfScopeKeywords)) {
            return true;
        }

        if (preg_match('/\b(qui|quoi|comment|pourquoi|ou|quand|combien|quel|quelle|quels|quelles|explique|defini|definition)\b/', $normalizedMessage) === 1) {
            return true;
        }

        return false;
    }

    private function wantsAllDoctorsRequest(string $normalizedMessage): bool
    {
        if ($normalizedMessage === '') {
            return false;
        }

        if (
            preg_match('/(?:\btt\b|\btout\b|\btous\b|\ball\b)(?:\s+\w+){0,4}\s+(?:medecins?|docteurs?)\b/', $normalizedMessage) === 1
        ) {
            return true;
        }

        if (
            preg_match('/\b(?:liste|lister|voir|affich\w*|mont\w*|donn\w*)\b(?:\s+\w+){0,5}\s+(?:medecins?|docteurs?)\b/', $normalizedMessage) === 1
        ) {
            return true;
        }

        if (
            preg_match('/\b(?:qui sont|quels sont)\s+les\s+(?:medecins?|docteurs?)\b/', $normalizedMessage) === 1
        ) {
            return true;
        }

        return false;
    }

    private function extractDateFromText(string $message): ?string
    {
        $direct = $this->normalizeDate($message);
        if ($direct !== null) {
            return $direct;
        }

        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $message, $m) === 1) {
            return $this->normalizeDate($m[1]);
        }

        if (preg_match('/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4})\b/', $message, $m) === 1) {
            return $this->normalizeDate($m[1]);
        }

        if (preg_match('/\b(\d{1,2}[\/\-]\d{1,2})\b/', $message, $m) === 1) {
            $candidate = $m[1] . '/' . date('Y');
            return $this->normalizeDate($candidate);
        }

        $normalized = $this->normalizeText($message);
        if (preg_match('/\b(\d{1,2})\s+(janvier|fevrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre)(?:\s+(\d{4}))?\b/', $normalized, $m) === 1) {
            $months = [
                'janvier' => 1,
                'fevrier' => 2,
                'mars' => 3,
                'avril' => 4,
                'mai' => 5,
                'juin' => 6,
                'juillet' => 7,
                'aout' => 8,
                'septembre' => 9,
                'octobre' => 10,
                'novembre' => 11,
                'decembre' => 12,
            ];

            $day = (int) $m[1];
            $month = $months[$m[2]] ?? 0;
            $year = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : (int) date('Y');

            if ($month > 0 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        return null;
    }

    private function extractTimeFromText(string $message): ?string
    {
        $direct = $this->normalizeTime($message);
        if ($direct !== null) {
            return $direct;
        }

        if (preg_match('/\b(\d{1,2}:\d{1,2})\b/', $message, $m) === 1) {
            return $this->normalizeTime($m[1]);
        }

        if (preg_match('/\b(\d{1,2}h(?:\d{1,2})?)\b/i', $message, $m) === 1) {
            return $this->normalizeTime(strtolower($m[1]));
        }

        return null;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function extractSpecialityQuery(string $message, array $context): ?string
    {
        $specialites = $context['specialites'] ?? null;
        if (!is_array($specialites) || $specialites === []) {
            return null;
        }

        $normalizedMessage = $this->normalizeText($message);
        foreach ($specialites as $specialite) {
            if (!is_string($specialite)) {
                continue;
            }

            $specialite = trim($specialite);
            if ($specialite === '') {
                continue;
            }

            $normalizedSpecialite = $this->normalizeText($specialite);
            if ($normalizedSpecialite === '') {
                continue;
            }

            if (
                str_contains($normalizedMessage, $normalizedSpecialite)
                || str_contains($normalizedSpecialite, $normalizedMessage)
            ) {
                return $specialite;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function extractDoctorQuery(string $message, array $context): ?string
    {
        $normalizedMessage = $this->normalizeText($message);
        if ($normalizedMessage === '') {
            return null;
        }

        $medecins = $context['medecins'] ?? null;
        if (!is_array($medecins) || $medecins === []) {
            return null;
        }

        foreach ($medecins as $medecin) {
            if (!is_array($medecin)) {
                continue;
            }

            $nom = isset($medecin['nom']) && is_string($medecin['nom']) ? trim($medecin['nom']) : '';
            if ($nom === '') {
                continue;
            }

            $normalizedNom = $this->normalizeText($nom);
            if ($normalizedNom === '') {
                continue;
            }

            if (
                str_contains($normalizedNom, $normalizedMessage)
                || str_contains($normalizedMessage, $normalizedNom)
            ) {
                return $nom;
            }

            $parts = preg_split('/\s+/', $normalizedNom) ?: [];
            foreach ($parts as $part) {
                if (strlen($part) < 3) {
                    continue;
                }

                if (str_contains($normalizedMessage, $part)) {
                    return $nom;
                }
            }
        }

        if (preg_match('/\bdr\s+([a-z]{2,}(?:\s+[a-z]{2,}){0,2})\b/', $normalizedMessage, $m) === 1) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * @param array<string,mixed> $context
     *
     * @return array<int,array{role:string,content:string}>
     */
    private function buildMessages(string $message, string $step, array $context): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $this->buildSystemPrompt(),
            ],
        ];

        foreach ($this->extractConversationHistory($context) as $historyMessage) {
            $messages[] = $historyMessage;
        }

        $messages[] = [
            'role' => 'user',
            'content' => $this->buildUserPrompt($message, $step, $context),
        ];

        return $messages;
    }

    /**
     * @param array<string,mixed> $context
     *
     * @return array<int,array{role:string,content:string}>
     */
    private function extractConversationHistory(array $context): array
    {
        $state = $context['state'] ?? null;
        if (!is_array($state)) {
            return [];
        }

        $history = $state['history'] ?? null;
        if (!is_array($history) || $history === []) {
            return [];
        }

        $messages = [];
        foreach ($history as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = isset($item['role']) && is_string($item['role']) ? strtolower(trim($item['role'])) : '';
            if (!in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $content = isset($item['content']) && is_string($item['content']) ? trim($item['content']) : '';
            if ($content === '') {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        if (count($messages) > 12) {
            $messages = array_slice($messages, -12);
        }

        return $messages;
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
You are MediConnect assistant for French patients.
You ONLY help with:
- finding doctors
- specialities
- booking medical appointments

If the user asks out-of-scope topics (weather, football, politics, restaurants, etc.), answer politely that this is not your role.

Return ONLY a JSON object with these keys:
- action: one of UNKNOWN, OUT_OF_SCOPE, LIST_DOCTORS, EXPLORE, BOOK, PICK_DOCTOR, SET_DATE, SET_TIME, CHANGE_DATE, CHANGE_TIME, CONFIRM, NONE
- reply: natural French answer for the patient, concise and clear (max 2 short sentences)
- doctorQuery: doctor name query or null
- specialityQuery: speciality query or null
- date: YYYY-MM-DD or null
- time: HH:MM 24h or null

Rules:
- Be robust to accents, uppercase/lowercase, and typos.
- Answer like ChatGPT in French: natural, helpful, and direct.
- If the user asks for all doctors => LIST_DOCTORS.
- If the user asks to book / take an appointment => BOOK.
- If user provides doctor/speciality => PICK_DOCTOR or EXPLORE.
- If user provides date => SET_DATE.
- If user provides time => SET_TIME.
- If user asks to change date/time => CHANGE_DATE / CHANGE_TIME.
- If user confirms recap => CONFIRM.
- If out-of-scope => action OUT_OF_SCOPE and reply must start with: "Ce n'est pas mon role."
PROMPT;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function buildUserPrompt(string $message, string $step, array $context): string
    {
        $promptContext = $context;
        if (
            isset($promptContext['state'])
            && is_array($promptContext['state'])
            && array_key_exists('history', $promptContext['state'])
        ) {
            unset($promptContext['state']['history']);
        }

        $contextJson = json_encode($promptContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($contextJson)) {
            $contextJson = '{}';
        }

        return "Step actuel: {$step}\nContexte: {$contextJson}\nMessage patient: {$message}";
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeJsonObject(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches) !== 1) {
            return [];
        }

        $decoded = json_decode($matches[0], true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string,mixed> $result
     *
     * @return array{
     *   action:string,
     *   reply:?string,
     *   doctorQuery:?string,
     *   specialityQuery:?string,
     *   date:?string,
     *   time:?string
     * }
     */
    private function sanitizeResult(array $result): array
    {
        $action = strtoupper(trim((string) ($result['action'] ?? 'UNKNOWN')));
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            $action = 'UNKNOWN';
        }

        return [
            'action' => $action,
            'reply' => $this->sanitizeNullableString($result['reply'] ?? null),
            'doctorQuery' => $this->sanitizeNullableString($result['doctorQuery'] ?? null),
            'specialityQuery' => $this->sanitizeNullableString($result['specialityQuery'] ?? null),
            'date' => $this->normalizeDate($result['date'] ?? null),
            'time' => $this->normalizeTime($result['time'] ?? null),
        ];
    }

    private function sanitizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) === 1) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $m) === 1) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if (!checkdate($month, $day, $year)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return null;
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim(strtolower($value));
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})h(\d{1,2})$/', $value, $m) === 1) {
            $hour = (int) $m[1];
            $min = (int) $m[2];
            if ($hour >= 0 && $hour <= 23 && $min >= 0 && $min <= 59) {
                return sprintf('%02d:%02d', $hour, $min);
            }
            return null;
        }

        if (preg_match('/^(\d{1,2})h$/', $value, $m) === 1) {
            $hour = (int) $m[1];
            if ($hour >= 0 && $hour <= 23) {
                return sprintf('%02d:00', $hour);
            }
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $value, $m) === 1) {
            $hour = (int) $m[1];
            $min = (int) $m[2];
            if ($hour >= 0 && $hour <= 23 && $min >= 0 && $min <= 59) {
                return sprintf('%02d:%02d', $hour, $min);
            }
            return null;
        }

        return null;
    }

    /**
     * @return array{
     *   action:string,
     *   reply:?string,
     *   doctorQuery:?string,
     *   specialityQuery:?string,
     *   date:?string,
     *   time:?string
     * }
     */
    private function emptyResult(): array
    {
        return [
            'action' => 'UNKNOWN',
            'reply' => null,
            'doctorQuery' => null,
            'specialityQuery' => null,
            'date' => null,
            'time' => null,
        ];
    }
}
