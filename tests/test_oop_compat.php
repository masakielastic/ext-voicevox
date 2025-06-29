<?php
/**
 * VOICEVOX OOP互換性テスト
 * 既存関数とOOPクラスの動作が同じことを確認
 */

// テスト用定数
define('TEST_LIB_PATH', '/path/to/voicevox_core.so');
define('TEST_DICT_PATH', '/path/to/open_jtalk_dic_utf_8-1.11');
define('TEST_SPEAKER_ID', 3);
define('TEST_TEXT', 'こんにちは、テストです。');

echo "=== VOICEVOX OOP互換性テスト ===\n\n";

// テスト1: クラスの存在確認
echo "1. クラス存在確認テスト\n";
try {
    if (class_exists('\\Voicevox\\Engine')) {
        echo "✓ Voicevox\\Engine クラスが存在します\n";
    } else {
        echo "✗ Voicevox\\Engine クラスが見つかりません\n";
        exit(1);
    }
    
    if (class_exists('\\Voicevox\\Exception\\VoicevoxException')) {
        echo "✓ Voicevox\\Exception\\VoicevoxException クラスが存在します\n";
    } else {
        echo "✗ VoicevoxException クラスが見つかりません\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ クラス確認エラー: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// テスト2: シングルトンパターンテスト
echo "2. シングルトンパターンテスト\n";
try {
    $engine1 = \Voicevox\Engine::getInstance();
    $engine2 = \Voicevox\Engine::getInstance();
    
    if ($engine1 === $engine2) {
        echo "✓ シングルトンパターンが正しく動作しています\n";
    } else {
        echo "✗ シングルトンパターンが失敗しました\n";
    }
} catch (Exception $e) {
    echo "✗ シングルトンテストエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// テスト3: 基本メソッドテスト
echo "3. 基本メソッドテスト\n";
try {
    $engine = \Voicevox\Engine::getInstance();
    
    // 初期化前の状態確認
    if (!$engine->isInitialized()) {
        echo "✓ 初期化前は未初期化状態です\n";
    } else {
        echo "✗ 初期化前なのに初期化済みと判定されました\n";
    }
    
    // バージョン取得テスト
    $version = $engine->getVersion();
    if (is_string($version)) {
        echo "✓ バージョン取得成功: $version\n";
    } else {
        echo "✗ バージョン取得に失敗しました\n";
    }
    
} catch (Exception $e) {
    echo "✗ 基本メソッドテストエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// テスト4: 既存関数との互換性テスト
echo "4. 既存関数との互換性テスト\n";
try {
    // 既存関数の存在確認
    $functions = [
        'voicevox_initialize',
        'voicevox_is_initialized', 
        'voicevox_get_version',
        'voicevox_tts',
        'voicevox_audio_query',
        'voicevox_synthesis',
        'voicevox_finalize'
    ];
    
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✓ $func 関数が存在します\n";
        } else {
            echo "✗ $func 関数が見つかりません\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ 関数存在確認エラー: " . $e->getMessage() . "\n";
}

echo "\n";

// テスト5: エラーハンドリングテスト
echo "5. エラーハンドリングテスト\n";
try {
    $engine = \Voicevox\Engine::getInstance();
    
    // 初期化なしでTTS実行（エラーになるはず）
    try {
        $wav = $engine->tts(TEST_TEXT, TEST_SPEAKER_ID);
        echo "✗ 未初期化でTTSが成功してしまいました\n";
    } catch (\Voicevox\Exception\VoicevoxException $e) {
        echo "✓ 未初期化でのTTS実行で適切に例外が発生しました\n";
    }
    
} catch (Exception $e) {
    echo "✗ エラーハンドリングテストエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// テスト6: 初期化テスト（実際のライブラリがない場合はスキップ）
echo "6. 初期化テスト\n";
if (file_exists(TEST_LIB_PATH)) {
    try {
        $engine = \Voicevox\Engine::getInstance();
        
        // OOP方式での初期化
        $oop_result = $engine->initialize(TEST_LIB_PATH, TEST_DICT_PATH);
        
        // 既存関数での結果と比較
        $func_result = voicevox_initialize(TEST_LIB_PATH, TEST_DICT_PATH);
        
        if ($oop_result === $func_result) {
            echo "✓ OOP方式と関数方式の初期化結果が一致しました\n";
        } else {
            echo "✗ OOP方式と関数方式の初期化結果が異なります\n";
        }
        
        // 初期化状態確認
        $oop_status = $engine->isInitialized();
        $func_status = voicevox_is_initialized();
        
        if ($oop_status === $func_status) {
            echo "✓ OOP方式と関数方式の初期化状態確認結果が一致しました\n";
        } else {
            echo "✗ OOP方式と関数方式の初期化状態確認結果が異なります\n";
        }
        
    } catch (Exception $e) {
        echo "✗ 初期化テストエラー: " . $e->getMessage() . "\n";
    }
} else {
    echo "- ライブラリファイルが見つからないため初期化テストをスキップします\n";
}

echo "\n=== テスト完了 ===\n";
?>