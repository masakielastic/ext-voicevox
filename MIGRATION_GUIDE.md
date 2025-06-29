# VOICEVOX PHP Extension - 手続き型API からOOP APIへの移行ガイド

## 概要

VOICEVOX PHP拡張機能は、手続き型API（`voicevox_*` 関数）を **v1.0.0で削除予定** です（2025年9月29日）。このガイドは、OOP API（`\Voicevox\Engine`）への移行を支援します。

## 移行の必要性

### なぜ移行が必要か？

1. **保守性の向上**: 単一のAPIアーキテクチャに統一
2. **エラーハンドリング**: 例外ベースのより堅牢な処理
3. **将来性**: 新機能はOOP APIでのみ提供
4. **メモリ効率**: 重複実装の除去によるパフォーマンス向上

### 移行タイムライン

- **現在**: 手続き型APIは非推奨警告を表示
- **2025年9月29日**: v1.0.0リリースで手続き型API完全削除
- **推奨期限**: 2025年8月末までの移行完了

## 関数対応表

### 基本操作

| 手続き型API | OOP API | 備考 |
|------------|---------|------|
| `voicevox_initialize($lib, $dict)` | `$engine->initialize($lib, $dict)` | シングルトンパターン |
| `voicevox_finalize()` | `$engine->finalize()` | 自動クリーンアップも利用可能 |
| `voicevox_get_version()` | `$engine->getVersion()` | 戻り値は同じ |
| `voicevox_is_initialized()` | `$engine->isInitialized()` | 戻り値は同じ |

### 音声合成操作

| 手続き型API | OOP API | 備考 |
|------------|---------|------|
| `voicevox_tts($text, $speaker)` | `$engine->tts($text, $speaker)` | 例外処理が改善 |
| `voicevox_audio_query($text, $speaker)` | `$engine->audioQuery($text, $speaker)` | JSONレスポンス |
| `voicevox_synthesis($query, $speaker)` | `$engine->synthesis($query, $speaker)` | バイナリレスポンス |

## コード移行例

### 1. 基本的な初期化と音声合成

#### 移行前（手続き型）
```php
<?php
// ❌ 非推奨 - 削除予定
$lib_path = '/path/to/libvoicevox_core.so';
$dict_path = '/path/to/open_jtalk_dic_utf_8-1.11';

if (voicevox_initialize($lib_path, $dict_path)) {
    $wav_data = voicevox_tts("こんにちは", 3);
    if ($wav_data !== false) {
        file_put_contents('output.wav', $wav_data);
    }
    voicevox_finalize();
} else {
    echo "初期化に失敗しました\n";
}
?>
```

#### 移行後（OOP）
```php
<?php
// ✅ 推奨 - モダンなOOP API
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

$lib_path = '/path/to/libvoicevox_core.so';
$dict_path = '/path/to/open_jtalk_dic_utf_8-1.11';

try {
    $engine = Engine::getInstance();
    $engine->initialize($lib_path, $dict_path);
    
    $wav_data = $engine->tts("こんにちは", 3);
    file_put_contents('output.wav', $wav_data);
    
    $engine->finalize();
} catch (VoicevoxException $e) {
    echo "VOICEVOX エラー: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般エラー: " . $e->getMessage() . "\n";
}
?>
```

### 2. AudioQuery + Synthesis 分離処理

#### 移行前（手続き型）
```php
<?php
// ❌ 非推奨 - 削除予定
if (voicevox_is_initialized()) {
    $audio_query = voicevox_audio_query("テスト音声", 0);
    if ($audio_query !== false) {
        // パラメータ調整
        $query_data = json_decode($audio_query, true);
        $query_data['speed_scale'] = 1.2;
        $modified_query = json_encode($query_data);
        
        $wav_data = voicevox_synthesis($modified_query, 0);
        if ($wav_data !== false) {
            file_put_contents('modified_output.wav', $wav_data);
        }
    }
}
?>
```

