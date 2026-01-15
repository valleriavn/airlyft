<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_path = __DIR__ . '/../../db/connect.php';
if (!file_exists($db_path)) {
    ob_clean();
    die(json_encode(['success' => false, 'reply' => 'Database connection error.'], JSON_UNESCAPED_UNICODE));
}

require_once $db_path;
require_once __DIR__ . '/bookingHelper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = handleChatRequest();
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

ob_clean();
echo json_encode(['success' => false, 'reply' => 'Invalid request']);

function handleChatRequest()
{
    global $conn;

    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $bookingHelper = new BookingHelper($conn);

        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'reply' => 'Invalid request format.'];
        }

        $user_message = trim($input['message'] ?? $_POST['message'] ?? '');
        $user_message = strip_tags($user_message);
        $user_message = preg_replace('/[^\p{L}\p{N}\s.,!?\-:;()]/u', '', $user_message);

        if (empty($user_message) || strlen($user_message) < 2) {
            return ['success' => false, 'reply' => 'Please enter a message.'];
        }

        if (strlen($user_message) > 500) {
            return ['success' => false, 'reply' => 'Message too long. Please keep it under 500 characters.'];
        }

        $airlyftCheck = isAirLyftRelated($user_message);
        if (!$airlyftCheck['is_airlyft']) {
            return [
                'success' => true,
                'reply' => getAirLyftOnlyMessage(),
                'agent' => 'Horizon',
                'ai_mode' => 'fallback'
            ];
        }

        $user_message_clean = htmlspecialchars($user_message, ENT_QUOTES, 'UTF-8');

        if ($user_id) {
            storeChatMessage($user_id, 'user', $user_message_clean, $conn);
        }

        $intent = detectQuestionIntent($user_message);
        $is_vague = isVagueQuestion($user_message, $intent);

        if ($is_vague) {
            $clarification = getOneSimpleClarification($user_message, $intent, $bookingHelper);
            if ($user_id) {
                storeChatMessage($user_id, 'agent', $clarification, $conn);
            }
            return [
                'success' => true,
                'reply' => $clarification,
                'agent' => 'Horizon',
                'ai_mode' => 'clarification',
                'requires_input' => true
            ];
        }

        $ai_response = getAIResponseWithFallback($user_message, $user_id, $bookingHelper, $intent, $conn);

        if (empty($ai_response['reply'])) {
            $ai_response['reply'] = "I'm here to help with AirLyft travel. What would you like to know?";
        }

        $reply_clean = htmlspecialchars($ai_response['reply'], ENT_QUOTES, 'UTF-8');
        $reply_clean = preg_replace('/\s+/', ' ', $reply_clean);
        $reply_clean = trim($reply_clean);

        if ($user_id) {
            storeChatMessage($user_id, 'agent', $reply_clean, $conn, $ai_response['is_fallback']);
        }

        $quick_actions = [];
        if (!empty($ai_response['quick_actions']) && is_array($ai_response['quick_actions'])) {
            foreach ($ai_response['quick_actions'] as $action) {
                $action_clean = trim(strip_tags($action));
                if (strlen($action_clean) > 0 && strlen($action_clean) <= 30) {
                    $quick_actions[] = $action_clean;
                }
            }
        }

        return [
            'success' => true,
            'reply' => $reply_clean,
            'quick_actions' => array_slice($quick_actions, 0, 1),
            'agent' => 'Horizon',
            'ai_mode' => $ai_response['mode'] ?? 'fallback',
            'fallback' => $ai_response['is_fallback'] ?? true,
            'model' => $ai_response['model'] ?? null
        ];
    } catch (Exception $e) {
        error_log("Chatbot error: " . $e->getMessage());
        return [
            'success' => false,
            'reply' => 'Error processing request. Please try again.',
            'agent' => 'Horizon',
            'ai_mode' => 'error'
        ];
    }
}

