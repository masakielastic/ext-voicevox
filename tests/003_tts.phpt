--TEST--
VOICEVOX TTS functionality test (OOP version)
--SKIPIF--
<?php 
if (!extension_loaded('voicevox')) print 'skip VOICEVOX extension not available';
if (!file_exists('/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so')) 
    print 'skip VOICEVOX library not found';
?>
--FILE--
<?php
echo "=== VOICEVOX TTS Test (OOP) ===\n";

$lib_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so';
$dict_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11';

// Initialize
$engine = \Voicevox\Engine::getInstance();
$engine->initialize($lib_path, $dict_path);

// 1. Basic TTS test
$wav_data = $engine->tts("テスト", 3);
var_dump(is_string($wav_data));
var_dump(strlen($wav_data) > 1000); // WAV should be > 1KB

// 2. Different speaker test
$wav_data2 = $engine->tts("こんにちは", 0);
var_dump(is_string($wav_data2));
var_dump(strlen($wav_data2) > 1000);

// 3. Empty text (should throw exception)
try {
    $wav_data3 = $engine->tts("", 3);
    var_dump(false); // Should not reach here
} catch (\Voicevox\Exception\VoicevoxException $e) {
    var_dump(true); // Exception properly thrown
}

// 4. Invalid speaker ID (behavior may vary)
try {
    $wav_data4 = $engine->tts("テスト", 999);
    var_dump(is_string($wav_data4)); // Success
} catch (\Voicevox\Exception\VoicevoxException $e) {
    var_dump(true); // Or exception
}

// 5. AudioQuery + Synthesis test
$audio_query = $engine->audioQuery("分離テスト", 3);
var_dump(is_string($audio_query));

$synthesis_wav = $engine->synthesis($audio_query, 3);
var_dump(is_string($synthesis_wav));
var_dump(strlen($synthesis_wav) > 1000);

// Cleanup
$engine->finalize();

echo "OOP TTS test completed\n";
?>
--EXPECT--
=== VOICEVOX TTS Test (OOP) ===
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
OOP TTS test completed
