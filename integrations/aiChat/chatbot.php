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

        // Check if message is empty or just whitespace
        if (empty($user_message) || trim($user_message) === '') {
            return ['success' => false, 'reply' => 'Please enter a message.'];
        }

        // Accept numbers (for passenger counts) and short valid answers
        $is_number = preg_match('/^\d+$/', $user_message);
        $is_valid_short = strlen($user_message) >= 1 && ($is_number || strlen($user_message) >= 2);

        if (!$is_valid_short) {
            return ['success' => false, 'reply' => 'Please enter a message.'];
        }

        if (strlen($user_message) > 500) {
            return ['success' => false, 'reply' => 'Message too long. Please keep it under 500 characters.'];
        }

        // Check if this is a follow-up answer
        $is_followup_answer = isFollowUpAnswer($user_message);

        // Check if AI is available
        $ai_available = isOllamaAvailable();

        // If AI is not available and it's not a basic query, suggest setup
        if (!$ai_available && !$is_followup_answer && !isBasicQuery($user_message)) {
            return [
                'success' => true,
                'reply' => getAIUnavailableMessage($bookingHelper),
                'agent' => 'Horizon',
                'ai_mode' => 'offline',
                'requires_setup' => true
            ];
        }

        // If it's a follow-up answer or AirLyft related, process it
        $airlyftCheck = isAirLyftRelated($user_message);
        if (!$airlyftCheck['is_airlyft'] && !$is_followup_answer) {
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

        // Handle follow-up answers (like passenger counts)
        if ($is_followup_answer) {
            $followup_response = handleFollowUpAnswer($user_message, $bookingHelper);
            if ($followup_response) {
                if ($user_id) {
                    storeChatMessage($user_id, 'agent', $followup_response['reply'], $conn);
                }
                return [
                    'success' => true,
                    'reply' => $followup_response['reply'],
                    'agent' => 'Horizon',
                    'ai_mode' => 'followup'
                ];
            }
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

        // Get response - use AI if available, otherwise fallback
        if ($ai_available) {
            $ai_response = getAIResponseWithFallback($user_message, $user_id, $bookingHelper, $intent, $conn);
        } else {
            $ai_response = getConversationalFallback($user_message, $bookingHelper, $intent);
            $ai_response['is_fallback'] = true;
            $ai_response['mode'] = 'offline';
        }

        if (empty($ai_response['reply'])) {
            $ai_response['reply'] = "I'm here to help with AirLyft travel. What would you like to know?";
        }

        // Final cleaning and validation
        $reply_clean = $ai_response['reply'];
        $reply_clean = htmlspecialchars($reply_clean, ENT_QUOTES, 'UTF-8');
        $reply_clean = preg_replace('/\s+/', ' ', $reply_clean);
        $reply_clean = trim($reply_clean);

        // Ensure readable length
        if (strlen($reply_clean) > 450) {
            $cut = substr($reply_clean, 0, 447);
            $last_period = strrpos($cut, '.');
            if ($last_period > 200) {
                $reply_clean = substr($reply_clean, 0, $last_period + 1);
            } else {
                $reply_clean = substr($reply_clean, 0, 447) . '...';
            }
        }

        if ($user_id) {
            storeChatMessage($user_id, 'agent', $reply_clean, $conn, $ai_response['is_fallback'] ?? true);
        }

        return [
            'success' => true,
            'reply' => $reply_clean,
            'agent' => 'Horizon',
            'ai_mode' => $ai_response['mode'] ?? 'fallback',
            'fallback' => $ai_response['is_fallback'] ?? true,
            'model' => $ai_response['model'] ?? null,
            'requires_setup' => !$ai_available && !isBasicQuery($user_message)
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

function isBasicQuery($message)
{
    $lower = strtolower($message);
    $basic_patterns = [
        '/^(hello|hi|hey|greetings)/i',
        '/^(help|support)/i',
        '/^(book|booking)/i',
        '/^(price|pricing|cost|how much)/i',
        '/^(destination|where)/i',
        '/^(aircraft|plane|helicopter)/i'
    ];

    foreach ($basic_patterns as $pattern) {
        if (preg_match($pattern, $lower)) {
            return true;
        }
    }
    return false;
}

function getAIUnavailableMessage($bookingHelper)
{
    $user_name = $bookingHelper->getUserFirstName();
    $greeting = $user_name ? "Hi $user_name! " : "Hello! ";

    return $greeting . "I'm currently in basic mode. For enhanced AI assistance with detailed recommendations, please set up Ollama. Would you like to see the setup guide? You can also ask me basic questions about booking, destinations, or pricing.";
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
            'mode' => 'ai',
            'model' => $ollama_response['model'] ?? 'mistral'
        ];
    }

    error_log("⚠️ Ollama unavailable, using enhanced fallback");
    $fallback = getConversationalFallback($question, $bookingHelper, $intent);

    return [
        'reply' => $fallback['reply'],
        'is_fallback' => true,
        'mode' => 'fallback',
        'model' => 'fallback'
    ];
}

function getOllamaResponse($question, $user_id, $bookingHelper, $intent)
{
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

    // Try only the fastest model first - if unavailable, fallback is faster
    $model = 'mistral:latest';
    $raw_reply = callOllamaAPI($prompt, $model);

    if ($raw_reply) {
        $clean_reply = cleanAIResponse($raw_reply);

        if (isValidResponse($clean_reply, $question)) {
            return [
                'success' => true,
                'reply' => $clean_reply,
                'model' => $model
            ];
        }
    }

    return ['success' => false];
}

function buildConversationalPrompt($knowledge, $question, $bookingHelper, $intent)
{
    $user_name = $bookingHelper->getUserFirstName() ?? "Guest";

    return <<<PROMPT
You are Horizon, AirLyft's AI travel assistant. Answer concisely with short, clear sentences.

USER: $user_name
INTENT: $intent
QUESTION: "$question"

RULES:
- Maximum 80 words total, be brief and relevant
- Write in short sentences, maximum 20 words per sentence
- Use periods to separate ideas, not commas for long lists
- No special characters, no markdown, no emojis, no symbols
- No bold text, no asterisks, no dashes for lists
- Answer directly with specific facts from knowledge base
- Include key details: names, prices, durations, capacities
- Be professional and friendly
- Only use information from the knowledge base below
- Write in plain English, simple sentences

KNOWLEDGE:
$knowledge

Provide a concise answer with short, readable sentences separated by periods.
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
            'temperature' => 0.3,
            'top_p' => 0.9,
            'num_predict' => 150,
            'repeat_penalty' => 1.2,
            'top_k' => 40
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 2,
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
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_FAILONERROR => true
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

    // Remove common prefixes
    $response = preg_replace('/^(Assistant:|Horizon:|Based on|According to|I can|I\'m|Here\'s|Sure,|Of course).{0,100}/mi', '', $response);

    // Remove markdown formatting
    $response = preg_replace('/\*\*(.*?)\*\*/', '$1', $response);
    $response = preg_replace('/\*(.*?)\*/', '$1', $response);
    $response = preg_replace('/`(.*?)`/', '$1', $response);
    $response = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $response);

    // Remove list markers and special characters
    $response = str_replace(['* ', '- ', '• ', '→', '→ ', '✓', '✗', '•', '▪', '▸'], '', $response);

    // Remove emojis and special unicode characters
    $response = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $response);
    $response = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $response);
    $response = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $response);
    $response = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $response);
    $response = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $response);

    // Remove multiple newlines and convert to spaces first
    $response = preg_replace('/\n{2,}/', '. ', $response);
    $response = preg_replace('/\n/', ' ', $response);

    // Remove special characters except basic punctuation
    $response = preg_replace('/[^\p{L}\p{N}\s.,!?;:()\-\'\"]/u', '', $response);

    // Clean up extra spaces
    $response = preg_replace('/\s+/', ' ', $response);
    $response = trim($response);

    // Break long sentences into shorter, more readable sentences
    $sentences = preg_split('/[.!?]+\s+/', $response, -1, PREG_SPLIT_NO_EMPTY);
    $formatted_sentences = [];

    foreach ($sentences as $sentence) {
        $sentence = trim($sentence);
        if (empty($sentence)) continue;

        // Capitalize first letter
        $sentence = ucfirst($sentence);

        // If sentence is too long (over 120 chars), try to break at commas
        if (strlen($sentence) > 120) {
            $parts = preg_split('/,\s+/', $sentence);
            $current = '';
            foreach ($parts as $part) {
                if (strlen($current . $part) > 120 && !empty($current)) {
                    $formatted_sentences[] = trim($current) . '.';
                    $current = $part;
                } else {
                    $current .= ($current ? ', ' : '') . $part;
                }
            }
            if (!empty($current)) {
                $formatted_sentences[] = trim($current) . '.';
            }
        } else {
            $formatted_sentences[] = $sentence . '.';
        }
    }

    $response = implode(' ', $formatted_sentences);
    $response = preg_replace('/\.+/', '.', $response);
    $response = preg_replace('/\s+/', ' ', $response);
    $response = trim($response);

    // Limit total length to 350 characters for concise responses
    if (strlen($response) > 350) {
        $cut = substr($response, 0, 350);
        $last_period = strrpos($cut, '.');

        if ($last_period > 200) {
            $response = substr($response, 0, $last_period + 1);
        } else {
            $response = substr($response, 0, 347) . '...';
        }
    }

    // Ensure proper sentence ending
    if (!empty($response) && !preg_match('/[.!?]\s*$/', $response)) {
        $response .= '.';
    }

    // Ensure first letter is capitalized
    if (!empty($response)) {
        $response = ucfirst(trim($response));
    }

    return trim($response);
}