function getOneSimpleClarification($message, $intent, $bookingHelper)
{
    $lower = strtolower($message);

    if (preg_match('/^(help|hi|hello|what|hey)$/i', trim($message))) {
        return "What brings you to AirLyft today? Are you thinking about a trip soon?";
    }

    if (preg_match('/honeymoon|romantic|anniversary/', $lower)) {
        return "That's wonderful! When are you planning this special getaway?";
    }

    if (preg_match('/family|kids|children/', $lower)) {
        return "Exciting! How many people would be traveling with you?";
    }

    if (preg_match('/corporate|business|team|meeting/', $lower)) {
        return "Great! Is this for a team retreat, strategy meeting, or executive gathering?";
    }

    if (preg_match('/^(tell|show|give|find|search).{0,30}$/i', $message)) {
        return "I can help! Are you looking to book a trip, explore destinations, or check pricing?";
    }

    if (preg_match('/price|cost|how much/', $lower)) {
        return "How many people would be flying, and which destination interests you?";
    }

    if (preg_match('/aircraft|plane|helicopter/', $lower)) {
        return "Are you curious about our fleet for a specific trip you're planning?";
    }

    return "Tell me a bit more - are you planning a romantic getaway, family vacation, or corporate trip?";
}

function getAIResponseWithFallback($question, $user_id, $bookingHelper, $intent, $conn)
{
    $ollama_response = getOllamaResponse($question, $user_id, $bookingHelper, $intent);
    if ($ollama_response['success']) {
        return [
            'reply' => $ollama_response['reply'],
            'is_fallback' => false,
            'mode' => 'ollama',
            'model' => $ollama_response['model'] ?? 'mistral',
            'quick_actions' => $ollama_response['quick_actions'] ?? []
        ];
    }

    error_log("⚠️ Ollama unavailable, using enhanced fallback");
    $fallback = getConversationalFallback($question, $bookingHelper, $intent);

    return [
        'reply' => $fallback['reply'],
        'is_fallback' => true,
        'mode' => 'enhanced',
        'model' => 'fallback',
        'quick_actions' => $fallback['quick_actions'] ?? []
    ];
}

function getOllamaResponse($question, $user_id, $bookingHelper, $intent)
{
    error_log("🔍 Checking Ollama...");

    if (!isOllamaAvailable()) {
        return ['success' => false];
    }

    $knowledge_file = __DIR__ . '/data.txt';
    if (!file_exists($knowledge_file)) {
        return ['success' => false];
    }

    $knowledge = @file_get_contents($knowledge_file);
    if (empty($knowledge)) {
        return ['success' => false];
    }

    $prompt = buildConversationalPrompt($knowledge, $question, $bookingHelper, $intent);
    error_log("📝 Building conversational prompt");

    $models = ['mistral:latest', 'gemma3:4b', 'llama3.2:latest'];

    foreach ($models as $model) {
        error_log("🔄 Trying: $model");
        $raw_reply = callOllamaAPI($prompt, $model);

        if ($raw_reply) {
            $clean_reply = cleanAIResponse($raw_reply);

            if (isValidResponse($clean_reply, $question)) {
                error_log("✅ Response OK");
                $quick_actions = extractQuickActions($clean_reply, $intent);

                return [
                    'success' => true,
                    'reply' => $clean_reply,
                    'model' => $model,
                    'quick_actions' => $quick_actions
                ];
            }
        }
    }

    return ['success' => false];
}

function buildConversationalPrompt($knowledge, $question, $bookingHelper, $intent)
{
    $user_name = $bookingHelper->getUserFirstName() ?? "Guest";

    return <<<PROMPT
You are Horizon, AirLyft's AI travel assistant. Be concise but detailed.

USER: $user_name
INTENT: $intent
QUESTION: "$question"

RULES:
- Keep responses under 120 words
- Provide specific details: destination names, aircraft types, prices, features
- Answer the question directly with relevant facts
- Include key information: flight duration, capacity, unique features
- Be friendly but professional
- Only mention AirLyft services from the knowledge base
- Use clear, simple language

KNOWLEDGE:
$knowledge

Respond with concise but detailed information. Include specific facts and numbers.
PROMPT;
}

function callOllamaAPI($prompt, $model = 'mistral:latest')
{
    $url = 'http://127.0.0.1:11434/api/generate';
    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.4,
            'top_p' => 0.85,
            'num_predict' => 300,
            'repeat_penalty' => 1.15,
            'top_k' => 30
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_NOSIGNAL => 1
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || empty($response)) {
        return null;
    }

    $data = json_decode($response, true);
    if (!isset($data['response'])) {
        return null;
    }

    $result = trim($data['response']);
    return !empty($result) ? $result : null;
}

function isOllamaAvailable()
{
    $ch = curl_init('http://127.0.0.1:11434/api/tags');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code === 200 && !empty($response));
}

