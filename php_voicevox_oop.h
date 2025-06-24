#ifndef PHP_VOICEVOX_OOP_H
#define PHP_VOICEVOX_OOP_H

#include "php.h"
#include "php_voicevox.h"

// クラスエントリ
extern zend_class_entry *voicevox_engine_ce;
extern zend_class_entry *voicevox_exception_ce;

// オブジェクト構造体
typedef struct _voicevox_engine_object {
    void *lib_handle;
    bool is_initialized;
    char *library_path;
    char *dict_path;
    zend_object std;
} voicevox_engine_object;

// マクロ
#define Z_VOICEVOX_ENGINE_P(zv) voicevox_engine_from_obj(Z_OBJ_P((zv)))

// 関数宣言
static inline voicevox_engine_object *voicevox_engine_from_obj(zend_object *obj) {
    return (voicevox_engine_object*)((char*)(obj) - XtOffsetOf(voicevox_engine_object, std));
}

zend_object *voicevox_engine_create_object(zend_class_entry *ce);
void voicevox_engine_free_object(zend_object *object);

// メソッド宣言
PHP_METHOD(VoicevoxEngine, getInstance);
PHP_METHOD(VoicevoxEngine, initialize);
PHP_METHOD(VoicevoxEngine, isInitialized);
PHP_METHOD(VoicevoxEngine, getVersion);
PHP_METHOD(VoicevoxEngine, tts);
PHP_METHOD(VoicevoxEngine, audioQuery);
PHP_METHOD(VoicevoxEngine, synthesis);
PHP_METHOD(VoicevoxEngine, finalize);

// 初期化関数
void voicevox_oop_init(INIT_FUNC_ARGS);

#endif /* PHP_VOICEVOX_OOP_H */