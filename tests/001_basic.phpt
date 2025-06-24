--TEST--
VOICEVOX extension basic functionality test
--SKIPIF--
<?php if (!extension_loaded('voicevox')) print 'skip VOICEVOX extension not available'; ?>
--FILE--
<?php
echo "=== VOICEVOX Extension Basic Test ===\n";

// 1. Extension loaded check
var_dump(extension_loaded('voicevox'));

// 2. Function existence check
$functions = ['voicevox_initialize', 'voicevox_finalize', 'voicevox_get_version', 
              'voicevox_tts', 'voicevox_audio_query', 'voicevox_synthesis', 
              'voicevox_is_initialized'];

foreach ($functions as $func) {
    var_dump(function_exists($func));
}

// 3. Constants check
var_dump(defined('VOICEVOX_RESULT_OK'));
var_dump(defined('VOICEVOX_ACCELERATION_MODE_AUTO'));
var_dump(defined('VOICEVOX_ACCELERATION_MODE_CPU'));
var_dump(defined('VOICEVOX_ACCELERATION_MODE_GPU'));

echo "Basic test completed\n";
?>
--EXPECT--
=== VOICEVOX Extension Basic Test ===
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
Basic test completed
