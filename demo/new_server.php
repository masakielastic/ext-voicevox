<?php
/**
 * VOICEVOX API Server (OOP Version)
 * PHP Built-in Server + VOICEVOX Extension with OOP Interface
 * 
 * Usage:
 * php -d extension=modules/voicevox.so -S localhost:8080 new_server.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

// VOICEVOX設定
define('VOICEVOX_LIB_PATH', '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so');
define('VOICEVOX_DICT_PATH', '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11');

// グローバルエンジンインスタンス
$voicevox_engine = null;

/**
 * VOICEVOX Engine初期化（一度だけ実行）
 */
function initialize_voicevox_engine() {
    global $voicevox_engine;
    
    if ($voicevox_engine && $voicevox_engine->isInitialized()) {
        return true;
    }
    
    if (!extension_loaded('voicevox')) {
        error_log('VOICEVOX extension not loaded');
        return false;
    }
    
    try {
        $voicevox_engine = Engine::getInstance();
        
        if ($voicevox_engine->isInitialized()) {
            return true;
        }
        
        $start_time = microtime(true);
        $result = $voicevox_engine->initialize(VOICEVOX_LIB_PATH, VOICEVOX_DICT_PATH);
        $init_time = microtime(true) - $start_time;
        
        if ($result) {
            $version = $voicevox_engine->getVersion();
            error_log("VOICEVOX initialized successfully (v{$version}) in " . sprintf('%.2f', $init_time) . " seconds");
            return true;
        } else {
            error_log('VOICEVOX initialization failed');
            return false;
        }
        
    } catch (VoicevoxException $e) {
        error_log('VOICEVOX Exception: ' . $e->getMessage() . ' (Code: ' . $e->getVoicevoxCode() . ')');
        return false;
    } catch (Exception $e) {
        error_log('General Exception: ' . $e->getMessage());
        return false;
    }
}

/**
 * VOICEVOXエンジンを取得
 */
function get_voicevox_engine() {
    global $voicevox_engine;
    
    if (!$voicevox_engine || !$voicevox_engine->isInitialized()) {
        if (!initialize_voicevox_engine()) {
            throw new Exception('VOICEVOX engine not available');
        }
    }
    
    return $voicevox_engine;
}

/**
 * JSONレスポンスを送信
 */
function send_json_response($data, $status_code = 200) {
    // エラーログ出力（デバッグ用）
    error_log("Sending JSON response: " . json_encode($data));
    
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if ($json === false) {
        // JSON エンコードエラーの場合
        error_log("JSON encode error: " . json_last_error_msg());
        $error_response = json_encode(['error' => 'JSON encoding failed', 'status' => 'error']);
        echo $error_response;
    } else {
        echo $json;
    }
    exit;
}

/**
 * エラーレスポンスを送信
 */
function send_error_response($message, $status_code = 400) {
    send_json_response([
        'error' => $message,
        'status' => 'error',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_type' => 'OOP'
    ], $status_code);
}

/**
 * WAVファイルレスポンスを送信
 */
function send_wav_response($wav_data, $filename = 'voice.wav') {
    header('Content-Type: audio/wav');
    header('Content-Length: ' . strlen($wav_data));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Access-Control-Allow-Origin: *');
    echo $wav_data;
    exit;
}

/**
 * POSTデータを取得
 */
function get_post_data() {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        return json_decode($json, true);
    }
    
    return $_POST;
}

// CORSプリフライト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}

// ルーティング
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// 初期化確認
try {
    if (!initialize_voicevox_engine()) {
        send_error_response('VOICEVOX engine initialization failed', 500);
    }
} catch (Exception $e) {
    send_error_response('VOICEVOX engine error: ' . $e->getMessage(), 500);
}

