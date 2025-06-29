--TEST--
VOICEVOX extension basic functionality test (OOP version)
--SKIPIF--
<?php if (!extension_loaded('voicevox')) print 'skip VOICEVOX extension not available'; ?>
--FILE--
<?php
echo "=== VOICEVOX Extension Basic Test (OOP) ===\n";

// 1. Extension loaded check
var_dump(extension_loaded('voicevox'));

// 2. OOP Class existence check
var_dump(class_exists('\\Voicevox\\Engine'));
var_dump(class_exists('\\Voicevox\\Exception\\VoicevoxException'));

// 3. OOP Method existence check
if (class_exists('\\Voicevox\\Engine')) {
    $engine = \Voicevox\Engine::getInstance();
    $methods = ['getInstance', 'initialize', 'isInitialized', 'getVersion', 
                'tts', 'audioQuery', 'synthesis', 'finalize'];
    
    foreach ($methods as $method) {
        var_dump(method_exists($engine, $method));
    }
}

// 4. Procedural functions still exist (deprecated)
$functions = ['voicevox_initialize', 'voicevox_finalize', 'voicevox_get_version', 
              'voicevox_tts', 'voicevox_audio_query', 'voicevox_synthesis', 
              'voicevox_is_initialized'];

foreach ($functions as $func) {
    var_dump(function_exists($func));
}

// 5. Constants check
var_dump(defined('VOICEVOX_RESULT_OK'));
var_dump(defined('VOICEVOX_ACCELERATION_MODE_AUTO'));
var_dump(defined('VOICEVOX_ACCELERATION_MODE_CPU'));
var_dump(defined('VOICEVOX_ACCELERATION_MODE_GPU'));

echo "Basic OOP test completed\n";
?>
--EXPECT--
=== VOICEVOX Extension Basic Test (OOP) ===
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
Basic OOP test completed
