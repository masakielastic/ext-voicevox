#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_voicevox.h"
#include "php_voicevox_oop.h"
#include "zend_exceptions.h"
#include <dlfcn.h>

// クラスエントリ
zend_class_entry *voicevox_engine_ce;
zend_class_entry *voicevox_exception_ce;

// シングルトンインスタンス
static zval singleton_instance;
static bool singleton_initialized = false;

// オブジェクトハンドラー
static zend_object_handlers voicevox_engine_object_handlers;

// 引数情報
ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_engine_getInstance, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_engine_initialize, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, lib_path, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, dict_path, IS_STRING, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_engine_isInitialized, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_engine_getVersion, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_engine_tts, 0, 0, 2)
    ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, speaker_id, IS_LONG, 0)
    ZEND_ARG_TYPE_INFO(0, kana, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_engine_audioQuery, 0, 0, 2)
    ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, speaker_id, IS_LONG, 0)
    ZEND_ARG_TYPE_INFO(0, kana, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_engine_synthesis, 0, 0, 2)
    ZEND_ARG_TYPE_INFO(0, audio_query, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, speaker_id, IS_LONG, 0)
    ZEND_ARG_TYPE_INFO(0, enable_upspeak, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_engine_finalize, 0, 0, 0)
ZEND_END_ARG_INFO()

// メソッドテーブル
const zend_function_entry voicevox_engine_methods[] = {
    PHP_ME(VoicevoxEngine, getInstance, arginfo_voicevox_engine_getInstance, ZEND_ACC_PUBLIC | ZEND_ACC_STATIC)
    PHP_ME(VoicevoxEngine, initialize, arginfo_voicevox_engine_initialize, ZEND_ACC_PUBLIC)
    PHP_ME(VoicevoxEngine, isInitialized, arginfo_voicevox_engine_isInitialized, ZEND_ACC_PUBLIC)
    PHP_ME(VoicevoxEngine, getVersion, arginfo_voicevox_engine_getVersion, ZEND_ACC_PUBLIC)
    PHP_ME(VoicevoxEngine, tts, arginfo_voicevox_engine_tts, ZEND_ACC_PUBLIC)
    PHP_ME(VoicevoxEngine, audioQuery, arginfo_voicevox_engine_audioQuery, ZEND_ACC_PUBLIC)
    PHP_ME(VoicevoxEngine, synthesis, arginfo_voicevox_engine_synthesis, ZEND_ACC_PUBLIC)
    PHP_ME(VoicevoxEngine, finalize, arginfo_voicevox_engine_finalize, ZEND_ACC_PUBLIC)
    PHP_FE_END
};

// オブジェクト作成関数
zend_object *voicevox_engine_create_object(zend_class_entry *ce)
{
    voicevox_engine_object *intern = zend_object_alloc(sizeof(voicevox_engine_object), ce);
    
    intern->lib_handle = NULL;
    intern->is_initialized = false;
    intern->library_path = NULL;
    intern->dict_path = NULL;
    
    zend_object_std_init(&intern->std, ce);
    object_properties_init(&intern->std, ce);
    intern->std.handlers = &voicevox_engine_object_handlers;
    
    return &intern->std;
}

// オブジェクト解放関数
void voicevox_engine_free_object(zend_object *object)
{
    voicevox_engine_object *intern = voicevox_engine_from_obj(object);
    
    if (intern->library_path) {
        efree(intern->library_path);
    }
    if (intern->dict_path) {
        efree(intern->dict_path);
    }
    
    zend_object_std_dtor(&intern->std);
}

// シングルトンパターンの実装
PHP_METHOD(VoicevoxEngine, getInstance)
{
    if (!singleton_initialized) {
        object_init_ex(&singleton_instance, voicevox_engine_ce);
        singleton_initialized = true;
    }
    
    RETURN_ZVAL(&singleton_instance, 1, 0);
}

