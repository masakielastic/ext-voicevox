<?php
/**
 * AudioQuery/Synthesis分離API動作確認テスト (OOP版)
 * Migration: Converted from procedural to OOP API
 * separation.mdの実装計画に基づく機能テスト
 */

use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

echo "=== AudioQuery/Synthesis分離API テスト (OOP版) ===\n\n";

// テスト1: OOPクラスメソッド確認
echo "1. OOP分離APIメソッド確認\n";
try {
    if (class_exists('\\Voicevox\\Engine')) {
        $engine = Engine::getInstance();
        echo "✓ Engine インスタンス取得成功\n";
        
        $separation_methods = ['audioQuery', 'synthesis'];
        foreach ($separation_methods as $method) {
            if (method_exists($engine, $method)) {
                echo "✓ Engine::$method() メソッドが存在します\n";
            } else {
                echo "✗ Engine::$method() メソッドが見つかりません\n";
            }
        }
    } else {
        echo "✗ Voicevox\\Engine クラスが見つかりません\n";
    }
} catch (Exception $e) {
    echo "✗ OOPテストエラー: " . $e->getMessage() . "\n";
}

// エンジン初期化
echo "\n2. エンジン初期化\n";
try {
    // 環境変数またはデフォルトパスを使用
    $lib_path = $_ENV['VOICEVOX_LIB_PATH'] ?? '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so';
    $dict_path = $_ENV['VOICEVOX_DICT_PATH'] ?? '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11';
    
    if (!$engine->isInitialized()) {
        $result = $engine->initialize($lib_path, $dict_path);
        if ($result) {
            echo "✓ エンジン初期化成功\n";
        } else {
            echo "✗ エンジン初期化失敗\n";
            exit(1);
        }
    } else {
        echo "✓ エンジンは既に初期化済み\n";
    }
} catch (VoicevoxException $e) {
    echo "✗ VOICEVOX初期化例外: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ 初期化例外: " . $e->getMessage() . "\n";
    exit(1);
}

// テスト3: エラーハンドリング確認（例外処理）
echo "\n3. 分離APIエラーハンドリング確認（例外処理）\n";

// 空文字列エラーテスト
echo "空文字列エラーテスト:\n";
try {
    $empty_audio_query = $engine->audioQuery("", 3);
    echo "✗ 空文字列AudioQueryが成功してしまいました\n";
} catch (VoicevoxException $e) {
    echo "✓ 空文字列AudioQuery例外処理成功: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ 空文字列AudioQuery一般例外: " . $e->getMessage() . "\n";
}

try {
    $empty_synthesis = $engine->synthesis("", 3);
    echo "✗ 空AudioQuery Synthesisが成功してしまいました\n";
} catch (VoicevoxException $e) {
    echo "✓ 空AudioQuery Synthesis例外処理成功: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ 空AudioQuery Synthesis一般例外: " . $e->getMessage() . "\n";
}

// テスト4: 基本的な分離API動作確認
echo "\n4. 基本的な分離API動作確認\n";

$test_text = "こんにちは、AudioQuery分離テストです";
$speaker_id = 3;

