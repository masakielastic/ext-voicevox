# VOICEVOX PHP Extension - 手続き型API削除計画

## 概要

このドキュメントはVOICEVOX PHP拡張機能における手続き型API（Procedural API）の段階的削除計画を示します。プロジェクトは既にOOP-first設計に移行しており、手続き型APIは非推奨として維持されています。

## 現状分析

### 削除対象の手続き型API関数

以下の7つの手続き型関数が削除対象です：

1. `voicevox_initialize()` - 初期化関数
2. `voicevox_finalize()` - 終了処理関数
3. `voicevox_get_version()` - バージョン取得関数
4. `voicevox_tts()` - テキスト音声合成関数
5. `voicevox_audio_query()` - AudioQuery生成関数
6. `voicevox_synthesis()` - 音声合成関数
7. `voicevox_is_initialized()` - 初期化状態確認関数

### 影響範囲分析

#### ✅ **本番コード**: 完全移行済み
- **demo/voicevox_server.php**: 100% OOP API使用
- **demo/new_server.php**: 100% OOP API使用
- **その他の本番コード**: 手続き型API使用なし

#### 🔄 **テストコード**: 部分的に使用中
- **影響度**: 低（テストコードのみ）
- **使用箇所**: 8ファイル、計16箇所
- **種類**: 
  - 関数存在確認テスト（削除不要）
  - 互換性テスト（要更新）
  - 非推奨警告テスト（削除後は無効化）

## 段階的削除計画

### Phase 1: 準備段階（即座実施可能）
**期間**: 1日  
**リスク**: 極低

#### 1.1 テストコードの更新
- **対象**: `tests/test_enhanced_oop.php` (2箇所)
- **対象**: `tests/test_oop_compat.php` (2箇所)
- **作業**: 手続き型API呼び出しをOOP APIに置換
- **影響**: なし（テストコードのみ）

#### 1.2 ドキュメント更新
- **CLAUDE.md**: 手続き型APIの削除予定を明記
- **README.md**: 削除スケジュールの追加

### Phase 2: 非推奨化強化（2週間後）
**期間**: 1日  
**リスク**: 低

#### 2.1 非推奨警告の強化
- **現状**: `E_DEPRECATED` 警告を出力
- **強化**: より詳細な移行ガイドメッセージ
- **追加**: 削除予定日を警告メッセージに含める

#### 2.2 移行ガイドの充実
- **作成**: `MIGRATION_GUIDE.md`
- **内容**: 
  - 手続き型→OOP APIの対応表
  - コード変換例
  - トラブルシューティング

### Phase 3: 互換性テストの調整（1ヶ月後）
**期間**: 半日  
**リスク**: 極低

#### 3.1 テストスイートの整理
- **非推奨警告テスト**: 削除後は無効化または削除
- **互換性テスト**: OOP API単体テストに変更
- **機能テスト**: 影響なし（継続実行）

### Phase 4: 実装削除（3ヶ月後）
**期間**: 1日  
**リスク**: 中（後方互換性喪失）

#### 4.1 C言語実装の削除
**削除対象ファイル部分**:
```c
// voicevox.c から削除される関数群
PHP_FUNCTION(voicevox_initialize)      // 行142-202
PHP_FUNCTION(voicevox_finalize)        // 行203-226
PHP_FUNCTION(voicevox_get_version)     // 行227-249
PHP_FUNCTION(voicevox_tts)             // 行250-297
PHP_FUNCTION(voicevox_audio_query)     // 行298-345
PHP_FUNCTION(voicevox_synthesis)       // 行346-396
PHP_FUNCTION(voicevox_is_initialized)  // 行397-407
```

**削除対象構造体**:
```c
// 関数テーブルエントリの削除
const zend_function_entry voicevox_functions[] = {
    // これらのエントリを削除
    PHP_FE(voicevox_initialize, arginfo_voicevox_initialize)
    PHP_FE(voicevox_finalize, arginfo_voicevox_finalize)
    // ... 他の手続き型関数エントリ
};
```

#### 4.2 ヘッダーファイルの削除
**削除対象 (php_voicevox.h)**:
```c
// 関数宣言の削除（行22-28）
PHP_FUNCTION(voicevox_initialize);
PHP_FUNCTION(voicevox_finalize);
PHP_FUNCTION(voicevox_get_version);
PHP_FUNCTION(voicevox_tts);
PHP_FUNCTION(voicevox_audio_query);
PHP_FUNCTION(voicevox_synthesis);
PHP_FUNCTION(voicevox_is_initialized);
```

#### 4.3 引数情報構造体の削除
**削除対象**:
```c
// 全ての arginfo_voicevox_* 構造体
ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_initialize, ...)
ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_finalize, ...)
// ... 他の arginfo 構造体
```

### Phase 5: 最終清掃（削除直後）
**期間**: 半日  
**リスク**: 極低

#### 5.1 コードの最適化
- **削除**: 使用されなくなった変数・定数
- **削除**: 不要なインクルード
- **最適化**: メモリ使用量の削減

#### 5.2 テストスイートの最終調整
- **削除**: 手続き型API関連テスト
- **追加**: OOP API専用テスト
- **確認**: 全テストの正常実行

## 詳細な削除コード一覧

### 完全削除対象

