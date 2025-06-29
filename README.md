# VOICEVOX PHP Extension

VOICEVOX音声合成エンジンのPHP拡張機能です。手続き型APIとオブジェクト指向APIの両方をサポートしています。

## 必要な環境

- PHP 8.0以上
- VOICEVOX Engine（音声合成ライブラリ）
- OpenJTalk辞書ファイル

## VOICEVOX環境設定

### 必須環境変数

拡張機能を使用する前に、以下の環境変数またはパスを設定してください：

#### VOICEVOX_LIB_PATH
VOICEVOX音声合成ライブラリ（`libvoicevox_core.so`）のパスを指定します。

```bash
export VOICEVOX_LIB_PATH="/path/to/libvoicevox_core.so"
```

**例（一般的な配置）:**
```bash
# VOICEVOX AppImageを展開した場合
export VOICEVOX_LIB_PATH="/home/user/.voicevox/squashfs-root/vv-engine/libvoicevox_core.so"

# システムインストールの場合
export VOICEVOX_LIB_PATH="/usr/local/lib/libvoicevox_core.so"
```

#### VOICEVOX_DICT_PATH
OpenJTalk辞書ディレクトリのパスを指定します。

```bash
export VOICEVOX_DICT_PATH="/path/to/open_jtalk_dic_utf_8-1.11"
```

**例（一般的な配置）:**
```bash
# VOICEVOX AppImageを展開した場合
export VOICEVOX_DICT_PATH="/home/user/.voicevox/squashfs-root/vv-engine/pyopenjtalk/open_jtalk_dic_utf_8-1.11"

# システムインストールの場合
export VOICEVOX_DICT_PATH="/usr/local/share/open_jtalk/dic"
```

### 環境変数の永続化

**Bashの場合（~/.bashrc に追加）:**
```bash
echo 'export VOICEVOX_LIB_PATH="/your/path/to/libvoicevox_core.so"' >> ~/.bashrc
echo 'export VOICEVOX_DICT_PATH="/your/path/to/open_jtalk_dic_utf_8-1.11"' >> ~/.bashrc
source ~/.bashrc
```

**systemd環境ファイルの場合（/etc/environment）:**
```
VOICEVOX_LIB_PATH=/your/path/to/libvoicevox_core.so
VOICEVOX_DICT_PATH=/your/path/to/open_jtalk_dic_utf_8-1.11
```

### ファイル存在確認

設定前に必要なファイルが存在することを確認してください：

```bash
# ライブラリファイルの確認
ls -la "$VOICEVOX_LIB_PATH"

# 辞書ディレクトリの確認  
ls -la "$VOICEVOX_DICT_PATH"

# 辞書ファイルの確認
ls -la "$VOICEVOX_DICT_PATH"/*.dic
```

### 依存ライブラリの設定

VOICEVOX Engineは `libonnxruntime.so` に依存しています。環境によっては以下のようにシンボリックリンクの作成が必要です：

```bash
# libonnxruntime.so.1.13.1 から libonnxruntime.so へのシンボリックリンク作成
ln -sf libonnxruntime.so.1.13.1 libonnxruntime.so
```

このシンボリックリンクは、VOICEVOX Engineが `libonnxruntime.so` を参照する際に、実際のライブラリファイル `libonnxruntime.so.1.13.1` にリンクするために必要です。

## ビルド方法

### デフォルト設定でビルド

```bash
make
```

### カスタムパスでビルド

環境変数を使用してパスを指定できます：

```bash
make SRCDIR=/path/to/source \
     BUILDDIR=/path/to/build \
     TOP_SRCDIR=/path/to/source \
     TOP_BUILDDIR=/path/to/build \
     PHPLIBDIR=/path/to/modules
```

#### 環境変数

- `SRCDIR`: ソースディレクトリのパス
- `BUILDDIR`: ビルドディレクトリのパス  
- `TOP_SRCDIR`: トップレベルソースディレクトリのパス
- `TOP_BUILDDIR`: トップレベルビルドディレクトリのパス
- `PHPLIBDIR`: PHPライブラリの出力ディレクトリのパス

指定しない場合は `/home/masakielastic/projects/ext-voicevox` がデフォルト値として使用されます。

## 使用方法

### 基本的な使用例

#### 手続き型API（従来方式）

```php
<?php
// 拡張機能の確認
if (!extension_loaded('voicevox')) {
    die('VOICEVOX extension not loaded');
}

// 初期化
$lib_path = $_ENV['VOICEVOX_LIB_PATH'] ?? '/path/to/libvoicevox_core.so';
$dict_path = $_ENV['VOICEVOX_DICT_PATH'] ?? '/path/to/open_jtalk_dic_utf_8-1.11';

if (!voicevox_initialize($lib_path, $dict_path)) {
    die('Failed to initialize VOICEVOX');
}

// 音声合成
$text = "こんにちは、世界！";
$speaker_id = 3; // 話者ID（0-46等、利用可能な話者による）
$wav_data = voicevox_tts($text, $speaker_id);

if ($wav_data !== false) {
    // WAVファイルとして保存
    file_put_contents('output.wav', $wav_data);
    echo "音声ファイルを生成しました: output.wav\n";
} else {
    echo "音声合成に失敗しました\n";
}

// 終了処理
voicevox_finalize();
?>
```