#### 移行後（OOP）
```php
<?php
// ✅ 推奨 - 例外処理付きOOP API
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

try {
    $engine = Engine::getInstance();
    
    if ($engine->isInitialized()) {
        $audio_query = $engine->audioQuery("テスト音声", 0);
        
        // パラメータ調整
        $query_data = json_decode($audio_query, true);
        $query_data['speed_scale'] = 1.2;
        $modified_query = json_encode($query_data);
        
        $wav_data = $engine->synthesis($modified_query, 0);
        file_put_contents('modified_output.wav', $wav_data);
    }
} catch (VoicevoxException $e) {
    echo "VOICEVOX エラー: " . $e->getMessage() . "\n";
}
?>
```

### 3. エラーハンドリングの改善

#### 移行前（手続き型）
```php
<?php
// ❌ 戻り値チェックによるエラー処理
$result = voicevox_tts("", 3); // 空文字列
if ($result === false) {
    echo "TTS処理に失敗しました\n";
    // エラーの詳細は不明
}
?>
```

#### 移行後（OOP）
```php
<?php
// ✅ 例外による詳細なエラー処理
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

try {
    $engine = Engine::getInstance();
    $result = $engine->tts("", 3); // 空文字列
} catch (VoicevoxException $e) {
    echo "VOICEVOX エラー: " . $e->getMessage() . "\n";
    echo "エラーコード: " . $e->getCode() . "\n";
    // 詳細なエラー情報が利用可能
}
?>
```

## 高度な移行パターン

### 1. クラス内での使用

#### 移行前
```php
<?php
class SpeechSynthesizer {
    private $initialized = false;
    
    public function __construct($lib_path, $dict_path) {
        $this->initialized = voicevox_initialize($lib_path, $dict_path);
    }
    
    public function speak($text, $speaker_id = 3) {
        if (!$this->initialized) {
            return false;
        }
        return voicevox_tts($text, $speaker_id);
    }
    
    public function __destruct() {
        if ($this->initialized) {
            voicevox_finalize();
        }
    }
}
?>
```

#### 移行後
```php
<?php
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

class SpeechSynthesizer {
    private $engine;
    private $initialized = false;
    
    public function __construct($lib_path, $dict_path) {
        try {
            $this->engine = Engine::getInstance();
            $this->engine->initialize($lib_path, $dict_path);
            $this->initialized = true;
        } catch (VoicevoxException $e) {
            throw new RuntimeException("音声エンジンの初期化に失敗: " . $e->getMessage());
        }
    }
    
    public function speak($text, $speaker_id = 3) {
        if (!$this->initialized) {
            throw new RuntimeException("音声エンジンが初期化されていません");
        }
        
        try {
            return $this->engine->tts($text, $speaker_id);
        } catch (VoicevoxException $e) {
            throw new RuntimeException("音声合成に失敗: " . $e->getMessage());
        }
    }
    
    public function __destruct() {
        if ($this->initialized && $this->engine) {
            try {
                $this->engine->finalize();
            } catch (VoicevoxException $e) {
                // ログ出力など
                error_log("音声エンジン終了処理エラー: " . $e->getMessage());
            }
        }
    }
}
?>
```

### 2. 複数の音声合成処理

#### 移行前
```php
<?php
// ❌ 複数処理での状態管理が複雑
$texts = ["おはよう", "こんにちは", "こんばんは"];
$results = [];

if (voicevox_initialize($lib_path, $dict_path)) {
    foreach ($texts as $index => $text) {
        $wav = voicevox_tts($text, $index % 4); // 話者IDをローテーション
        if ($wav !== false) {
            $results[] = $wav;
        }
    }
    voicevox_finalize();
}
?>
```

#### 移行後
```php
<?php
// ✅ 例外処理とクリーンなコード構造
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

$texts = ["おはよう", "こんにちは", "こんばんは"];
$results = [];

try {
    $engine = Engine::getInstance();
    $engine->initialize($lib_path, $dict_path);
    
    foreach ($texts as $index => $text) {
        try {
            $wav = $engine->tts($text, $index % 4);
            $results[] = $wav;
        } catch (VoicevoxException $e) {
            error_log("音声合成失敗 '$text': " . $e->getMessage());
            continue; // 他の処理を継続
        }
    }
    
} catch (VoicevoxException $e) {
    throw new RuntimeException("音声エンジンエラー: " . $e->getMessage());
} finally {
    // 確実な終了処理
    if (isset($engine)) {
        try {
            $engine->finalize();
        } catch (VoicevoxException $e) {
            error_log("終了処理エラー: " . $e->getMessage());
        }
    }
}
?>
```

