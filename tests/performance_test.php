<?php
/**
 * VOICEVOX Extension Performance Test
 * ファイル名: tests/performance_test.php
 */

echo "=== VOICEVOX Performance Test ===\n";

$lib_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so';
$dict_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11';

// 初期化時間測定
echo "1. Initialization Performance\n";
$start_time = microtime(true);
$result = voicevox_initialize($lib_path, $dict_path);
$init_time = microtime(true) - $start_time;

if (!$result) {
    die("ERROR: Initialization failed\n");
}

printf("   Initialization time: %.2f seconds\n", $init_time);

// TTS性能テスト
echo "\n2. TTS Performance Test\n";

$test_texts = [
    "こんにちは",
    "今日はいい天気ですね。",
    "VOICEVOXのパフォーマンステストを実行しています。これは少し長めのテキストです。",
    "短い",
    "人工知能技術の発展により、音声合成の品質が大幅に向上しました。特に深層学習を用いた手法では、自然で聞き取りやすい音声を生成することが可能になっています。"
];

$speakers = [0, 1, 3, 8];
$total_tests = count($test_texts) * count($speakers);
$completed_tests = 0;
$total_time = 0;
$total_bytes = 0;

foreach ($speakers as $speaker_id) {
    printf("   Testing Speaker ID: %d\n", $speaker_id);
    
    foreach ($test_texts as $i => $text) {
        $start = microtime(true);
        $wav_data = voicevox_tts($text, $speaker_id);
        $duration = microtime(true) - $start;
        
        if ($wav_data !== false) {
            $size = strlen($wav_data);
            $total_bytes += $size;
            $total_time += $duration;
            $completed_tests++;
            
            printf("     Text %d: %.3fs, %d bytes\n", $i + 1, $duration, $size);
        } else {
            printf("     Text %d: FAILED\n", $i + 1);
        }
    }
}

printf("\n   Performance Summary:\n");
printf("     Total tests: %d/%d\n", $completed_tests, $total_tests);
printf("     Average time per TTS: %.3f seconds\n", $total_time / $completed_tests);
printf("     Total audio generated: %.2f MB\n", $total_bytes / (1024 * 1024));
printf("     Throughput: %.2f KB/s\n", ($total_bytes / 1024) / $total_time);

// AudioQuery性能テスト
echo "\n3. AudioQuery Performance Test\n";

$query_start = microtime(true);
$audio_query = voicevox_audio_query("パフォーマンステスト用のテキストです。", 3);
$query_time = microtime(true) - $query_start;

if ($audio_query !== false) {
    printf("   AudioQuery generation: %.3f seconds\n", $query_time);
    printf("   AudioQuery size: %d bytes\n", strlen($audio_query));
    
    // Synthesis性能テスト
    $synthesis_start = microtime(true);
    $wav_data = voicevox_synthesis($audio_query, 3);
    $synthesis_time = microtime(true) - $synthesis_start;
    
    if ($wav_data !== false) {
        printf("   Synthesis time: %.3f seconds\n", $synthesis_time);
        printf("   Total time (Query + Synthesis): %.3f seconds\n", $query_time + $synthesis_time);
    } else {
        echo "   Synthesis: FAILED\n";
    }
} else {
    echo "   AudioQuery: FAILED\n";
}

// メモリ使用量測定
echo "\n4. Memory Usage\n";
printf("   Current memory usage: %.2f MB\n", memory_get_usage(true) / (1024 * 1024));
printf("   Peak memory usage: %.2f MB\n", memory_get_peak_usage(true) / (1024 * 1024));

// 終了時間測定
$finalize_start = microtime(true);
voicevox_finalize();
$finalize_time = microtime(true) - $finalize_start;

printf("\n5. Finalization time: %.3f seconds\n", $finalize_time);

echo "\n=== Performance Test Completed ===\n";

// 性能評価
$avg_tts_time = $total_time / $completed_tests;
echo "\n=== Performance Evaluation ===\n";

if ($init_time < 15) {
    echo "✓ Initialization: GOOD (< 15s)\n";
} elseif ($init_time < 30) {
    echo "⚠ Initialization: ACCEPTABLE (15-30s)\n";
} else {
    echo "✗ Initialization: SLOW (> 30s)\n";
}

if ($avg_tts_time < 1.0) {
    echo "✓ TTS Speed: EXCELLENT (< 1s)\n";
} elseif ($avg_tts_time < 2.0) {
    echo "✓ TTS Speed: GOOD (1-2s)\n";
} elseif ($avg_tts_time < 5.0) {
    echo "⚠ TTS Speed: ACCEPTABLE (2-5s)\n";
} else {
    echo "✗ TTS Speed: SLOW (> 5s)\n";
}

$peak_memory_mb = memory_get_peak_usage(true) / (1024 * 1024);
if ($peak_memory_mb < 500) {
    echo "✓ Memory Usage: GOOD (< 500MB)\n";
} elseif ($peak_memory_mb < 1000) {
    echo "⚠ Memory Usage: ACCEPTABLE (500MB-1GB)\n";
} else {
    echo "✗ Memory Usage: HIGH (> 1GB)\n";
}

echo "\nTest completed successfully.\n";
?>
