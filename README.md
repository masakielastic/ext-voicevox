# VOICEVOX PHP Extension

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

## テスト実行

```bash
make test
```