#### 1. voicevox.c（約260行削除）
```c
// 行142-202: PHP_FUNCTION(voicevox_initialize) 全体
// 行203-226: PHP_FUNCTION(voicevox_finalize) 全体  
// 行227-249: PHP_FUNCTION(voicevox_get_version) 全体
// 行250-297: PHP_FUNCTION(voicevox_tts) 全体
// 行298-345: PHP_FUNCTION(voicevox_audio_query) 全体
// 行346-396: PHP_FUNCTION(voicevox_synthesis) 全体
// 行397-407: PHP_FUNCTION(voicevox_is_initialized) 全体

// 行39-56: 引数情報構造体（7個）
ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_initialize, ...)
ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_finalize, ...)
// ... 他の arginfo 構造体

// 関数テーブルから該当エントリを削除
const zend_function_entry voicevox_functions[] = {
    // 以下の7行を削除
    PHP_FE(voicevox_initialize, arginfo_voicevox_initialize)
    PHP_FE(voicevox_finalize, arginfo_voicevox_finalize)
    PHP_FE(voicevox_get_version, arginfo_voicevox_get_version)
    PHP_FE(voicevox_tts, arginfo_voicevox_tts)
    PHP_FE(voicevox_audio_query, arginfo_voicevox_audio_query)
    PHP_FE(voicevox_synthesis, arginfo_voicevox_synthesis)
    PHP_FE(voicevox_is_initialized, arginfo_voicevox_is_initialized)
    PHP_FE_END
};
```

#### 2. php_voicevox.h（7行削除）
```c
// 行22-28: 関数宣言の削除
PHP_FUNCTION(voicevox_initialize);
PHP_FUNCTION(voicevox_finalize);
PHP_FUNCTION(voicevox_get_version);
PHP_FUNCTION(voicevox_tts);
PHP_FUNCTION(voicevox_audio_query);
PHP_FUNCTION(voicevox_synthesis);
PHP_FUNCTION(voicevox_is_initialized);
```

### 保持される機能

以下は削除されず、OOP APIとして継続提供：

1. **コア機能**: 初期化、終了、TTS、AudioQuery、Synthesis
2. **エラーハンドリング**: VoicevoxException
3. **設定管理**: 全ての設定オプション
4. **メタデータ**: バージョン情報、話者情報
5. **メモリ管理**: 自動クリーンアップ機能

## リスク評価と対策

### 高リスク要因
**なし** - 本番コードは完全にOOP APIに移行済み

### 中リスク要因
1. **外部依存**: 他のプロジェクトが手続き型APIを使用している可能性
   - **対策**: 3ヶ月の非推奨期間で十分な移行時間を提供
   - **対策**: 詳細な移行ガイドの提供

### 低リスク要因
1. **テストの一時的な失敗**
   - **対策**: Phase 1でテストコードを事前に更新
   - **対策**: CI/CDパイプラインでの継続的テスト

## 削除による利益

### コードベースの改善
1. **保守性向上**: 重複実装の除去
2. **複雑度削減**: 単一APIパターンへの統一
3. **メモリ効率**: 不要な関数定義の削除
4. **セキュリティ**: 攻撃対象面の縮小

### 開発効率の向上
1. **新機能開発**: OOP APIのみに集中
2. **バグ修正**: 単一実装での修正
3. **テスト工数**: 重複テストの除去
4. **ドキュメント**: 単一APIのドキュメント

## バージョニング戦略

### セマンティックバージョニング
- **Phase 1-3**: パッチバージョン更新（0.1.1, 0.1.2, ...）
- **Phase 4**: メジャーバージョン更新（1.0.0）
- **理由**: 後方互換性を破る変更のため

### ブランチ戦略
- **main**: Phase 1-3の変更
- **legacy-support**: 手続き型API保持版（保守のみ）
- **v1.0**: Phase 4以降の新アーキテクチャ

## 実装チェックリスト

### Phase 1: 準備
- [ ] テストコードの手続き型API呼び出しを置換
- [ ] ドキュメントの更新
- [ ] CI/CDパイプラインでのテスト確認

### Phase 2: 非推奨化強化
- [ ] 警告メッセージの詳細化
- [ ] 移行ガイドの作成
- [ ] 外部通知（必要に応じて）

### Phase 3: テスト調整
- [ ] 非推奨警告テストの無効化準備
- [ ] 新しいテストスイートの作成
- [ ] パフォーマンステストの更新

### Phase 4: 実装削除
- [ ] C言語実装の削除（voicevox.c）
- [ ] ヘッダーファイルの更新（php_voicevox.h）
- [ ] 関数テーブルの更新
- [ ] 引数情報構造体の削除
- [ ] コンパイルテストの実行

### Phase 5: 最終清掃
- [ ] 不要なコードの削除
- [ ] メモリ使用量の確認
- [ ] 全テストスイートの実行
- [ ] ドキュメントの最終更新
- [ ] リリースノートの作成

## 完了条件

1. **機能的完了**: OOP APIのみで全機能が利用可能
2. **テスト完了**: 全テストが手続き型API依存なしで実行可能
3. **ドキュメント完了**: 手続き型APIへの言及を全て削除
4. **パフォーマンス確認**: 削除による性能向上の確認
5. **後方互換性**: 意図的な破壊的変更として明確に文書化

---

**最終更新**: 2025-06-29  
**責任者**: VOICEVOX Extension Development Team  
**次回レビュー**: Phase 1完了後