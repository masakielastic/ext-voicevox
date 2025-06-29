<?php
/**
 * 強化されたVOICEVOX OOP実装テスト
 * テスト要件対応確認
 */

echo "=== VOICEVOX OOP強化版テスト ===\n\n";

// テスト1: 基本機能確認（tests/001_basic.phpt相当）
echo "1. 基本機能確認\n";
if (extension_loaded('voicevox')) {
    echo "✓ 拡張機能がロードされています\n";
} else {
    echo "✗ 拡張機能がロードされていません\n";
    exit(1);
}

// 関数存在確認
$required_functions = [
    'voicevox_initialize',
    'voicevox_finalize',
    'voicevox_get_version',
    'voicevox_tts',
    'voicevox_audio_query',
    'voicevox_synthesis',
    'voicevox_is_initialized'
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "✓ $func 関数が存在します\n";
    } else {
        echo "✗ $func 関数が見つかりません\n";
    }
}

// 定数確認
$required_constants = [
    'VOICEVOX_RESULT_OK',
    'VOICEVOX_ACCELERATION_MODE_AUTO',
    'VOICEVOX_ACCELERATION_MODE_CPU',
    'VOICEVOX_ACCELERATION_MODE_GPU'
];

foreach ($required_constants as $const) {
    if (defined($const)) {
        echo "✓ $const 定数が定義されています\n";
    } else {
        echo "✗ $const 定数が見つかりません\n";
    }
}

echo "\n";

// テスト2: OOPクラス確認
echo "2. OOPクラス確認\n";
try {
    if (class_exists('\\Voicevox\\Engine')) {
        echo "✓ Voicevox\\Engine クラスが存在します\n";
        
        $engine = \Voicevox\Engine::getInstance();
        echo "✓ Engine インスタンス取得成功\n";
        
        // 初期化前状態確認（tests/002_initialization.phpt相当）
        if (!$engine->isInitialized()) {
            echo "✓ 初期化前は未初期化状態\n";
        } else {
            echo "✗ 初期化前なのに初期化済み状態\n";
        }
        
        // バージョン取得（ライブラリ未ロードでも文字列を返すはず）
        $version = $engine->getVersion();
        if (is_string($version)) {
            echo "✓ バージョン取得成功: '$version'\n";
        } else {
            echo "✗ バージョン取得失敗\n";
        }
        
    } else {
        echo "✗ Voicevox\\Engine クラスが見つかりません\n";
    }
    
    if (class_exists('\\Voicevox\\Exception\\VoicevoxException')) {
        echo "✓ VoicevoxException クラスが存在します\n";
    } else {
        echo "✗ VoicevoxException クラスが見つかりません\n";
    }
} catch (Exception $e) {
    echo "✗ OOPクラステストエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// テスト3: エラーハンドリング確認（tests/003_tts.phpt相当）
echo "3. エラーハンドリング確認\n";
try {
    $engine = \Voicevox\Engine::getInstance();
    
    // 未初期化でのTTS（falseを返すはず）
    $result = $engine->tts("テスト", 3);
    if ($result === false) {
        echo "✓ 未初期化でのTTSが適切にfalseを返しました\n";
    } else {
        echo "✗ 未初期化でのTTSが予期しない結果を返しました\n";
    }
    
    // 空文字列でのTTS（falseを返すはず）
    $result = $engine->tts("", 3);
    if ($result === false) {
        echo "✓ 空文字列TTSが適切にfalseを返しました\n";
    } else {
        echo "✗ 空文字列TTSが予期しない結果を返しました\n";
    }
    
    // 未初期化でのAudioQuery（falseを返すはず）
    $result = $engine->audioQuery("テスト", 3);
    if ($result === false) {
        echo "✓ 未初期化でのAudioQueryが適切にfalseを返しました\n";
    } else {
        echo "✗ 未初期化でのAudioQueryが予期しない結果を返しました\n";
    }
    
    // 空文字列でのAudioQuery（falseを返すはず）
    $result = $engine->audioQuery("", 3);
    if ($result === false) {
        echo "✓ 空文字列AudioQueryが適切にfalseを返しました\n";
    } else {
        echo "✗ 空文字列AudioQueryが予期しない結果を返しました\n";
    }
    
} catch (Exception $e) {
    echo "✗ エラーハンドリングテストエラー: " . $e->getMessage() . "\n";
}

echo "\n";

// テスト4: 手続き型-OOP互換性確認
echo "4. 手続き型-OOP互換性確認\n";
try {
    // 初期化状態確認の一致
    $oop_status = \Voicevox\Engine::getInstance()->isInitialized();
    $func_status = voicevox_is_initialized();
    
    if ($oop_status === $func_status) {
        echo "✓ 初期化状態確認が一致しています\n";
    } else {
        echo "✗ 初期化状態確認が一致していません (OOP: " . ($oop_status ? 'true' : 'false') . ", 関数: " . ($func_status ? 'true' : 'false') . ")\n";
    }
    
    // バージョン取得の一致
    $oop_version = \Voicevox\Engine::getInstance()->getVersion();
    $func_version = voicevox_get_version();
    
    if ($oop_version === $func_version) {
        echo "✓ バージョン取得が一致しています\n";
    } else {
        echo "✗ バージョン取得が一致していません (OOP: '$oop_version', 関数: '$func_version')\n";
    }
    
} catch (Exception $e) {
    echo "✗ 互換性テストエラー: " . $e->getMessage() . "\n";
}

echo "\n=== テスト完了 ===\n";
echo "このテストは実際のVOICEVOXライブラリなしで実行されています。\n";
echo "実際のTTS機能をテストするには、VOICEVOXライブラリを配置してください。\n";
?>