// ===== RESPONSE PROCESSING =====
function cleanAIResponse($response)
{
    if (empty($response)) return '';

    $response = trim($response);
    $response = preg_replace('/^(Assistant:|Horizon:|Based on|According to|I can|I\'m).{0,80}/mi', '', $response);
    $response = preg_replace('/\n{3,}/', "\n\n", $response);
    $response = preg_replace('/\n{2,}/', "\n", $response);
    $response = str_replace(['* ', '- ', '• '], '', $response);
    $response = preg_replace('/\s+/', ' ', $response);

    if (strlen($response) > 500) {
        $response = substr($response, 0, 497) . '...';
    }

    if (!preg_match('/[.!?]\s*$/', $response)) {
        $response .= '.';
    }

    return trim($response);
}

function isValidResponse($response, $question)
{
    if (empty($response) || strlen($response) < 20) return false;
    if (strlen($response) > 600) return false;

    $lower = strtolower($response);
    $lower_question = strtolower($question);

    $has_relevant_content = (
        strpos($lower, 'airlyft') !== false ||
        strpos($lower, 'destination') !== false ||
        strpos($lower, 'aircraft') !== false ||
        strpos($lower, 'booking') !== false ||
        strpos($lower, 'flight') !== false ||
        strpos($lower, 'travel') !== false
    );

    $has_question_words = (
        strpos($lower_question, 'what') !== false ||
        strpos($lower_question, 'how') !== false ||
        strpos($lower_question, 'where') !== false ||
        strpos($lower_question, 'when') !== false ||
        strpos($lower_question, 'why') !== false
    );

    return $has_relevant_content || !$has_question_words;
}

function extractQuickActions($response, $intent)
{
    $actions = [];

    if (preg_match_all('/\[([^\]]+)\]/', $response, $matches)) {
        foreach ($matches[1] as $action) {
            if (strlen($action) <= 25) {
                $actions[] = trim($action);
            }
        }
    }

    if (empty($actions) && in_array($intent, ['BOOKING', 'BOOKING_PROCESS', 'DESTINATION_INQUIRY', 'PRICING_INQUIRY'])) {
        switch ($intent) {
            case 'BOOKING':
            case 'BOOKING_PROCESS':
                $actions = ['Start Booking'];
                break;
            case 'PRICING_INQUIRY':
                $actions = ['Get Quote'];
                break;
            case 'DESTINATION_INQUIRY':
                $actions = ['View Destinations'];
                break;
        }
    }

    return array_slice($actions, 0, 1);
}

// ===== VAGUE QUESTION DETECTION =====
function isVagueQuestion($message, $intent)
{
    $lower = strtolower(trim($message));

    if (strlen($message) < 5 || preg_match('/^(help|what|where|when|why|how|hello|hi|hey)$/i', $lower)) {
        return true;
    }

    $vague_patterns = [
        '/^(tell|give|show|find).{0,25}$/i',
        '/^(what|which|do you).{0,15}$/i',
        '/^(i want|i need|i\'m looking).{0,10}$/i'
    ];

    foreach ($vague_patterns as $pattern) {
        if (preg_match($pattern, $lower)) return true;
    }

    return false;
}

function detectQuestionIntent($question)
{
    $lower = strtolower($question);

    if (preg_match('/honeymoon|romantic|anniversary/', $lower)) return 'HONEYMOON';
    if (preg_match('/family|kids|children|group/', $lower)) return 'FAMILY';
    if (preg_match('/corporate|business|executive/', $lower)) return 'CORPORATE';
    if (preg_match('/wellness|detox|yoga|meditation/', $lower)) return 'WELLNESS';
    if (preg_match('/book|reserve|how.*book/', $lower)) return 'BOOKING';
    if (preg_match('/price|cost|rate|quote|expensive/', $lower)) return 'PRICING';
    if (preg_match('/aircraft|plane|helicopter|fleet/', $lower)) return 'AIRCRAFT';
    if (preg_match('/destination|where|island|location/', $lower)) return 'DESTINATION';

    return 'GENERAL';
}

function isAirLyftRelated($message)
{
    $lower = strtolower($message);
    $keywords = [
        'airlyft',
        'booking',
        'flight',
        'aircraft',
        'destination',
        'private',
        'charter',
        'amanpulo',
        'balesin',
        'huma',
        'cessna',
        'helicopter',
        'resort',
        'island',
        'palawan',
        'package',
        'honeymoon',
        'family',
        'luxury',
        'trip',
        'travel',
        'book'
    ];

    foreach ($keywords as $kw) {
        if (strpos($lower, $kw) !== false) return ['is_airlyft' => true];
    }

    return ['is_airlyft' => false];
}