switch ($uri) {
    case '/':
    case '/status':
        // ステータス確認
        try {
            $engine = get_voicevox_engine();
            send_json_response([
                'status' => 'ok',
                'service' => 'VOICEVOX API Server (OOP)',
                'version' => $engine->getVersion(),
                'initialized' => $engine->isInitialized(),
                'implementation' => 'OOP Interface',
                'timestamp' => date('Y-m-d H:i:s'),
                'endpoints' => [
                    'GET /status' => 'Service status',
                    'GET /speakers' => 'Get available speakers',
                    'POST /tts' => 'Text-to-Speech (OOP)',
                    'POST /audio_query' => 'Generate audio query (OOP)',
                    'POST /synthesis' => 'Synthesize from audio query (OOP)'
                ]
            ]);
        } catch (Exception $e) {
            send_error_response('Status check failed: ' . $e->getMessage(), 500);
        }
        break;
        
    case '/speakers':
        // 話者一覧API
        if ($method !== 'GET') {
            send_error_response('GET method required');
        }
        
        $metas_file = __DIR__ . '/metas.json';
        if (!file_exists($metas_file)) {
            send_error_response('metas.json not found', 404);
        }
        
        try {
            $metas_content = file_get_contents($metas_file);
            $metas_data = json_decode($metas_content, true);
            
            if ($metas_data === null) {
                send_error_response('Invalid metas.json format', 500);
            }
            
            // 話者データを整理
            $speakers = [];
            foreach ($metas_data as $speaker) {
                $speaker_name = $speaker['name'];
                foreach ($speaker['styles'] as $style) {
                    // frame_decodeとsingタイプは除外（通常の音声合成では使用しない）
                    if (!isset($style['type']) || $style['type'] === null) {
                        $speakers[] = [
                            'id' => $style['id'],
                            'name' => $speaker_name,
                            'style' => $style['name'],
                            'display_name' => $speaker_name . '（' . $style['name'] . '）',
                            'speaker_uuid' => $speaker['speaker_uuid'] ?? null
                        ];
                    }
                }
            }
            
            // IDでソート
            usort($speakers, function($a, $b) {
                return $a['id'] - $b['id'];
            });
            
            send_json_response([
                'status' => 'success',
                'speakers' => $speakers,
                'total_count' => count($speakers),
                'implementation' => 'OOP Interface'
            ]);
            
        } catch (Exception $e) {
            error_log('Speakers API Error: ' . $e->getMessage());
            send_error_response('Failed to load speakers data: ' . $e->getMessage(), 500);
        }
        break;
        
    case '/tts':
        // Text-to-Speech API (OOP版)
        if ($method !== 'POST') {
            send_error_response('POST method required');
        }
        
        $data = get_post_data();
        
        if (!isset($data['text']) || empty($data['text'])) {
            send_error_response('Text parameter is required');
        }
        
        $text = $data['text'];
        $speaker_id = $data['speaker_id'] ?? 3;
        $kana = $data['kana'] ?? false;
        $format = $data['format'] ?? 'wav'; // wav or base64
        
        // バリデーション
        if (strlen($text) > 1000) {
            send_error_response('Text too long (max 1000 characters)');
        }
        
        if (!is_numeric($speaker_id) || $speaker_id < 0 || $speaker_id > 100) {
            send_error_response('Invalid speaker_id (0-100)');
        }
        
        try {
            $engine = get_voicevox_engine();
            
            $start_time = microtime(true);
            $wav_data = $engine->tts($text, (int)$speaker_id, (bool)$kana);
            $tts_time = microtime(true) - $start_time;
            
            if ($wav_data === false) {
                send_error_response('TTS generation failed');
            }
            
            if ($format === 'base64') {
                send_json_response([
                    'status' => 'success',
                    'audio_data' => base64_encode($wav_data),
                    'format' => 'wav',
                    'encoding' => 'base64',
                    'size' => strlen($wav_data),
                    'generation_time' => round($tts_time, 3),
                    'text' => $text,
                    'speaker_id' => $speaker_id,
                    'implementation' => 'OOP Interface'
                ]);
            } else {
                // WAVファイルとして返す
                $filename = 'voice_oop_' . md5($text . $speaker_id) . '.wav';
                send_wav_response($wav_data, $filename);
            }
            
        } catch (VoicevoxException $e) {
            error_log('VOICEVOX TTS Error: ' . $e->getMessage() . ' (Code: ' . $e->getVoicevoxCode() . ')');
            send_error_response('VOICEVOX TTS error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            error_log('TTS Error: ' . $e->getMessage());
            send_error_response('TTS processing error: ' . $e->getMessage(), 500);
        }
        break;
        
    case '/audio_query':
        // AudioQuery生成API (OOP版)
        if ($method !== 'POST') {
            send_error_response('POST method required');
        }
        
        $data = get_post_data();
        
        if (!isset($data['text']) || empty($data['text'])) {
            send_error_response('Text parameter is required');
        }
        
        $text = $data['text'];
        $speaker_id = $data['speaker_id'] ?? 3;
        $kana = $data['kana'] ?? false;
        
        try {
            $engine = get_voicevox_engine();
            
            $start_time = microtime(true);
            $audio_query = $engine->audioQuery($text, (int)$speaker_id, (bool)$kana);
            $query_time = microtime(true) - $start_time;
            
            if ($audio_query === false) {
                send_error_response('AudioQuery generation failed');
            }
            
            $query_data = json_decode($audio_query, true);
            
            if ($query_data === null) {
                error_log("AudioQuery JSON decode failed: " . json_last_error_msg());
                error_log("Raw AudioQuery: " . substr($audio_query, 0, 500));
                send_error_response('AudioQuery JSON decode failed');
            }
            
            // AudioQueryのデフォルト値を設定（スネークケース形式のみ）
            if (!isset($query_data['speed_scale']) || $query_data['speed_scale'] === null) {
                $query_data['speed_scale'] = 1.0;
            }
            if (!isset($query_data['pitch_scale']) || $query_data['pitch_scale'] === null) {
                $query_data['pitch_scale'] = 0.0;
            }
            if (!isset($query_data['intonation_scale']) || $query_data['intonation_scale'] === null) {
                $query_data['intonation_scale'] = 1.0;
            }
            if (!isset($query_data['volume_scale']) || $query_data['volume_scale'] === null) {
                $query_data['volume_scale'] = 1.0;
            }
            if (!isset($query_data['pre_phoneme_length']) || $query_data['pre_phoneme_length'] === null) {
                $query_data['pre_phoneme_length'] = 0.1;
            }
            if (!isset($query_data['post_phoneme_length']) || $query_data['post_phoneme_length'] === null) {
                $query_data['post_phoneme_length'] = 0.1;
            }
            if (!isset($query_data['output_sampling_rate']) || $query_data['output_sampling_rate'] === null) {
                $query_data['output_sampling_rate'] = 24000;
            }
            if (!isset($query_data['output_stereo']) || $query_data['output_stereo'] === null) {
                $query_data['output_stereo'] = false;
            }
            
            error_log("AudioQuery processed successfully for speaker $speaker_id (OOP)");
            
            send_json_response([
                'status' => 'success',
                'audio_query' => $query_data,
                'generation_time' => round($query_time, 3),
                'text' => $text,
                'speaker_id' => $speaker_id,
                'implementation' => 'OOP Interface'
            ]);
            
        } catch (VoicevoxException $e) {
            error_log('VOICEVOX AudioQuery Error: ' . $e->getMessage() . ' (Code: ' . $e->getVoicevoxCode() . ')');
            send_error_response('VOICEVOX AudioQuery error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            error_log('AudioQuery Error: ' . $e->getMessage());
            send_error_response('AudioQuery processing error: ' . $e->getMessage(), 500);
        }
        break;
        
    case '/synthesis':
        // 音声合成API（AudioQueryから）(OOP版)
        if ($method !== 'POST') {
            send_error_response('POST method required');
        }
        
        $data = get_post_data();
        
        if (!isset($data['audio_query'])) {
            send_error_response('audio_query parameter is required');
        }
        
        // AudioQueryの処理を修正
        if (is_array($data['audio_query'])) {
            $audio_query = json_encode($data['audio_query']);
        } else if (is_string($data['audio_query'])) {
            // 既にJSON文字列の場合、デコード→エンコードで正規化
            $decoded = json_decode($data['audio_query'], true);
            if ($decoded === null) {
                send_error_response('Invalid audio_query JSON format');
            }
            $audio_query = json_encode($decoded);
        } else {
            send_error_response('audio_query must be object or JSON string');
        }
        
        $speaker_id = $data['speaker_id'] ?? 3;
        $enable_upspeak = $data['enable_interrogative_upspeak'] ?? true;
        $format = $data['format'] ?? 'wav';
        
        // デバッグログ
        error_log("Synthesis (OOP) - Speaker ID: $speaker_id, AudioQuery length: " . strlen($audio_query));
        
        try {
            $engine = get_voicevox_engine();
            
            $start_time = microtime(true);
            $wav_data = $engine->synthesis($audio_query, (int)$speaker_id, (bool)$enable_upspeak);
            $synthesis_time = microtime(true) - $start_time;
            
            if ($wav_data === false) {
                send_error_response('Synthesis failed');
            }
            
            if ($format === 'base64') {
                send_json_response([
                    'status' => 'success',
                    'audio_data' => base64_encode($wav_data),
                    'format' => 'wav',
                    'encoding' => 'base64',
                    'size' => strlen($wav_data),
                    'synthesis_time' => round($synthesis_time, 3),
                    'speaker_id' => $speaker_id,
                    'implementation' => 'OOP Interface'
                ]);
            } else {
                $filename = 'synthesis_oop_' . time() . '.wav';
                send_wav_response($wav_data, $filename);
            }
            
        } catch (VoicevoxException $e) {
            error_log('VOICEVOX Synthesis Error: ' . $e->getMessage() . ' (Code: ' . $e->getVoicevoxCode() . ')');
            send_error_response('VOICEVOX Synthesis error: ' . $e->getMessage(), 500);
        } catch (Exception $e) {
            error_log('Synthesis Error: ' . $e->getMessage());
            send_error_response('Synthesis processing error: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        // 404 Not Found
        send_error_response('Endpoint not found', 404);
}

// ここには到達しない
?>