function isValidResponse($response, $question)
{
    if (empty($response) || strlen($response) < 15) return false;
    if (strlen($response) > 450) return false;

    // Must have at least one sentence with proper ending
    if (!preg_match('/[.!?]/', $response)) return false;

    // Must have readable structure (at least one space suggests words)
    if (substr_count($response, ' ') < 2) return false;

    $lower = strtolower($response);
    $lower_question = strtolower($question);

    $has_relevant_content = (
        strpos($lower, 'airlyft') !== false ||
        strpos($lower, 'destination') !== false ||
        strpos($lower, 'aircraft') !== false ||
        strpos($lower, 'booking') !== false ||
        strpos($lower, 'flight') !== false ||
        strpos($lower, 'travel') !== false ||
        strpos($lower, 'trip') !== false ||
        strpos($lower, 'resort') !== false
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

// ===== VAGUE QUESTION DETECTION =====
function isVagueQuestion($message, $intent)
{
    $lower = strtolower(trim($message));

    // If intent is detected, it's not vague
    if ($intent !== 'GENERAL') {
        // Check if message has clear context even with intent
        if (preg_match('/\b(about|tell me about|info|information|details|explain|what is|show me|give me)\b.*\b(price|pricing|cost|aircraft|destination|booking|flight|trip)\b/', $lower)) {
            return false; // Has clear topic with context
        }
    }

    // Very short messages without context
    if (strlen($message) < 5 || preg_match('/^(help|what|where|when|why|how|hello|hi|hey)$/i', $lower)) {
        return true;
    }

    // Patterns that are vague only if no clear topic
    $vague_patterns = [
        '/^(tell|give|show|find)\s+(me)?\s*$/i',
        '/^(what|which|do you)\s*$/i',
        '/^(i want|i need|i\'m looking)\s*$/i'
    ];

    foreach ($vague_patterns as $pattern) {
        if (preg_match($pattern, $lower)) return true;
    }

    // Check if message has a clear topic word
    $clear_topics = ['price', 'pricing', 'cost', 'aircraft', 'destination', 'booking', 'flight', 'trip', 'resort', 'island', 'honeymoon', 'family'];
    foreach ($clear_topics as $topic) {
        if (strpos($lower, $topic) !== false) {
            return false; // Has a clear topic, not vague
        }
    }

    return false; // Default to not vague if we can't determine
}

function detectQuestionIntent($question)
{
    $lower = strtolower(trim($question));

    // Order matters - more specific first
    if (preg_match('/honeymoon|romantic|anniversary/', $lower)) return 'HONEYMOON';
    if (preg_match('/family|kids|children|group/', $lower)) return 'FAMILY';
    if (preg_match('/corporate|business|executive/', $lower)) return 'CORPORATE';
    if (preg_match('/wellness|detox|yoga|meditation/', $lower)) return 'WELLNESS';
    if (preg_match('/\b(price|pricing|cost|costs|rate|rates|quote|quotes|fare|fares|expensive|budget|how much|what.*cost|tell me.*price|about.*price)\b/', $lower)) return 'PRICING';
    if (preg_match('/\b(book|booking|reserve|reservation|how.*book|how.*reserve|booking.*process|reservation.*process)\b/', $lower)) return 'BOOKING';
    if (preg_match('/\b(aircraft|airplane|plane|planes|helicopter|helicopters|fleet|aircraft.*type|which.*aircraft)\b/', $lower)) return 'AIRCRAFT';
    if (preg_match('/\b(destination|destinations|where.*go|which.*destination|location|place|places|island|resort|resorts|show.*destination|view.*destination|tell me.*destination)\b/', $lower)) return 'DESTINATION';

    return 'GENERAL';
}

function isAirLyftRelated($message)
{
    $lower = strtolower(trim($message));

    // First check for clear travel/service keywords
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
        'book',
        'pricing',
        'price',
        'cost',
        'fare',
        'rate',
        'quote',
        'airport',
        'plane',
        'jet',
        'rotor',
        'turboprop',
        'boracay',
        'siargao',
        'bohol',
        'baguio',
        'coron'
    ];

    foreach ($keywords as $kw) {
        if (strpos($lower, $kw) !== false) return ['is_airlyft' => true];
    }

    // Check for question patterns about travel services
    $travel_patterns = [
        '/\b(how much|what.*cost|tell me.*price|about.*price|pricing.*info)\b/',
        '/\b(where.*go|which.*destination|show.*places|view.*resort)\b/',
        '/\b(what.*aircraft|which.*plane|how.*book|booking.*process)\b/',
        '/\b(romantic.*getaway|family.*vacation|corporate.*trip)\b/'
    ];

    foreach ($travel_patterns as $pattern) {
        if (preg_match($pattern, $lower)) return ['is_airlyft' => true];
    }

    return ['is_airlyft' => false];
}

function isFollowUpAnswer($message)
{
    $lower = strtolower(trim($message));

    // Check for affirmative/negative responses
    $affirmative = ['yes', 'yeah', 'yep', 'yup', 'sure', 'okay', 'ok', 'alright', 'absolutely', 'definitely', 'of course', 'certainly'];
    $negative = ['no', 'nope', 'nah', 'not really', "don't", 'dont'];

    if (in_array($lower, $affirmative) || in_array($lower, $negative)) {
        return true;
    }

    // Check for affirmative patterns
    if (preg_match('/^(yes|yeah|yep|yup|sure|okay|ok|alright|absolutely|definitely|of course|certainly)\b/i', $lower)) {
        return true;
    }

    // Check if it's a number (potential passenger count)
    if (preg_match('/^\d+$/', $message)) {
        $num = (int)$message;
        // Valid passenger count range
        if ($num >= 1 && $num <= 20) {
            return true;
        }
    }

    // Check for patterns that indicate follow-up answers
    $followup_patterns = [
        '/^\d+\s*(people|person|passengers?|guests?|travelers?|will be|would be|traveling|travelling)\b/i',
        '/\b\d+\s*(people|person|passengers?|guests?|travelers?)\b/i',
        '/\b(just|only|about|around|approximately)\s+\d+/i',
        '/^\d+\s*$/',
    ];

    foreach ($followup_patterns as $pattern) {
        if (preg_match($pattern, $message)) {
            return true;
        }
    }

    return false;
}

function handleFollowUpAnswer($message, $bookingHelper)
{
    $lower = strtolower(trim($message));
    $user_name = $bookingHelper->getUserFirstName();
    $greeting = $user_name ? "Hi $user_name! " : "";

    // Handle affirmative responses (yes, sure, okay, etc.)
    if (preg_match('/^(yes|yeah|yep|yup|sure|okay|ok|alright|absolutely|definitely|of course|certainly)\b/i', $lower)) {
        // When user says yes to booking, guide them to start the booking process
        return [
            'reply' => $greeting . "Excellent! Let's start your booking. First, please visit our booking page or tell me which destination you'd like to visit. We have twelve destinations available including Amanpulo in Palawan, Balesin Island, Boracay, Siargao, and more. Which destination interests you?"
        ];
    }

    // Handle negative responses (no, nope, etc.)
    if (preg_match('/^(no|nope|nah|not really|not yet)\b/i', $lower)) {
        return [
            'reply' => $greeting . "No problem! Take your time to explore our destinations and aircraft options. Feel free to ask me about pricing, destinations, aircraft types, or anything else about AirLyft travel. What would you like to know?"
        ];
    }

    // Extract number from message
    preg_match('/\d+/', $message, $matches);
    $count = isset($matches[0]) ? (int)$matches[0] : 0;

    if ($count >= 1 && $count <= 20) {
        // Provide aircraft recommendations based on passenger count
        if ($count <= 5) {
            return [
                'reply' => $greeting . "Great! For {$count} " . ($count == 1 ? 'person' : 'people') . ", I recommend the Cessna 206. It accommodates up to 5 passengers and is economical for short routes. The Airbus H160 helicopter is also suitable for 1 to 8 passengers with luxury amenities. Which destination are you interested in?"
            ];
        } elseif ($count <= 8) {
            return [
                'reply' => $greeting . "Perfect! For {$count} people, I recommend the Airbus H160 helicopter. It accommodates 1 to 8 passengers with luxury panoramic windows. The Sikorsky S-76D is also suitable for 1 to 6 passengers for executive travel. Which destination interests you?"
            ];
        } elseif ($count <= 10) {
            return [
                'reply' => $greeting . "Excellent! For {$count} people, I recommend the Cessna Grand Caravan EX. It is a spacious turboprop that accommodates 6 to 10 passengers, perfect for groups. Which destination would you like to visit?"
            ];
        } else {
            return [
                'reply' => $greeting . "For {$count} people, you would need multiple aircraft or the Cessna Grand Caravan EX which handles up to 10 passengers. Groups larger than 10 may require splitting across multiple flights. Please contact us for custom arrangements. Which destination are you planning to visit?"
            ];
        }
    }

    // If it's not a valid number or pattern, return null to continue normal processing
    return null;
}

function getAirLyftOnlyMessage()
{
    return "I only help with AirLyft luxury travel. I can tell you about our destinations, aircraft, booking process, and pricing. What would you like to know?";
}

function getConversationalFallback($question, $bookingHelper, $intent)
{
    $lower = strtolower($question);
    $user_name = $bookingHelper->getUserFirstName();
    $greeting = $user_name ? "Hi $user_name! " : "";

    if (preg_match('/honeymoon|romantic|anniversary|couple/', $lower)) {
        return [
            'reply' => $greeting . "For a romantic getaway, I recommend Amanpulo in Palawan. It is a private island with butler service, powder-white sand, and secluded villas. Flight time is 50 minutes from Manila. Huma Island Resort offers overwater villas with direct water access. Amorita Resort in Bohol features infinity pools and panoramic ocean views. Which destination interests you?"
        ];
    }

    if (preg_match('/family|kids|children|group travel/', $lower)) {
        return [
            'reply' => $greeting . "Balesin Island features seven themed villages perfect for families. These include Greek, Thai, Bali, Italian, French, Swiss, and Philippine themes. Shangri-La Boracay offers kids activities, water sports, and multiple pools. Both accommodate large groups. How many people would be traveling?"
        ];
    }

    if (preg_match('/book|reserve|how.*book|process|steps/', $lower)) {
        return [
            'reply' => $greeting . "The booking process is simple. First, choose from twelve destinations. Second, select from four aircraft types. Third, pick your dates. Fourth, add passenger details. Fifth, get your quote. Sixth, pay via PayPal. Seventh, receive confirmation. Finally, enjoy door-to-resort service. Ready to start?"
        ];
    }

    if (preg_match('/\b(price|pricing|cost|costs|rate|rates|how much|what.*cost|expensive|budget|tell me.*price|about.*price)\b/', $lower)) {
        return [
            'reply' => $greeting . "Aircraft pricing starts from different rates. The Cessna 206 is from 45,000 pesos for 1 to 5 passengers with 1,000 kilometer range. The Grand Caravan is from 85,000 pesos for 6 to 10 passengers with 1,700 kilometer range. The Airbus H160 is from 120,000 pesos for 1 to 8 passengers, luxury helicopter. The Sikorsky S-76D is from 150,000 pesos for 1 to 6 passengers, executive class. Which aircraft fits your group?"
        ];
    }

    if (preg_match('/aircraft|plane|helicopter|fleet|vehicle/', $lower)) {
        return [
            'reply' => $greeting . "We have four aircraft types. The Cessna 206 is a single-engine aircraft for 1 to 5 people, economical for short routes. The Grand Caravan EX is a turboprop for 6 to 10 people, spacious for groups. The Airbus H160 is a luxury twin-engine helicopter for 1 to 8 people with panoramic windows. The Sikorsky S-76D is an executive twin-engine helicopter for 1 to 6 people. Which fits your travel needs?"
        ];
    }

    if (preg_match('/destination|where|island|location|place/', $lower)) {
        return [
            'reply' => $greeting . "We serve twelve destinations. Amanpulo in Palawan is a private island. Boracay is a beach paradise. Siargao offers surfing and eco-resorts. Baguio provides cool mountain climate. Balesin Island has seven themed villages. Huma Island features overwater villas. We have six more destinations available. What type of experience are you looking for?"
        ];
    }

    // General queries
    return [
        'reply' => $greeting . "I'm here to help with AirLyft travel! What would you like to know about our destinations, aircraft, or booking process?"
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