function getAirLyftOnlyMessage()
{
    return "I only help with AirLyft luxury travel. 🏝️\n\n" .
        "I can tell you about our destinations, aircraft, booking process, and pricing.\n\n" .
        "What would you like to know?";
}

function getConversationalFallback($question, $bookingHelper, $intent)
{
    $lower = strtolower($question);
    $user_name = $bookingHelper->getUserFirstName();
    $greeting = $user_name ? "Hi $user_name! " : "";

    if (preg_match('/honeymoon|romantic|anniversary|couple/', $lower)) {
        return [
            'reply' => $greeting . "For a romantic getaway, I recommend **Amanpulo in Palawan** – a private island with butler service, powder-white sand, and secluded villas. Flight time: 50 minutes from Manila. **Huma Island Resort** offers overwater villas with direct water access. **Amorita Resort** in Bohol features infinity pools and panoramic ocean views. Which destination interests you?",
            'quick_actions' => ['Start Booking']
        ];
    }

    if (preg_match('/family|kids|children|group travel/', $lower)) {
        return [
            'reply' => $greeting . "**Balesin Island** features 7 themed villages (Greek, Thai, Bali, Italian, French, Swiss, Philippine) perfect for families. **Shangri-La Boracay** offers kids' activities, water sports, and multiple pools. Both accommodate large groups. How many people would be traveling?",
            'quick_actions' => ['View Destinations']
        ];
    }

    if (preg_match('/book|reserve|how.*book|process|steps/', $lower)) {
        return [
            'reply' => $greeting . "Booking process: 1) Choose destination (12 options), 2) Select aircraft (4 types), 3) Pick dates, 4) Add passenger details, 5) Get quote, 6) Pay via PayPal, 7) Receive confirmation, 8) Enjoy door-to-resort service. Ready to start?",
            'quick_actions' => ['Start Booking']
        ];
    }

    if (preg_match('/price|cost|rate|how much|expensive|budget/', $lower)) {
        return [
            'reply' => $greeting . "Aircraft pricing: **Cessna 206** from ₱45,000 (1-5 passengers, 1,000km range), **Grand Caravan** from ₱85,000 (6-10 passengers, 1,700km range), **Airbus H160** from ₱120,000 (1-8 passengers, luxury helicopter), **Sikorsky S-76D** from ₱150,000 (1-6 passengers, executive). Which aircraft fits your group?",
            'quick_actions' => ['Get Quote']
        ];
    }

    if (preg_match('/aircraft|plane|helicopter|fleet|vehicle/', $lower)) {
        return [
            'reply' => $greeting . "We have 4 aircraft: **Cessna 206** (single-engine, 1-5 people, economical), **Grand Caravan EX** (turboprop, 6-10 people, spacious), **Airbus H160** (twin-engine helicopter, 1-8 people, luxury with panoramic windows), **Sikorsky S-76D** (twin-engine helicopter, 1-6 people, executive comfort). Which fits your travel needs?",
            'quick_actions' => ['View Fleet']
        ];
    }

    if (preg_match('/destination|where|island|location|place/', $lower)) {
        return [
            'reply' => $greeting . "We serve 12 destinations: **Amanpulo** (Palawan - private island), **Boracay** (beach paradise), **Siargao** (surfing and eco-resorts), **Baguio** (cool mountains), **Balesin Island** (7 themed villages), **Huma Island** (overwater villas), plus 6 more. What type of experience are you looking for?",
            'quick_actions' => ['View Destinations']
        ];
    }

    return [
        'reply' => $greeting . "I'm here to help with AirLyft travel! What would you like to know about our destinations, aircraft, or booking process?",
        'quick_actions' => []
    ];
}

function storeChatMessage($user_id, $sender, $message, $conn, $is_fallback = false)
{
    if (!$user_id || !$conn) return;

    try {
        $result = $conn->query("SHOW TABLES LIKE 'ai_chat_messages'");
        if ($result && $result->num_rows === 0) {
            return;
        }

        $stmt = $conn->prepare("
            INSERT INTO ai_chat_messages 
            (user_id, sender_type, message, is_fallback, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");

        if ($stmt) {
            $is_fallback_int = $is_fallback ? 1 : 0;
            $stmt->bind_param("issi", $user_id, $sender, $message, $is_fallback_int);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Store message error: " . $e->getMessage());
    }
}
