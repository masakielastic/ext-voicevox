<?php
/**
 * 環境変数設定付きVOICEVOXテスト
 */

// 実際のVOICEVOXライブラリパスを設定
define('VOICEVOX_LIB_PATH', '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so');
define('VOICEVOX_DICT_PATH', '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11');

echo "=== VOICEVOX 環境変数テスト ===\n\n";

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
echo "2. 初期化テスト\n";
try {
    // 手続き型での初期化
    echo "手続き型API:\n";
    $result = voicevox_initialize(VOICEVOX_LIB_PATH, VOICEVOX_DICT_PATH);
    if ($result) {
        echo "✓ 手続き型初期化成功\n";
        
        $version = voicevox_get_version();
        echo "✓ バージョン: $version\n";
        
        $is_init = voicevox_is_initialized();
        echo "✓ 初期化状態: " . ($is_init ? 'true' : 'false') . "\n";
        
        // 重複初期化テスト
        $duplicate_result = voicevox_initialize(VOICEVOX_LIB_PATH, VOICEVOX_DICT_PATH);
        if (!$duplicate_result) {
            echo "✓ 重複初期化が適切に防止されました\n";
        } else {
            echo "✗ 重複初期化が防止されませんでした\n";
        }
        
    } else {
        echo "✗ 手続き型初期化失敗\n";
    }
    
    echo "\nOOP API:\n";
    // OOP での確認
    $engine = \Voicevox\Engine::getInstance();
    $oop_version = $engine->getVersion();
    $oop_init = $engine->isInitialized();
    
    echo "✓ OOPバージョン: $oop_version\n";
    echo "✓ OOP初期化状態: " . ($oop_init ? 'true' : 'false') . "\n";
    
    // 互換性確認
    if ($version === $oop_version && $is_init === $oop_init) {
        echo "✓ 手続き型とOOPの結果が一致しています\n";
    } else {
        echo "✗ 手続き型とOOPの結果が異なります\n";
    }
    
} catch (Exception $e) {
    echo "✗ 初期化エラー: " . $e->getMessage() . "\n";
}

echo "\n";

// TTS基本テスト
echo "3. TTS基本テスト\n";
try {
    // 短いテキストでのTTS
    $test_text = "テスト";
    $speaker_id = 3;
    
    echo "手続き型TTS:\n";
    $wav_data = voicevox_tts($test_text, $speaker_id);
    if ($wav_data !== false) {
        echo "✓ TTS成功 (サイズ: " . strlen($wav_data) . " bytes)\n";
        
        // WAVヘッダー確認
        if (substr($wav_data, 0, 4) === 'RIFF' && substr($wav_data, 8, 4) === 'WAVE') {
            echo "✓ 有効なWAVファイル形式\n";
        } else {
            echo "✗ 無効なWAVファイル形式\n";
        }
    } else {
        echo "✗ TTS失敗\n";
    }
    
    echo "\nOOP TTS:\n";
    $oop_wav_data = $engine->tts($test_text, $speaker_id);
    if ($oop_wav_data !== false) {
        echo "✓ OOP TTS成功 (サイズ: " . strlen($oop_wav_data) . " bytes)\n";
        
        // 結果の一致確認
        if ($wav_data === $oop_wav_data) {
            echo "✓ 手続き型とOOPのTTS結果が一致しています\n";
        } else {
            echo "✗ 手続き型とOOPのTTS結果が異なります\n";
        }
    } else {
        echo "✗ OOP TTS失敗\n";
    }
    
} catch (Exception $e) {
    echo "✗ TTSエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// エラーケーステスト
echo "4. エラーケーステスト\n";
try {
    // 空文字列テスト
    $empty_result = voicevox_tts("", 3);
    if ($empty_result === false) {
        echo "✓ 空文字列TTS防止成功\n";
    } else {
        echo "✗ 空文字列TTSが成功してしまいました\n";
    }
    
    $oop_empty_result = $engine->tts("", 3);
    if ($oop_empty_result === false) {
        echo "✓ OOP空文字列TTS防止成功\n";
    } else {
        echo "✗ OOP空文字列TTSが成功してしまいました\n";
    }
    
    // 無効話者IDテスト (999は通常存在しない)
    $invalid_speaker_result = voicevox_tts("テスト", 999);
    echo "✓ 無効話者ID結果: " . ($invalid_speaker_result === false ? 'false' : 'データ返却') . "\n";
    
} catch (Exception $e) {
    echo "✗ エラーケーステストエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// 終了処理
echo "5. 終了処理\n";
try {
    $finalize_result = voicevox_finalize();
    if ($finalize_result) {
        echo "✓ 終了処理成功\n";
        
        // 終了後の状態確認
        $after_finalize = voicevox_is_initialized();
        if (!$after_finalize) {
            echo "✓ 終了後は未初期化状態\n";
        } else {
            echo "✗ 終了後も初期化状態のままです\n";
        }
    } else {
        echo "✗ 終了処理失敗\n";
    }
} catch (Exception $e) {
    echo "✗ 終了処理エラー: " . $e->getMessage() . "\n";
}

echo "\n=== テスト完了 ===\n";
?>