// 初期化メソッド
PHP_METHOD(VoicevoxEngine, initialize)
{
    char *lib_path = NULL, *dict_path = NULL;
    size_t lib_path_len = 0, dict_path_len = 0;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS(), "s|s", &lib_path, &lib_path_len, &dict_path, &dict_path_len) == FAILURE) {
        RETURN_FALSE;
    }
    
    voicevox_engine_object *obj = Z_VOICEVOX_ENGINE_P(ZEND_THIS);
    
    // テスト互換性: 重複初期化で例外ではなくfalseを返す
    if (obj->is_initialized) {
        RETURN_FALSE;
    }
    
    // 既存のvoicevox_initialize実装を利用
    zval args[4];
    zval func_name;
    zval retval;
    
    ZVAL_STRING(&args[0], lib_path);
    if (dict_path) {
        ZVAL_STRING(&args[1], dict_path);
    } else {
        ZVAL_NULL(&args[1]);
    }
    ZVAL_LONG(&args[2], 0); // cpu_threads
    ZVAL_FALSE(&args[3]); // load_all
    
    ZVAL_STRING(&func_name, "voicevox_initialize");
    
    // 既存関数を呼び出し
    if (call_user_function(EG(function_table), NULL, &func_name, &retval, dict_path ? 2 : 1, args) == SUCCESS) {
        bool result = Z_TYPE(retval) == IS_TRUE;
        obj->is_initialized = result;
        
        if (result) {
            obj->library_path = estrdup(lib_path);
            if (dict_path) {
                obj->dict_path = estrdup(dict_path);
            }
        }
        
        zval_ptr_dtor(&args[0]);
        if (dict_path) {
            zval_ptr_dtor(&args[1]);
        }
        zval_ptr_dtor(&func_name);
        zval_ptr_dtor(&retval);
        
        RETURN_BOOL(result);
    }
    
    zval_ptr_dtor(&args[0]);
    if (dict_path) {
        zval_ptr_dtor(&args[1]);
    }
    zval_ptr_dtor(&func_name);
    
    RETURN_FALSE;
}

// 初期化状態確認メソッド
PHP_METHOD(VoicevoxEngine, isInitialized)
{
    voicevox_engine_object *obj = Z_VOICEVOX_ENGINE_P(ZEND_THIS);
    RETURN_BOOL(obj->is_initialized);
}

// バージョン取得メソッド
PHP_METHOD(VoicevoxEngine, getVersion)
{
    zval func_name;
    zval retval;
    
    ZVAL_STRING(&func_name, "voicevox_get_version");
    
    if (call_user_function(EG(function_table), NULL, &func_name, &retval, 0, NULL) == SUCCESS) {
        zval_ptr_dtor(&func_name);
        RETURN_ZVAL(&retval, 1, 1);
    }
    
    zval_ptr_dtor(&func_name);
    RETURN_STRING("");
}

// TTS メソッド
PHP_METHOD(VoicevoxEngine, tts)
{
    char *text;
    size_t text_len;
    zend_long speaker_id;
    bool kana = false;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS(), "sl|b", &text, &text_len, &speaker_id, &kana) == FAILURE) {
        RETURN_FALSE;
    }
    
    voicevox_engine_object *obj = Z_VOICEVOX_ENGINE_P(ZEND_THIS);
    
    // テスト互換性: 未初期化時は例外ではなくfalseを返す
    if (!obj->is_initialized) {
        RETURN_FALSE;
    }
    
    // テスト互換性: 空文字列チェック
    if (text_len == 0) {
        RETURN_FALSE;
    }
    
    zval args[3];
    zval func_name;
    zval retval;
    
    ZVAL_STRING(&args[0], text);
    ZVAL_LONG(&args[1], speaker_id);
    ZVAL_BOOL(&args[2], kana);
    ZVAL_STRING(&func_name, "voicevox_tts");
    
    if (call_user_function(EG(function_table), NULL, &func_name, &retval, 3, args) == SUCCESS) {
        zval_ptr_dtor(&args[0]);
        zval_ptr_dtor(&func_name);
        RETURN_ZVAL(&retval, 1, 1);
    }
    
    zval_ptr_dtor(&args[0]);
    zval_ptr_dtor(&func_name);
    RETURN_FALSE;
}