## 移行時の注意点

### 1. 例外処理の必須化

**重要**: OOP APIは例外ベースのエラーハンドリングを使用します。

- **VoicevoxException**: VOICEVOX特有のエラー
- **Exception**: 一般的なPHPエラー

### 2. シングルトンパターン

OOP APIはシングルトンパターンを使用：

```php
// ✅ 正しい使用法
$engine1 = Engine::getInstance();
$engine2 = Engine::getInstance();
// $engine1 === $engine2 (同じインスタンス)

// ❌ 間違った使用法
$engine = new Engine(); // 直接インスタンス化は不可
```

### 3. 自動リソース管理

OOP APIは自動的なメモリ管理を提供：

```php
// 明示的な finalize() 呼び出しは任意
// プロセス終了時に自動的にクリーンアップされる
try {
    $engine = Engine::getInstance();
    $engine->initialize($lib_path, $dict_path);
    $wav = $engine->tts("テスト", 3);
    // finalize() の呼び出しは任意
} catch (VoicevoxException $e) {
    // エラーハンドリング
}
```

## トラブルシューティング

### Q1: 移行後に "Class not found" エラーが発生

**A**: `use` 文の追加を確認してください：

```php
// 必須
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

// または完全限定名
$engine = \Voicevox\Engine::getInstance();
```

### Q2: 例外が発生していない

**A**: エラー報告レベルを確認：

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // OOP API呼び出し
} catch (VoicevoxException $e) {
    echo "VOICEVOX例外: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "一般例外: " . $e->getMessage() . "\n";
}
```

### Q3: 手続き型とOOPの併用は可能？

**A**: 現在は可能ですが、**非推奨**です。v1.0.0では手続き型APIが削除されるため、完全移行を推奨します。

### Q4: パフォーマンスに違いはある？

**A**: OOP APIの方が効率的です：

- シングルトンパターンによるメモリ効率
- 重複実装の除去
- 最適化されたエラーハンドリング

## 移行検証

### 移行完了チェックリスト

- [ ] すべての `voicevox_*` 関数呼び出しを置換
- [ ] 適切な `use` 文を追加
- [ ] 例外処理（try-catch）を実装
- [ ] エラーハンドリングのテスト
- [ ] 統合テストの実行

### テスト方法

```php
<?php
// 移行確認用の簡単なテスト
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

try {
    $engine = Engine::getInstance();
    echo "✅ Engine インスタンス取得成功\n";
    
    $version = $engine->getVersion();
    echo "✅ バージョン取得成功: $version\n";
    
    $status = $engine->isInitialized();
    echo "✅ 状態確認成功: " . ($status ? 'initialized' : 'not initialized') . "\n";
    
    echo "🎉 OOP API移行完了！\n";
    
} catch (VoicevoxException $e) {
    echo "❌ VOICEVOX例外: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ 一般例外: " . $e->getMessage() . "\n";
}
?>
```

## 実装例

### デモアプリケーション
プロジェクトには移行完了済みのデモアプリケーションが含まれています：

- **demo/voicevox_server.php** - 完全なOOP API実装例
- **demo/new_server.php** - 高度なOOP API実装例

両方のファイルはOOP APIのベストプラクティスを示しており、移行の参考として活用できます。

## サポート

### ドキュメント
- [PROCEDURAL_API_REMOVAL_PLAN.md](PROCEDURAL_API_REMOVAL_PLAN.md) - 削除計画の詳細
- [CLAUDE.md](CLAUDE.md) - 開発者向けガイド
- [README.md](README.md) - 基本的な使用方法
- [demo/voicevox_server.php](demo/voicevox_server.php) - 実装例

### 問題報告
移行で問題が発生した場合は、以下の情報と共にissueを作成してください：

1. PHPバージョン
2. 拡張機能バージョン
3. 移行前のコード
4. 移行後のコード
5. エラーメッセージ（完全なスタックトレース）

---

**最終更新**: 2025-06-29  
**対象バージョン**: v0.1.0 → v1.0.0  
**移行期限**: 2025年9月29日