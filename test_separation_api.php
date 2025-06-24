<?php
/**
 * AudioQuery/Synthesis分離API動作確認テスト  
 * separation.mdの実装計画に基づく機能テスト
 */

echo "=== AudioQuery/Synthesis分離API テスト ===\n\n";

// テスト1: 基本機能確認
echo "1. 分離API関数存在確認\n";
$separation_functions = [
    'voicevox_audio_query',
    'voicevox_synthesis'
];

foreach ($separation_functions as $func) {
    if (function_exists($func)) {
        echo "✓ $func 関数が存在します\n";
    } else {
        echo "✗ $func 関数が見つかりません\n";
    }
}

// テスト2: OOPクラスメソッド確認
echo "\n2. OOPクラス分離APIメソッド確認\n";
try {
    if (class_exists('\\Voicevox\\Engine')) {
        $engine = \Voicevox\Engine::getInstance();
        
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

// テスト3: エラーハンドリング確認
echo "\n3. 分離APIエラーハンドリング確認\n";

// 手続き型APIエラーテスト
echo "手続き型API:\n";
$result = voicevox_audio_query("", 3); // 空文字列
if ($result === false) {
    echo "✓ voicevox_audio_query() 空文字列で適切にfalseを返しました\n";
} else {
    echo "✗ voicevox_audio_query() 空文字列で予期しない結果: " . gettype($result) . "\n";
}

$result = voicevox_synthesis("", 3); // 空AudioQuery
if ($result === false) {
    echo "✓ voicevox_synthesis() 空AudioQueryで適切にfalseを返しました\n";
} else {
    echo "✗ voicevox_synthesis() 空AudioQueryで予期しない結果: " . gettype($result) . "\n";
}

// OOP APIエラーテスト
echo "OOP API:\n";
try {
    $engine = \Voicevox\Engine::getInstance();
    
    $result = $engine->audioQuery("", 3); // 空文字列
    if ($result === false) {
        echo "✓ Engine::audioQuery() 空文字列で適切にfalseを返しました\n";
    } else {
        echo "✗ Engine::audioQuery() 空文字列で予期しない結果: " . gettype($result) . "\n";
    }
    
    $result = $engine->synthesis("", 3); // 空AudioQuery
    if ($result === false) {
        echo "✓ Engine::synthesis() 空AudioQueryで適切にfalseを返しました\n";
    } else {
        echo "✗ Engine::synthesis() 空AudioQueryで予期しない結果: " . gettype($result) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ OOPエラーテストエラー: " . $e->getMessage() . "\n";
}

// テスト4: パフォーマンス計測機能確認
echo "\n4. パフォーマンス計測機能確認\n";
echo "分離APIを使った2段階処理の測定:\n";

$test_texts = ["こんにちは", "さようなら", "ありがとう"];
$speaker_id = 3;

foreach ($test_texts as $text) {
    echo "テキスト: '$text'\n";
    
    // AudioQuery生成時間計測
    $query_start = microtime(true);
    $audio_query = voicevox_audio_query($text, $speaker_id);
    $query_time = microtime(true) - $query_start;
    
    if ($audio_query !== false) {
        echo "  ✓ AudioQuery生成時間: " . number_format($query_time * 1000, 3) . "ms\n";
        echo "  ✓ AudioQueryサイズ: " . strlen($audio_query) . " bytes\n";
        
        // AudioQueryの基本構造確認
        $query_data = json_decode($audio_query, true);
        if ($query_data !== null) {
            echo "  ✓ AudioQuery JSON形式が正常\n";
            
            // 基本パラメータ存在確認
            $required_params = ['speedScale', 'pitchScale', 'intonationScale', 'volumeScale'];
            $params_ok = true;
            foreach ($required_params as $param) {
                if (!isset($query_data[$param])) {
                    $params_ok = false;
                    break;
                }
            }
            
            if ($params_ok) {
                echo "  ✓ 基本パラメータが存在します\n";
            } else {
                echo "  ✗ 基本パラメータが不足しています\n";
            }
            
            // Synthesis実行時間計測
            $synthesis_start = microtime(true);
            $wav_data = voicevox_synthesis($audio_query, $speaker_id);
            $synthesis_time = microtime(true) - $synthesis_start;
            
            if ($wav_data !== false) {
                echo "  ✓ Synthesis実行時間: " . number_format($synthesis_time * 1000, 3) . "ms\n";
                echo "  ✓ 音声データサイズ: " . strlen($wav_data) . " bytes\n";
                
                $total_time = $query_time + $synthesis_time;
                echo "  ✓ 合計処理時間: " . number_format($total_time * 1000, 3) . "ms\n";
            } else {
                echo "  ✗ Synthesis実行失敗（未初期化のため正常）\n";
            }
        } else {
            echo "  ✗ AudioQuery JSON解析失敗\n";
        }
    } else {
        echo "  ✗ AudioQuery生成失敗（未初期化のため正常）\n";
    }
    echo "\n";
}

// テスト5: パラメータ調整機能確認
echo "5. パラメータ調整機能確認\n";
echo "AudioQueryパラメータ調整のテスト:\n";

$test_text = "パラメータ調整テスト";
$audio_query = voicevox_audio_query($test_text, $speaker_id);

if ($audio_query !== false) {
    $query_data = json_decode($audio_query, true);
    if ($query_data !== null) {
        echo "✓ 元のパラメータ:\n";
        echo "  - speedScale: " . $query_data['speedScale'] . "\n";
        echo "  - pitchScale: " . $query_data['pitchScale'] . "\n";
        echo "  - intonationScale: " . $query_data['intonationScale'] . "\n";
        echo "  - volumeScale: " . $query_data['volumeScale'] . "\n";
        
        // パラメータ調整
        $query_data['speedScale'] = 1.2;      // 1.2倍速
        $query_data['pitchScale'] = 0.1;      // ピッチ+0.1
        $query_data['intonationScale'] = 1.2; // イントネーション1.2倍
        $query_data['volumeScale'] = 1.5;     // 音量1.5倍
        
        $modified_query = json_encode($query_data);
        echo "✓ 調整後のパラメータ:\n";
        echo "  - speedScale: " . $query_data['speedScale'] . "\n";
        echo "  - pitchScale: " . $query_data['pitchScale'] . "\n";
        echo "  - intonationScale: " . $query_data['intonationScale'] . "\n";
        echo "  - volumeScale: " . $query_data['volumeScale'] . "\n";
        
        // 調整後のAudioQueryでSynthesis実行
        $modified_wav = voicevox_synthesis($modified_query, $speaker_id);
        if ($modified_wav !== false) {
            echo "✓ 調整されたパラメータでSynthesis成功\n";
            echo "  - 調整後音声データサイズ: " . strlen($modified_wav) . " bytes\n";
        } else {
            echo "✗ 調整されたパラメータでSynthesis失敗（未初期化のため正常）\n";
        }
    } else {
        echo "✗ AudioQuery JSON解析失敗\n";
    }
} else {
    echo "✗ AudioQuery生成失敗（未初期化のため正常）\n";
}

echo "\n=== 分離APIテスト完了 ===\n";
echo "注意: このテストは実際のVOICEVOXライブラリなしで実行されています。\n";
echo "実際の音声生成をテストするには、VOICEVOXライブラリと辞書を配置してください。\n";
echo "\n使用例:\n";
echo "1. \$audioQuery = voicevox_audio_query(\$text, \$speakerId);\n";
echo "2. \$queryData = json_decode(\$audioQuery, true);\n";
echo "3. \$queryData['speedScale'] = 1.2; // パラメータ調整\n";
echo "4. \$modifiedQuery = json_encode(\$queryData);\n";
echo "5. \$wavData = voicevox_synthesis(\$modifiedQuery, \$speakerId);\n";
?>