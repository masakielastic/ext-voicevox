<?php
/**
 * 環境変数設定付きVOICEVOXテスト (OOP版)
 * Migration: Converted from procedural to OOP API
 */

use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

// 実際のVOICEVOXライブラリパスを設定
define('VOICEVOX_LIB_PATH', '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so');
define('VOICEVOX_DICT_PATH', '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11');

echo "=== VOICEVOX 環境変数テスト (OOP版) ===\n\n";

// ライブラリファイルの存在確認
echo "1. ライブラリファイル確認\n";
if (file_exists(VOICEVOX_LIB_PATH)) {
    echo "✓ libvoicevox_core.so が見つかりました: " . VOICEVOX_LIB_PATH . "\n";
} else {
    echo "✗ libvoicevox_core.so が見つかりません: " . VOICEVOX_LIB_PATH . "\n";
    exit(1);
}

if (file_exists(VOICEVOX_DICT_PATH)) {
    echo "✓ 辞書ディレクトリが見つかりました: " . VOICEVOX_DICT_PATH . "\n";
} else {
    echo "✗ 辞書ディレクトリが見つかりません: " . VOICEVOX_DICT_PATH . "\n";
    exit(1);
}

echo "\n";

// 初期化テスト
echo "2. OOP API 初期化テスト\n";
try {
    // OOP APIでの初期化
    $engine = Engine::getInstance();
    echo "✓ Engine インスタンス取得成功\n";
    
    // 初期化前の状態確認
    $is_init_before = $engine->isInitialized();
    echo "✓ 初期化前の状態: " . ($is_init_before ? 'true' : 'false') . "\n";
    
    // 初期化実行
    $result = $engine->initialize(VOICEVOX_LIB_PATH, VOICEVOX_DICT_PATH);
    if ($result) {
        echo "✓ OOP初期化成功\n";
        
        $version = $engine->getVersion();
        echo "✓ バージョン: $version\n";
        
        $is_init_after = $engine->isInitialized();
        echo "✓ 初期化後の状態: " . ($is_init_after ? 'true' : 'false') . "\n";
        
        // 重複初期化テスト（例外が投げられるべき）
        try {
            $duplicate_result = $engine->initialize(VOICEVOX_LIB_PATH, VOICEVOX_DICT_PATH);
            echo "✗ 重複初期化が防止されませんでした\n";
        } catch (VoicevoxException $e) {
            echo "✓ 重複初期化が適切に例外で防止されました: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "✗ OOP初期化失敗\n";
    }
    
} catch (VoicevoxException $e) {
    echo "✗ VOICEVOX例外: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (Exception $e) {
    echo "✗ 一般例外: " . $e->getMessage() . "\n";
}

echo "\n";

// TTS基本テスト
echo "3. OOP API TTS基本テスト\n";
try {
    // 短いテキストでのTTS
    $test_text = "テスト";
    $speaker_id = 3;
    
    $start_time = microtime(true);
    $wav_data = $engine->tts($test_text, $speaker_id);
    $tts_time = microtime(true) - $start_time;
    
    if ($wav_data !== false) {
        echo "✓ TTS成功 (サイズ: " . strlen($wav_data) . " bytes, 時間: " . round($tts_time, 3) . "s)\n";
        
        // WAVヘッダー確認
        if (substr($wav_data, 0, 4) === 'RIFF' && substr($wav_data, 8, 4) === 'WAVE') {
            echo "✓ 有効なWAVファイル形式\n";
        } else {
            echo "✗ 無効なWAVファイル形式\n";
        }
    } else {
        echo "✗ TTS失敗\n";
    }
    
    // 長めのテキストテスト
    $long_text = "こんにちは、VOICEVOXのテストです。これはOOPインターフェイスを使用しています。";
    $long_wav_data = $engine->tts($long_text, $speaker_id);
    if ($long_wav_data !== false) {
        echo "✓ 長いテキストTTS成功 (サイズ: " . strlen($long_wav_data) . " bytes)\n";
    } else {
        echo "✗ 長いテキストTTS失敗\n";
    }
    
} catch (VoicevoxException $e) {
    echo "✗ VOICEVOX TTS例外: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (Exception $e) {
    echo "✗ TTS一般例外: " . $e->getMessage() . "\n";
}

echo "\n";

// AudioQuery + Synthesis テスト
echo "4. AudioQuery + Synthesis テスト\n";
try {
    $test_text = "音声合成のテストです";
    $speaker_id = 0;
    
    // AudioQuery生成
    $start_time = microtime(true);
    $audio_query = $engine->audioQuery($test_text, $speaker_id);
    $query_time = microtime(true) - $start_time;
    
    if ($audio_query !== false) {
        echo "✓ AudioQuery生成成功 (時間: " . round($query_time, 3) . "s)\n";
        
        // JSONパース確認
        $query_data = json_decode($audio_query, true);
        if ($query_data !== null) {
            echo "✓ AudioQuery JSONパース成功\n";
            
            // パラメータ調整テスト
            $query_data['speed_scale'] = 1.2;
            $query_data['pitch_scale'] = 0.1;
            $modified_query = json_encode($query_data);
            
            // 合成実行
            $start_time = microtime(true);
            $synthesis_wav = $engine->synthesis($modified_query, $speaker_id);
            $synthesis_time = microtime(true) - $start_time;
            
            if ($synthesis_wav !== false) {
                echo "✓ Synthesis成功 (サイズ: " . strlen($synthesis_wav) . " bytes, 時間: " . round($synthesis_time, 3) . "s)\n";
            } else {
                echo "✗ Synthesis失敗\n";
            }
        } else {
            echo "✗ AudioQuery JSONパース失敗\n";
        }
    } else {
        echo "✗ AudioQuery生成失敗\n";
    }
    
} catch (VoicevoxException $e) {
    echo "✗ VOICEVOX AudioQuery/Synthesis例外: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (Exception $e) {
    echo "✗ AudioQuery/Synthesis一般例外: " . $e->getMessage() . "\n";
}

echo "\n";

// エラーケーステスト（例外処理の確認）
echo "5. エラーケーステスト（例外処理）\n";
try {
    // 空文字列テスト（例外が投げられるべき）
    try {
        $empty_result = $engine->tts("", 3);
        echo "✗ 空文字列TTSが成功してしまいました\n";
    } catch (VoicevoxException $e) {
        echo "✓ 空文字列TTS例外処理成功: " . $e->getMessage() . "\n";
    }
    
    // 空AudioQueryテスト（例外が投げられるべき）
    try {
        $empty_synthesis = $engine->synthesis("", 3);
        echo "✗ 空AudioQuery Synthesisが成功してしまいました\n";
    } catch (VoicevoxException $e) {
        echo "✓ 空AudioQuery Synthesis例外処理成功: " . $e->getMessage() . "\n";
    }
    
    // 無効話者IDテスト (999は通常存在しない)
    try {
        $invalid_speaker_result = $engine->tts("テスト", 999);
        echo "✓ 無効話者ID結果: " . ($invalid_speaker_result === false ? 'false' : 'データ返却') . "\n";
    } catch (VoicevoxException $e) {
        echo "✓ 無効話者ID例外処理: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ エラーケーステスト例外: " . $e->getMessage() . "\n";
}

echo "\n";

// 非推奨API警告テスト
echo "6. 非推奨API警告テスト\n";
echo "手続き型API使用時の非推奨警告確認:\n";

// エラーハンドラーを設定して非推奨警告をキャッチ
$deprecation_warnings = [];
set_error_handler(function($severity, $message, $file, $line) use (&$deprecation_warnings) {
    if ($severity === E_DEPRECATED) {
        $deprecation_warnings[] = $message;
    }
    return false; // 通常のエラーハンドリングも継続
}, E_DEPRECATED);

// 手続き型APIを使用して非推奨警告をトリガー
$proc_version = @voicevox_get_version();
$proc_init_status = @voicevox_is_initialized();

// エラーハンドラーを復元
restore_error_handler();

if (!empty($deprecation_warnings)) {
    echo "✓ 非推奨警告が正しく発出されました:\n";
    foreach ($deprecation_warnings as $warning) {
        echo "  - $warning\n";
    }
} else {
    echo "✗ 非推奨警告が発出されませんでした\n";
}

echo "\n";

// 終了処理
echo "7. 終了処理\n";
try {
    $finalize_result = $engine->finalize();
    if ($finalize_result) {
        echo "✓ 終了処理成功\n";
        
        // 終了後の状態確認
        $after_finalize = $engine->isInitialized();
        if (!$after_finalize) {
            echo "✓ 終了後は未初期化状態\n";
        } else {
            echo "✗ 終了後も初期化状態のままです\n";
        }
    } else {
        echo "✗ 終了処理失敗\n";
    }
} catch (VoicevoxException $e) {
    echo "✗ VOICEVOX終了処理例外: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (Exception $e) {
    echo "✗ 終了処理一般例外: " . $e->getMessage() . "\n";
}

echo "\n";

// 未初期化状態でのAPI呼び出しテスト
echo "8. 未初期化状態API呼び出しテスト\n";
try {
    $uninit_result = $engine->tts("テスト", 3);
    echo "✗ 未初期化状態でTTSが成功してしまいました\n";
} catch (VoicevoxException $e) {
    echo "✓ 未初期化状態TTS例外処理成功: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ 未初期化状態TTS一般例外: " . $e->getMessage() . "\n";
}

echo "\n=== OOP版テスト完了 ===\n";
?>