<?php
// /integrations/aiChat/chatbot.php - CONVERSATIONAL AI
// Natural dialogue flow, progressive questioning

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

// ===== MAIN REQUEST HANDLER =====
function handleChatRequest()
{
    global $conn;

    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $bookingHelper = new BookingHelper($conn);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $user_message = trim($input['message'] ?? $_POST['message'] ?? '');

        if (empty($user_message) || strlen($user_message) > 500) {
            return ['success' => false, 'reply' => 'Please enter a valid message (max 500 characters).'];
        }

        // STEP 1: Check if AirLyft-related
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

        // STEP 2: Analyze intent & conversation context
        $intent = detectQuestionIntent($user_message);
        $is_vague = isVagueQuestion($user_message, $intent);

        // STEP 3: Vague? Ask ONE simple clarifying question instead of multiple
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

        // STEP 4: Get AI response (smart & conversational)
        $ai_response = getAIResponseWithFallback($user_message, $user_id, $bookingHelper, $intent, $conn);

        if ($user_id) {
            storeChatMessage($user_id, 'agent', $ai_response['reply'], $conn, $ai_response['is_fallback']);
        }

        return [
            'success' => true,
            'reply' => $ai_response['reply'],
            'quick_actions' => $ai_response['quick_actions'] ?? [],
            'agent' => 'Horizon',
            'ai_mode' => $ai_response['mode'],
            'fallback' => $ai_response['is_fallback'],
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

// ===== SINGLE CLARIFICATION QUESTION (NOT 3+) =====
function getOneSimpleClarification($message, $intent, $bookingHelper)
{
    $lower = strtolower($message);

    // If they just said "help" or "hi" - ask about travel plans
    if (preg_match('/^(help|hi|hello|what|hey)$/i', trim($message))) {
        return "What brings you to AirLyft today? Are you thinking about a trip soon?";
    }

    // Honeymoon hint? Ask about timing
    if (preg_match('/honeymoon|romantic|anniversary/', $lower)) {
        return "That's wonderful! When are you planning this special getaway?";
    }

    // Family hint? Ask about group size
    if (preg_match('/family|kids|children/', $lower)) {
        return "Exciting! How many people would be traveling with you?";
    }

    // Corporate hint? Ask about purpose
    if (preg_match('/corporate|business|team|meeting/', $lower)) {
        return "Great! Is this for a team retreat, strategy meeting, or executive gathering?";
    }

    // General vague? Ask about interest
    if (preg_match('/^(tell|show|give|find|search).{0,30}$/i', $message)) {
        return "I can help! Are you looking to book a trip, explore destinations, or check pricing?";
    }

    // Price inquiry? Ask about group
    if (preg_match('/price|cost|how much/', $lower)) {
        return "How many people would be flying, and which destination interests you?";
    }

    // Aircraft inquiry? Ask about use
    if (preg_match('/aircraft|plane|helicopter/', $lower)) {
        return "Are you curious about our fleet for a specific trip you're planning?";
    }

    // Default: Start simple
    return "Tell me a bit more - are you planning a romantic getaway, family vacation, or corporate trip?";
}

// ===== DUAL-MODE AI RESPONSE =====
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

// ===== OLLAMA AI ENGINE =====
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
    $user_name = $bookingHelper->getUserFullName() ?? "Guest";
    
    return <<<PROMPT
You are Horizon, AirLyft's friendly AI travel assistant. Have a natural conversation.

USER: $user_name
INTENT: $intent
QUESTION: "$question"

CONVERSATION STYLE:
- Be warm and conversational, like a travel expert friend
- Give relevant info about AirLyft
- If they need more details, suggest ONE next step
- Don't overwhelm with 10 options - be helpful and focused
- Include 1-2 [quick action] buttons that help them move forward
- Use their name occasionally if known

KNOWLEDGE:
$knowledge

Remember: Natural conversation > Information dump. Help them take the next step.
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
            'num_predict' => 600,
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
    $response = preg_replace('/^(Assistant:|Horizon:|Based on|According to).{0,60}/mi', '', $response);
    $response = preg_replace('/\n{3,}/', "\n\n", $response);
    $response = str_replace('* ', '• ', $response);
    
    if (!preg_match('/[.!?]\s*$/', $response)) {
        $response .= '.';
    }
    
    return trim($response);
}

function isValidResponse($response, $question)
{
    if (strlen($response) < 25) return false;
    $lower = strtolower($response);
    return (strpos($lower, 'airlyft') !== false || 
            strpos($lower, 'destination') !== false ||
            strpos($lower, 'aircraft') !== false ||
            strpos($lower, 'booking') !== false);
}

function extractQuickActions($response, $intent)
{
    $actions = [];
    if (preg_match_all('/\[([^\]]+)\]/', $response, $matches)) {
        foreach ($matches[1] as $action) {
            $actions[] = $action;
        }
    }
    
    if (empty($actions)) {
        // Max 2 actions, not 3+
        switch ($intent) {
            case 'BOOKING_PROCESS':
                $actions = ['Start Booking', 'See Destinations'];
                break;
            case 'PRICING_INQUIRY':
                $actions = ['Get Quote', 'Compare Aircraft'];
                break;
            case 'DESTINATION_INQUIRY':
                $actions = ['Book Now', 'See Destinations'];
                break;
            default:
                $actions = ['Learn More', 'Book Now'];
        }
    }
    
    return array_slice($actions, 0, 2); // Max 2 actions
}

// ===== VAGUE QUESTION DETECTION =====
function isVagueQuestion($message, $intent)
{
    $lower = strtolower(trim($message));
    
    // Single word
    if (strlen($message) < 5 || preg_match('/^(help|what|where|when|why|how|hello|hi|hey)$/i', $lower)) {
        return true;
    }
    
    // Very open-ended
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

// ===== INTENT DETECTION =====
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
        'airlyft', 'booking', 'flight', 'aircraft', 'destination', 'private', 'charter',
        'amanpulo', 'balesin', 'huma', 'cessna', 'helicopter', 'resort', 'island',
        'palawan', 'package', 'honeymoon', 'family', 'luxury', 'trip', 'travel', 'book'
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

// ===== CONVERSATIONAL FALLBACK (Natural, not robotic) =====
function getConversationalFallback($question, $bookingHelper, $intent)
{
    $lower = strtolower($question);
    $user_name = $bookingHelper->getUserFirstName();
    $greeting = $user_name ? "Hi $user_name! " : "";

    // Honeymoon/Romantic
    if (preg_match('/honeymoon|romantic|anniversary|couple/', $lower)) {
        return [
            'reply' => $greeting . "How exciting! For a romantic getaway, I'd highly recommend **Amanpulo in Palawan** – a private island with butler service. We're actually offering 15% off honeymoon packages right now. 💕\n\nIt's about a 50-minute flight from Manila on our Cessna 206 or a quick helicopter ride if you want pure luxury.\n\nWhat dates were you thinking?",
            'quick_actions' => ['See Amanpulo', 'Other Romance Spots']
        ];
    }

    // Family
    if (preg_match('/family|kids|children|group travel/', $lower)) {
        return [
            'reply' => $greeting . "Family trips are the best! **Balesin Island** is perfect – it has 7 different themed villages so there's always something new to explore.\n\nOr if you want a more beach-focused vibe, **Shangri-La Boracay** has kids' activities and water sports.\n\nHow many of you would be traveling?",
            'quick_actions' => ['Balesin Island', 'Shangri-La']
        ];
    }

    // Booking/Process
    if (preg_match('/book|reserve|how.*book|process|steps/', $lower)) {
        return [
            'reply' => $greeting . "Booking with AirLyft is super simple! Here's how it works:\n\n1️⃣ Pick your destination (12 amazing options)\n2️⃣ Choose your aircraft\n3️⃣ Select dates\n4️⃣ Add passenger details\n5️⃣ Get your quote\n6️⃣ Pay securely via PayPal\n7️⃣ You're confirmed!\n8️⃣ We handle everything else\n\nReady to start planning?",
            'quick_actions' => ['Start Booking', 'Ask Questions']
        ];
    }

    // Pricing
    if (preg_match('/price|cost|rate|how much|expensive|budget/', $lower)) {
        return [
            'reply' => $greeting . "Our pricing is straightforward:\n\n• **Cessna 206**: ₱45,000+ (1-5 people, great value)\n• **Cessna Grand Caravan**: ₱85,000+ (6-10 people, spacious)\n• **Airbus H160 Helicopter**: ₱120,000+ (luxury experience)\n• **Sikorsky S-76D**: ₱150,000+ (ultimate executive travel)\n\nHoneymoon packages get 15% off, and families of 6+ get group discounts.\n\nWhich aircraft interests you?",
            'quick_actions' => ['Get Custom Quote', 'Compare Options']
        ];
    }

    // Aircraft
    if (preg_match('/aircraft|plane|helicopter|fleet|vehicle/', $lower)) {
        return [
            'reply' => $greeting . "We have 4 beautiful aircraft:\n\n**Cessna 206** – Perfect for couples or small groups (1-5 people). Economical and fast.\n\n**Cessna Grand Caravan** – Family-friendly, spacious cabin for 6-10 people.\n\n**Airbus H160** – Our luxury helicopter. Quiet, modern, stunning views.\n\n**Sikorsky S-76D** – For VIPs and executives. Pure luxury and speed.\n\nWhich type of travel are you planning?",
            'quick_actions' => ['See All Details', 'Check Pricing']
        ];
    }

    // Destinations
    if (preg_match('/destination|where|island|location|place/', $lower)) {
        return [
            'reply' => $greeting . "We serve 12 amazing destinations! A few favorites:\n\n🏝️ **Amanpulo** (Palawan) – Private island, ultimate privacy\n🏖️ **Boracay** – Beach paradise, great for families\n🌿 **Siargao** – Surfing and eco-resorts\n🏔️ **Baguio** – Cool mountains, corporate retreats\n\nEach has its own vibe. What kind of experience are you after?",
            'quick_actions' => ['See All 12', 'Recommend One']
        ];
    }

    // Default
    return [
        'reply' => $greeting . "I'm here to help with AirLyft! Whether you're planning a romantic escape, family adventure, or business retreat, we've got the perfect destination and aircraft.\n\nWhat brings you to AirLyft today?",
        'quick_actions' => ['Explore Destinations', 'See Pricing']
    ];
}

// ===== DATABASE STORAGE =====
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