#### オブジェクト指向API（新方式）

```php
<?php
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

try {
    // エンジンインスタンス取得（シングルトン）
    $engine = Engine::getInstance();
    
    // 初期化
    $lib_path = $_ENV['VOICEVOX_LIB_PATH'] ?? '/path/to/libvoicevox_core.so';
    $dict_path = $_ENV['VOICEVOX_DICT_PATH'] ?? '/path/to/open_jtalk_dic_utf_8-1.11';
    
    if (!$engine->initialize($lib_path, $dict_path)) {
        throw new Exception('Failed to initialize VOICEVOX');
    }
    
    // 音声合成
    $text = "こんにちは、世界！";
    $speaker_id = 3;
    $wav_data = $engine->tts($text, $speaker_id);
    
    if ($wav_data !== false) {
        file_put_contents('output_oop.wav', $wav_data);
        echo "音声ファイルを生成しました: output_oop.wav\n";
    } else {
        echo "音声合成に失敗しました\n";
    }
    
    // バージョン情報
    echo "VOICEVOX Version: " . $engine->getVersion() . "\n";
    
    // 終了処理
    $engine->finalize();
    
} catch (VoicevoxException $e) {
    echo "VOICEVOX Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getVoicevoxCode() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
?>
```

### 高度な使用例（AudioQuery使用）

```php
<?php
// AudioQueryを使用した詳細制御
$engine = \Voicevox\Engine::getInstance();
$engine->initialize($lib_path, $dict_path);

$text = "音声のピッチを調整します";
$speaker_id = 1;

// 1. AudioQuery生成
$audio_query_json = $engine->audioQuery($text, $speaker_id);
$audio_query = json_decode($audio_query_json, true);

// 2. パラメータ調整
$audio_query['pitch_scale'] = 1.2;     // ピッチを20%上げる
$audio_query['speed_scale'] = 0.9;     // 速度を10%下げる
$audio_query['volume_scale'] = 1.1;    // 音量を10%上げる

// 3. 音声合成実行
$modified_query = json_encode($audio_query);
$wav_data = $engine->synthesis($modified_query, $speaker_id);

if ($wav_data !== false) {
    file_put_contents('modified_voice.wav', $wav_data);
    echo "調整された音声を生成しました\n";
}

$engine->finalize();
?>
```

### サーバーとしての使用

拡張機能をWebサーバーとして使用することもできます：

```bash
# OOP版サーバー起動
php -d extension=modules/voicevox.so -S localhost:8080 demo/new_server.php

# 従来版サーバー起動  
php -d extension=modules/voicevox.so -S localhost:8081 demo/voicevox_server.php
```

APIエンドポイント：
- `GET /status` - サーバー状態確認
- `GET /speakers` - 利用可能話者一覧
- `POST /tts` - テキスト音声合成
- `POST /audio_query` - AudioQuery生成
- `POST /synthesis` - AudioQueryからの音声合成

## 利用可能な関数・メソッド

### 手続き型関数

| 関数名 | 説明 | 戻り値 |
|--------|------|--------|
| `voicevox_initialize($lib_path, $dict_path)` | VOICEVOX初期化 | bool |
| `voicevox_is_initialized()` | 初期化状態確認 | bool |
| `voicevox_get_version()` | バージョン取得 | string |
| `voicevox_tts($text, $speaker_id, $kana=false)` | 音声合成 | string\|false |
| `voicevox_audio_query($text, $speaker_id, $kana=false)` | AudioQuery生成 | string\|false |
| `voicevox_synthesis($audio_query, $speaker_id, $enable_upspeak=true)` | 音声合成実行 | string\|false |
| `voicevox_finalize()` | 終了処理 | bool |

### OOPメソッド

**Voicevox\Engine クラス:**
- `getInstance()` - シングルトンインスタンス取得
- `initialize($lib_path, $dict_path)` - 初期化
- `isInitialized()` - 初期化状態確認
- `getVersion()` - バージョン取得
- `tts($text, $speaker_id, $kana=false)` - 音声合成
- `audioQuery($text, $speaker_id, $kana=false)` - AudioQuery生成
- `synthesis($audio_query, $speaker_id, $enable_upspeak=true)` - 音声合成実行
- `finalize()` - 終了処理

**Voicevox\Exception\VoicevoxException クラス:**
- `getVoicevoxCode()` - VOICEVOX固有エラーコード取得

### 定数

- `VOICEVOX_RESULT_OK` - 成功コード (0)
- `VOICEVOX_ACCELERATION_MODE_AUTO` - 自動モード (0)
- `VOICEVOX_ACCELERATION_MODE_CPU` - CPUモード (1)
- `VOICEVOX_ACCELERATION_MODE_GPU` - GPUモード (2)

## テスト実行

```bash
make test
```

### 個別テスト実行

```bash
# 基本テスト
php -d extension=modules/voicevox.so test_enhanced_oop.php

# 環境設定テスト
php -d extension=modules/voicevox.so test_with_env.php

# OOP互換性テスト
php -d extension=modules/voicevox.so test_oop_compat.php
```