try {
    // Step 1: AudioQuery生成
    echo "Step 1: AudioQuery生成\n";
    $start_time = microtime(true);
    $audio_query = $engine->audioQuery($test_text, $speaker_id);
    $query_time = microtime(true) - $start_time;
    
    if ($audio_query !== false) {
        echo "✓ AudioQuery生成成功 (時間: " . round($query_time, 3) . "s)\n";
        echo "  AudioQueryサイズ: " . strlen($audio_query) . " bytes\n";
        
        // JSON妥当性確認
        $query_data = json_decode($audio_query, true);
        if ($query_data !== null) {
            echo "✓ AudioQuery JSON解析成功\n";
            echo "  パラメータ数: " . count($query_data) . "\n";
            
            // 主要パラメータの存在確認
            $required_params = ['accent_phrases', 'speed_scale', 'pitch_scale', 'intonation_scale', 'volume_scale'];
            $missing_params = [];
            foreach ($required_params as $param) {
                if (!isset($query_data[$param])) {
                    $missing_params[] = $param;
                }
            }
            
            if (empty($missing_params)) {
                echo "✓ 必要なパラメータがすべて存在します\n";
            } else {
                echo "✗ 不足パラメータ: " . implode(', ', $missing_params) . "\n";
            }
        } else {
            echo "✗ AudioQuery JSON解析失敗\n";
            echo "  エラー: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "✗ AudioQuery生成失敗\n";
    }
    
} catch (VoicevoxException $e) {
    echo "✗ VOICEVOX AudioQuery例外: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (Exception $e) {
    echo "✗ AudioQuery一般例外: " . $e->getMessage() . "\n";
}

// テスト5: AudioQueryパラメータ調整テスト
echo "\n5. AudioQueryパラメータ調整テスト\n";

if (isset($query_data) && $query_data !== null) {
    try {
        // パラメータ調整
        echo "パラメータ調整中...\n";
        $modified_query_data = $query_data;
        $modified_query_data['speed_scale'] = 1.2;      // 速度を20%上げる
        $modified_query_data['pitch_scale'] = 0.1;      // ピッチを少し上げる
        $modified_query_data['volume_scale'] = 0.8;     // 音量を少し下げる
        
        $modified_query = json_encode($modified_query_data);
        
        echo "✓ パラメータ調整完了\n";
        echo "  速度スケール: " . $modified_query_data['speed_scale'] . "\n";
        echo "  ピッチスケール: " . $modified_query_data['pitch_scale'] . "\n";
        echo "  音量スケール: " . $modified_query_data['volume_scale'] . "\n";
        
        // Step 2: Synthesis実行
        echo "\nStep 2: Synthesis実行\n";
        $start_time = microtime(true);
        $wav_data = $engine->synthesis($modified_query, $speaker_id);
        $synthesis_time = microtime(true) - $start_time;
        
        if ($wav_data !== false) {
            echo "✓ Synthesis成功 (時間: " . round($synthesis_time, 3) . "s)\n";
            echo "  WAVデータサイズ: " . strlen($wav_data) . " bytes\n";
            
            // WAVヘッダー確認
            if (substr($wav_data, 0, 4) === 'RIFF' && substr($wav_data, 8, 4) === 'WAVE') {
                echo "✓ 有効なWAVファイル形式\n";
                
                // WAVファイル情報取得
                $wav_info = unpack('V', substr($wav_data, 4, 4));
                $file_size = $wav_info[1] + 8;
                echo "  WAVファイルサイズ: " . $file_size . " bytes\n";
            } else {
                echo "✗ 無効なWAVファイル形式\n";
            }
        } else {
            echo "✗ Synthesis失敗\n";
        }
        
    } catch (VoicevoxException $e) {
        echo "✗ VOICEVOX Synthesis例外: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
    } catch (Exception $e) {
        echo "✗ Synthesis一般例外: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ AudioQueryデータが利用できません\n";
}

// テスト6: 複数話者での分離API確認
echo "\n6. 複数話者での分離API確認\n";

$multi_test_speakers = [0, 1, 2, 3];
$multi_test_text = "話者テスト";

foreach ($multi_test_speakers as $test_speaker) {
    try {
        echo "話者ID $test_speaker テスト:\n";
        
        $speaker_audio_query = $engine->audioQuery($multi_test_text, $test_speaker);
        if ($speaker_audio_query !== false) {
            $speaker_wav = $engine->synthesis($speaker_audio_query, $test_speaker);
            if ($speaker_wav !== false) {
                echo "  ✓ 話者ID $test_speaker: 成功 (" . strlen($speaker_wav) . " bytes)\n";
            } else {
                echo "  ✗ 話者ID $test_speaker: Synthesis失敗\n";
            }
        } else {
            echo "  ✗ 話者ID $test_speaker: AudioQuery失敗\n";
        }
        
    } catch (VoicevoxException $e) {
        echo "  ✗ 話者ID $test_speaker: VOICEVOX例外 - " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        echo "  ✗ 話者ID $test_speaker: 一般例外 - " . $e->getMessage() . "\n";
    }
}

// テスト7: 非推奨API警告確認
echo "\n7. 非推奨API警告確認\n";
echo "手続き型分離API使用時の非推奨警告確認:\n";

// エラーハンドラーを設定して非推奨警告をキャッチ
$deprecation_warnings = [];
set_error_handler(function($severity, $message, $file, $line) use (&$deprecation_warnings) {
    if ($severity === E_DEPRECATED) {
        $deprecation_warnings[] = $message;
    }
    return false; // 通常のエラーハンドリングも継続
}, E_DEPRECATED);

// 手続き型分離APIを使用して非推奨警告をトリガー
$proc_audio_query = @voicevox_audio_query("テスト", 3);
$proc_synthesis = @voicevox_synthesis('{"test": "data"}', 3);

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

// テスト終了
echo "\n8. 終了処理\n";
try {
    $finalize_result = $engine->finalize();
    if ($finalize_result) {
        echo "✓ エンジン終了処理成功\n";
    } else {
        echo "✗ エンジン終了処理失敗\n";
    }
} catch (VoicevoxException $e) {
    echo "✗ VOICEVOX終了処理例外: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (Exception $e) {
    echo "✗ 終了処理一般例外: " . $e->getMessage() . "\n";
}

echo "\n=== AudioQuery/Synthesis分離API テスト (OOP版) 完了 ===\n";
?>