// Audio Query メソッド
PHP_METHOD(VoicevoxEngine, audioQuery)
{
    char *text;
    size_t text_len;
    zend_long speaker_id;
    bool kana = false;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS(), "sl|b", &text, &text_len, &speaker_id, &kana) == FAILURE) {
        RETURN_FALSE;
    }
    
    voicevox_engine_object *obj = Z_VOICEVOX_ENGINE_P(ZEND_THIS);
    
    // テスト互換性: 未初期化時は例外ではなくfalseを返す
    if (!obj->is_initialized) {
        RETURN_FALSE;
    }
    
    // テスト互換性: 空文字列チェック
    if (text_len == 0) {
        RETURN_FALSE;
    }
    
    zval args[3];
    zval func_name;
    zval retval;
    
    ZVAL_STRING(&args[0], text);
    ZVAL_LONG(&args[1], speaker_id);
    ZVAL_BOOL(&args[2], kana);
    ZVAL_STRING(&func_name, "voicevox_audio_query");
    
    if (call_user_function(EG(function_table), NULL, &func_name, &retval, 3, args) == SUCCESS) {
        zval_ptr_dtor(&args[0]);
        zval_ptr_dtor(&func_name);
        RETURN_ZVAL(&retval, 1, 1);
    }
    
    zval_ptr_dtor(&args[0]);
    zval_ptr_dtor(&func_name);
    RETURN_FALSE;
}

// Synthesis メソッド
PHP_METHOD(VoicevoxEngine, synthesis)
{
    char *audio_query;
    size_t audio_query_len;
    zend_long speaker_id;
    bool enable_upspeak = true;
    
    if (zend_parse_parameters(ZEND_NUM_ARGS(), "sl|b", &audio_query, &audio_query_len, &speaker_id, &enable_upspeak) == FAILURE) {
        RETURN_FALSE;
    }
    
    voicevox_engine_object *obj = Z_VOICEVOX_ENGINE_P(ZEND_THIS);
    
    // テスト互換性: 未初期化時は例外ではなくfalseを返す
    if (!obj->is_initialized) {
        RETURN_FALSE;
    }
    
    // テスト互換性: 空AudioQuery文字列チェック
    if (audio_query_len == 0) {
        RETURN_FALSE;
    }
    
    zval args[3];
    zval func_name;
    zval retval;
    
    ZVAL_STRING(&args[0], audio_query);
    ZVAL_LONG(&args[1], speaker_id);
    ZVAL_BOOL(&args[2], enable_upspeak);
    ZVAL_STRING(&func_name, "voicevox_synthesis");
    
    if (call_user_function(EG(function_table), NULL, &func_name, &retval, 3, args) == SUCCESS) {
        zval_ptr_dtor(&args[0]);
        zval_ptr_dtor(&func_name);
        RETURN_ZVAL(&retval, 1, 1);
    }
    
    zval_ptr_dtor(&args[0]);
    zval_ptr_dtor(&func_name);
    RETURN_FALSE;
}

// 終了処理メソッド
PHP_METHOD(VoicevoxEngine, finalize)
{
    voicevox_engine_object *obj = Z_VOICEVOX_ENGINE_P(ZEND_THIS);
    
    if (!obj->is_initialized) {
        RETURN_TRUE;
    }
    
    zval func_name;
    zval retval;
    
    ZVAL_STRING(&func_name, "voicevox_finalize");
    
    if (call_user_function(EG(function_table), NULL, &func_name, &retval, 0, NULL) == SUCCESS) {
        bool result = Z_TYPE(retval) == IS_TRUE;
        if (result) {
            obj->is_initialized = false;
        }
        zval_ptr_dtor(&func_name);
        zval_ptr_dtor(&retval);
        RETURN_BOOL(result);
    }
    
    zval_ptr_dtor(&func_name);
    RETURN_FALSE;
}

// OOP初期化関数
void voicevox_oop_init(INIT_FUNC_ARGS)
{
    zend_class_entry ce;
    
    // Engine クラス登録
    INIT_NS_CLASS_ENTRY(ce, "Voicevox", "Engine", voicevox_engine_methods);
    voicevox_engine_ce = zend_register_internal_class(&ce);
    voicevox_engine_ce->create_object = voicevox_engine_create_object;
    
    // オブジェクトハンドラー設定
    memcpy(&voicevox_engine_object_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    voicevox_engine_object_handlers.free_obj = voicevox_engine_free_object;
    voicevox_engine_object_handlers.offset = XtOffsetOf(voicevox_engine_object, std);
    
    // Exception クラス登録
    INIT_NS_CLASS_ENTRY(ce, "Voicevox\\Exception", "VoicevoxException", NULL);
    voicevox_exception_ce = zend_register_internal_class_ex(&ce, zend_ce_exception);
}