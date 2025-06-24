--TEST--
VOICEVOX initialization and version test
--SKIPIF--
<?php 
if (!extension_loaded('voicevox')) print 'skip VOICEVOX extension not available';
if (!file_exists('/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so')) 
    print 'skip VOICEVOX library not found';
?>
--FILE--
<?php
echo "=== VOICEVOX Initialization Test ===\n";

$lib_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so';
$dict_path = '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11';

// 1. Initial state check
var_dump(voicevox_is_initialized());

// 2. Initialize VOICEVOX
$result = voicevox_initialize($lib_path, $dict_path);
var_dump($result);

// 3. Check initialized state
var_dump(voicevox_is_initialized());

// 4. Get version
$version = voicevox_get_version();
var_dump(is_string($version));
var_dump(strlen($version) > 0);

// 5. Double initialization (should fail)
$result2 = voicevox_initialize($lib_path, $dict_path);
var_dump($result2);

// 6. Finalize
$finalize_result = voicevox_finalize();
var_dump($finalize_result);

// 7. Check finalized state
var_dump(voicevox_is_initialized());

echo "Initialization test completed\n";
?>
--EXPECT--
=== VOICEVOX Initialization Test ===
bool(false)
bool(true)
bool(true)
bool(true)
bool(true)
bool(false)
bool(true)
bool(false)
Initialization test completed
