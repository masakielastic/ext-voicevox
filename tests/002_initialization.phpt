--TEST--
VOICEVOX initialization and version test (OOP version)
--SKIPIF--
<?php 
if (!extension_loaded('voicevox')) print 'skip VOICEVOX extension not available';
if (!file_exists('/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so')) 
    print 'skip VOICEVOX library not found';
?>
--FILE--
<?php
echo "=== VOICEVOX Initialization Test (OOP) ===\n";

$lib_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so';
$dict_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11';

// 1. Get engine instance
$engine = \Voicevox\Engine::getInstance();
var_dump($engine instanceof \Voicevox\Engine);

// 2. Initial state check
var_dump($engine->isInitialized());

// 3. Initialize VOICEVOX
$result = $engine->initialize($lib_path, $dict_path);
var_dump($result);

// 4. Check initialized state
var_dump($engine->isInitialized());

// 5. Get version
$version = $engine->getVersion();
var_dump(is_string($version));
var_dump(strlen($version) > 0);

// 6. Exception handling test - double initialization
try {
    $result2 = $engine->initialize($lib_path, $dict_path);
    var_dump(false); // Should not reach here
} catch (\Voicevox\Exception\VoicevoxException $e) {
    var_dump(true); // Exception properly thrown
}

// 7. Finalize
$finalize_result = $engine->finalize();
var_dump($finalize_result);

// 8. Check finalized state
var_dump($engine->isInitialized());

echo "OOP Initialization test completed\n";
?>
--EXPECT--
=== VOICEVOX Initialization Test (OOP) ===
bool(true)
bool(false)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
OOP Initialization test completed
