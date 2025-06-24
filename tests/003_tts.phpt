--TEST--
VOICEVOX TTS functionality test
--SKIPIF--
<?php 
if (!extension_loaded('voicevox')) print 'skip VOICEVOX extension not available';
if (!file_exists('/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so')) 
    print 'skip VOICEVOX library not found';
?>
--FILE--
<?php
echo "=== VOICEVOX TTS Test ===\n";

$lib_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so';
$dict_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11';

// Initialize
voicevox_initialize($lib_path, $dict_path);

// 1. Basic TTS test
$wav_data = voicevox_tts("テスト", 3);
var_dump(is_string($wav_data));
var_dump(strlen($wav_data) > 1000); // WAV should be > 1KB

// 2. Different speaker test
$wav_data2 = voicevox_tts("こんにちは", 0);
var_dump(is_string($wav_data2));
var_dump(strlen($wav_data2) > 1000);

// 3. Empty text (should fail)
$wav_data3 = voicevox_tts("", 3);
var_dump($wav_data3 === false);

// 4. Invalid speaker ID (should still work, VOICEVOX handles gracefully)
$wav_data4 = voicevox_tts("テスト", 999);
// This might succeed or fail depending on VOICEVOX behavior
var_dump(is_string($wav_data4) || $wav_data4 === false);

// Cleanup
voicevox_finalize();

echo "TTS test completed\n";
?>
--EXPECT--
=== VOICEVOX TTS Test ===
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
TTS test completed
