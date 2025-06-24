# VOICEVOX環境設定ガイド

## 現在の状況

**⚠️ 注意:** 現在のOOP実装には以下の制限があります：

### 制限事項
1. **状態管理の分離**: 手続き型とOOPの初期化状態が独立している
2. **メモリ管理問題**: 一部のケースでメモリ破損が発生する可能性
3. **初期化の重複**: 手続き型とOOPを同時使用時の問題

## 推奨使用方法

### **パターン1: 手続き型のみ使用（推奨）**

```bash
# 環境変数設定
export VOICEVOX_LIB_PATH="/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so"
export VOICEVOX_DICT_PATH="/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11"

# テスト実行
php -d extension=modules/voicevox.so your_script.php
```

```php
<?php
// 手続き型API使用例
$result = voicevox_initialize($lib_path, $dict_path);
if ($result) {
    $wav = voicevox_tts("こんにちは", 3);
    voicevox_finalize();
}
?>
```

### **パターン2: OOPのみ使用（開発中）**

```php
<?php
// OOP API使用例（現在開発中）
use Voicevox\Engine;

$engine = Engine::getInstance();
$result = $engine->initialize($lib_path, $dict_path);
if ($result) {
    $wav = $engine->tts("こんにちは", 3);
    $engine->finalize();
}
?>
```

## 環境変数の設定

### **1. ライブラリパスの確認**

```bash
# ライブラリの存在確認
ls -la /home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so
ls -la /home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11/
```

### **2. 環境変数設定方法**

#### **一時的設定（現在のセッションのみ）**
```bash
export VOICEVOX_LIB_PATH="/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so"
export VOICEVOX_DICT_PATH="/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11"
```

#### **永続的設定（.bashrcに追加）**
```bash
echo 'export VOICEVOX_LIB_PATH="/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so"' >> ~/.bashrc
echo 'export VOICEVOX_DICT_PATH="/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11"' >> ~/.bashrc
source ~/.bashrc
```

#### **スクリプト内設定**
```php
<?php
// PHP内で直接設定
define('VOICEVOX_LIB_PATH', '/home/masakielastic/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so');
define('VOICEVOX_DICT_PATH', '/home/masakielastic/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11');
?>
```

## テスト実行例

### **基本テスト**
```bash
# 環境変数設定後
php -d extension=modules/voicevox.so test_enhanced_oop.php
```

### **実際のTTSテスト**
```bash
# 実際のライブラリパスを使用
php -d extension=modules/voicevox.so test_with_env.php
```

### **サーバーテスト**
```bash
# OOP版サーバー
php -d extension=modules/voicevox.so -S localhost:8080 demo/new_server.php

# 従来版サーバー
php -d extension=modules/voicevox.so -S localhost:8081 demo/voicevox_server.php
```

## 現在の動作状況

### **✅ 動作する機能**
- 基本的な手続き型API
- 拡張機能のロード
- ライブラリの初期化
- TTS基本機能
- 定数定義

### **⚠️ 制限がある機能**
- 手続き型とOOPの同時使用
- 状態管理の同期
- 一部のエラーケース処理

### **🚧 開発中の機能**
- 完全なOOP実装
- 状態管理の統一
- メモリ管理の最適化

## トラブルシューティング

### **Warning: voicevox_get_version(): VOICEVOX library is not loaded**
→ 正常な警告。ライブラリ未初期化時のデフォルト動作

### **zend_mm_heap corrupted**
→ メモリ管理の問題。手続き型とOOPの同時使用を避ける

### **Error: 無効なspeaker_idです**
→ 正常なエラー。存在しない話者IDを指定した場合の想定動作

## 次のステップ

1. **手続き型APIの安定化** ✅ 完了
2. **OOP状態管理の改善** 🚧 開発中
3. **メモリ管理の最適化** 🚧 開発中
4. **テストスイートの拡充** 📋 計画中