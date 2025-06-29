// 最終版VOICEVOX PHP拡張機能
// シンプル版（シグナル処理なし、ラッパースクリプトで制御）
#define COMPILE_DL_VOICEVOX 1

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_voicevox.h"
#include <dlfcn.h>
#include <stdint.h>
#include <stdbool.h>
#include <stdlib.h>

ZEND_DECLARE_MODULE_GLOBALS(voicevox)

// 関数ポインタ
static VoicevoxResultCode (*voicevox_initialize_func)(VoicevoxInitializeOptions);
static VoicevoxResultCode (*voicevox_tts_func)(const char*, uint32_t, VoicevoxTtsOptions, uintptr_t*, uint8_t**);
static VoicevoxResultCode (*voicevox_audio_query_func)(const char*, uint32_t, VoicevoxAudioQueryOptions, char**);
static VoicevoxResultCode (*voicevox_synthesis_func)(const char*, uint32_t, VoicevoxSynthesisOptions, uintptr_t*, uint8_t**);
static void (*voicevox_finalize_func)(void);
static void (*voicevox_wav_free_func)(uint8_t*);
static void (*voicevox_json_free_func)(char*);
static const char* (*voicevox_get_version_func)(void);

// 静的フラグ
static volatile bool voicevox_shutdown_called = false;
static volatile bool atexit_registered = false;

// atexit用のクリーンアップ関数
static void voicevox_atexit_cleanup(void) {
    if (VOICEVOX_G(is_initialized) && voicevox_finalize_func && !voicevox_shutdown_called) {
        voicevox_finalize_func();
        voicevox_shutdown_called = true;
    }
}

// 引数情報の定義
ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_initialize, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, lib_path, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, dict_path, IS_STRING, 1)
    ZEND_ARG_TYPE_INFO(0, cpu_threads, IS_LONG, 0)
    ZEND_ARG_TYPE_INFO(0, load_all, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_finalize, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_get_version, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_tts, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, speaker_id, IS_LONG, 0)
    ZEND_ARG_TYPE_INFO(0, kana, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_audio_query, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, text, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, speaker_id, IS_LONG, 0)
    ZEND_ARG_TYPE_INFO(0, kana, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_synthesis, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, audio_query_json, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, speaker_id, IS_LONG, 0)
    ZEND_ARG_TYPE_INFO(0, enable_upspeak, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_voicevox_is_initialized, 0, 0, 0)
ZEND_END_ARG_INFO()

// 関数テーブル
const zend_function_entry voicevox_functions[] = {
    PHP_FE(voicevox_initialize, arginfo_voicevox_initialize)
    PHP_FE(voicevox_finalize, arginfo_voicevox_finalize)
    PHP_FE(voicevox_get_version, arginfo_voicevox_get_version)
    PHP_FE(voicevox_tts, arginfo_voicevox_tts)
    PHP_FE(voicevox_audio_query, arginfo_voicevox_audio_query)
    PHP_FE(voicevox_synthesis, arginfo_voicevox_synthesis)
    PHP_FE(voicevox_is_initialized, arginfo_voicevox_is_initialized)
    PHP_FE_END
};

// モジュール定義
zend_module_entry voicevox_module_entry = {
    STANDARD_MODULE_HEADER,
    "voicevox",
    voicevox_functions,
    PHP_MINIT(voicevox),
    PHP_MSHUTDOWN(voicevox),
    NULL,
    NULL,
    PHP_MINFO(voicevox),
    PHP_VOICEVOX_VERSION,
    STANDARD_MODULE_PROPERTIES
};

// グローバル変数初期化関数
static void php_voicevox_init_globals(zend_voicevox_globals *voicevox_globals)
{
    voicevox_globals->lib_handle = NULL;
    voicevox_globals->is_initialized = false;
    voicevox_globals->library_path = NULL;
    voicevox_globals->dict_path = NULL;
}

// ライブラリロード
static bool load_voicevox_library(const char* lib_path)
{
    if (VOICEVOX_G(lib_handle)) {
        return true;
    }

    VOICEVOX_G(lib_handle) = dlopen(lib_path, RTLD_LAZY | RTLD_LOCAL);
    if (!VOICEVOX_G(lib_handle)) {
        php_error_docref(NULL, E_WARNING, "Failed to load VOICEVOX library: %s", dlerror());
        return false;
    }

    // 関数ポインタを取得
    voicevox_initialize_func = dlsym(VOICEVOX_G(lib_handle), "voicevox_initialize");
    voicevox_tts_func = dlsym(VOICEVOX_G(lib_handle), "voicevox_tts");
    voicevox_audio_query_func = dlsym(VOICEVOX_G(lib_handle), "voicevox_audio_query");
    voicevox_synthesis_func = dlsym(VOICEVOX_G(lib_handle), "voicevox_synthesis");
    voicevox_finalize_func = dlsym(VOICEVOX_G(lib_handle), "voicevox_finalize");
    voicevox_wav_free_func = dlsym(VOICEVOX_G(lib_handle), "voicevox_wav_free");
    voicevox_json_free_func = dlsym(VOICEVOX_G(lib_handle), "voicevox_audio_query_json_free");
    voicevox_get_version_func = dlsym(VOICEVOX_G(lib_handle), "voicevox_get_version");

    if (!voicevox_initialize_func || !voicevox_tts_func || !voicevox_finalize_func) {
        php_error_docref(NULL, E_WARNING, "Failed to get required VOICEVOX functions");
        dlclose(VOICEVOX_G(lib_handle));
        VOICEVOX_G(lib_handle) = NULL;
        return false;
    }

    return true;
}

// PHP関数: voicevox_initialize
PHP_FUNCTION(voicevox_initialize)
{
    // 非推奨警告を発出
    php_error_docref(NULL, E_DEPRECATED, 
        "voicevox_initialize() is deprecated and will be REMOVED in v1.0.0 (scheduled: 2025-09-29). "
        "Use Voicevox\\Engine::getInstance()->initialize() instead. "
        "See PROCEDURAL_API_REMOVAL_PLAN.md for migration guide.");
    
    char *lib_path = NULL, *dict_path = NULL;
    size_t lib_path_len = 0, dict_path_len = 0;
    zend_long cpu_threads = 0;
    zend_bool load_all = true;

    if (zend_parse_parameters(ZEND_NUM_ARGS(), "s|slb", &lib_path, &lib_path_len, &dict_path, &dict_path_len, &cpu_threads, &load_all) == FAILURE) {
        RETURN_FALSE;
    }

    if (VOICEVOX_G(is_initialized)) {
        php_error_docref(NULL, E_WARNING, "VOICEVOX is already initialized");
        RETURN_FALSE;
    }

    if (!load_voicevox_library(lib_path)) {
        RETURN_FALSE;
    }

    VoicevoxInitializeOptions opts = {
        .acceleration_mode = 1,  // CPU-only mode
        .cpu_num_threads = (uint16_t)cpu_threads,
        .load_all_models = load_all,
        .open_jtalk_dict_dir = dict_path
    };

    VoicevoxResultCode result = voicevox_initialize_func(opts);
    if (result == 0) {
        VOICEVOX_G(is_initialized) = true;
        
        // atexit関数を一度だけ登録
        if (!atexit_registered) {
            atexit(voicevox_atexit_cleanup);
            atexit_registered = true;
        }
        
        if (VOICEVOX_G(library_path)) {
            efree(VOICEVOX_G(library_path));
        }
        VOICEVOX_G(library_path) = estrdup(lib_path);
        
        if (dict_path) {
            if (VOICEVOX_G(dict_path)) {
                efree(VOICEVOX_G(dict_path));
            }
            VOICEVOX_G(dict_path) = estrdup(dict_path);
        }
        
        RETURN_TRUE;
    } else {
        php_error_docref(NULL, E_WARNING, "VOICEVOX initialization failed with code: %u", result);
        RETURN_FALSE;
    }
}

// PHP関数: voicevox_finalize
PHP_FUNCTION(voicevox_finalize)
{
    // 非推奨警告を発出
    php_error_docref(NULL, E_DEPRECATED, 
        "voicevox_finalize() is deprecated and will be REMOVED in v1.0.0 (scheduled: 2025-09-29). "
        "Use Voicevox\\Engine::getInstance()->finalize() instead. "
        "See PROCEDURAL_API_REMOVAL_PLAN.md for migration guide.");
    if (zend_parse_parameters_none() == FAILURE) {
        RETURN_FALSE;
    }

    if (!VOICEVOX_G(is_initialized)) {
        php_error_docref(NULL, E_WARNING, "VOICEVOX is not initialized");
        RETURN_FALSE;
    }

    if (voicevox_finalize_func && !voicevox_shutdown_called) {
        voicevox_finalize_func();
        voicevox_shutdown_called = true;
    }

    VOICEVOX_G(is_initialized) = false;
    RETURN_TRUE;
}

// PHP関数: voicevox_get_version
PHP_FUNCTION(voicevox_get_version)
{
    // 非推奨警告を発出
    php_error_docref(NULL, E_DEPRECATED, 
        "voicevox_get_version() is deprecated and will be REMOVED in v1.0.0 (scheduled: 2025-09-29). "
        "Use Voicevox\\Engine::getInstance()->getVersion() instead. "
        "See PROCEDURAL_API_REMOVAL_PLAN.md for migration guide.");
    if (zend_parse_parameters_none() == FAILURE) {
        RETURN_FALSE;
    }

    if (!VOICEVOX_G(lib_handle)) {
        php_error_docref(NULL, E_WARNING, "VOICEVOX library is not loaded");
        RETURN_FALSE;
    }

    if (voicevox_get_version_func) {
        const char* version = voicevox_get_version_func();
        RETURN_STRING(version);
    }

    RETURN_NULL();
}

// PHP関数: voicevox_tts
PHP_FUNCTION(voicevox_tts)
{
    // 非推奨警告を発出
    php_error_docref(NULL, E_DEPRECATED, 
        "voicevox_tts() is deprecated and will be REMOVED in v1.0.0 (scheduled: 2025-09-29). "
        "Use Voicevox\\Engine::getInstance()->tts() instead. "
        "See PROCEDURAL_API_REMOVAL_PLAN.md for migration guide.");
    char *text;
    size_t text_len;
    zend_long speaker_id = 3;
    zend_bool kana = false;

    if (zend_parse_parameters(ZEND_NUM_ARGS(), "s|lb", &text, &text_len, &speaker_id, &kana) == FAILURE) {
        RETURN_FALSE;
    }

    if (!VOICEVOX_G(is_initialized)) {
        php_error_docref(NULL, E_WARNING, "VOICEVOX is not initialized");
        RETURN_FALSE;
    }

    VoicevoxTtsOptions opts = { .kana = kana };
    uint8_t* wav_data = NULL;
    uintptr_t wav_length = 0;

    VoicevoxResultCode result = voicevox_tts_func(text, (uint32_t)speaker_id, opts, &wav_length, &wav_data);

    if (result == 0 && wav_data != NULL && wav_length > 0) {
        // zend_stringを直接作成（コピーを避ける）
        zend_string *wav_string = zend_string_alloc(wav_length, 0);
        memcpy(ZSTR_VAL(wav_string), wav_data, wav_length);
        ZSTR_VAL(wav_string)[wav_length] = '\0';
        
        // VOICEVOXメモリを即座に解放
        if (voicevox_wav_free_func) {
            voicevox_wav_free_func(wav_data);
        }
        
        RETURN_STR(wav_string);
    } else {
        // 失敗時もメモリを解放
        if (wav_data && voicevox_wav_free_func) {
            voicevox_wav_free_func(wav_data);
        }
        php_error_docref(NULL, E_WARNING, "TTS failed with code: %u", result);
        RETURN_FALSE;
    }
}

// PHP関数: voicevox_audio_query
PHP_FUNCTION(voicevox_audio_query)
{
    // 非推奨警告を発出
    php_error_docref(NULL, E_DEPRECATED, 
        "voicevox_audio_query() is deprecated and will be REMOVED in v1.0.0 (scheduled: 2025-09-29). "
        "Use Voicevox\\Engine::getInstance()->audioQuery() instead. "
        "See PROCEDURAL_API_REMOVAL_PLAN.md for migration guide.");
    char *text;
    size_t text_len;
    zend_long speaker_id = 3;
    zend_bool kana = false;

    if (zend_parse_parameters(ZEND_NUM_ARGS(), "s|lb", &text, &text_len, &speaker_id, &kana) == FAILURE) {
        RETURN_FALSE;
    }

    if (!VOICEVOX_G(is_initialized)) {
        php_error_docref(NULL, E_WARNING, "VOICEVOX is not initialized");
        RETURN_FALSE;
    }

    if (!voicevox_audio_query_func) {
        php_error_docref(NULL, E_WARNING, "voicevox_audio_query function is not available");
        RETURN_FALSE;
    }

    VoicevoxAudioQueryOptions opts = { .kana = kana };
    char* audio_query_json = NULL;

    VoicevoxResultCode result = voicevox_audio_query_func(text, (uint32_t)speaker_id, opts, &audio_query_json);

    if (result == 0 && audio_query_json != NULL) {
        zend_string *json_string = zend_string_init(audio_query_json, strlen(audio_query_json), 0);
        
        // VOICEVOXメモリを即座に解放
        if (voicevox_json_free_func) {
            voicevox_json_free_func(audio_query_json);
        }
        
        RETURN_STR(json_string);
    } else {
        if (audio_query_json && voicevox_json_free_func) {
            voicevox_json_free_func(audio_query_json);
        }
        php_error_docref(NULL, E_WARNING, "Audio query failed with code: %u", result);
        RETURN_FALSE;
    }
}

// PHP関数: voicevox_synthesis
PHP_FUNCTION(voicevox_synthesis)
{
    // 非推奨警告を発出
    php_error_docref(NULL, E_DEPRECATED, 
        "voicevox_synthesis() is deprecated and will be REMOVED in v1.0.0 (scheduled: 2025-09-29). "
        "Use Voicevox\\Engine::getInstance()->synthesis() instead. "
        "See PROCEDURAL_API_REMOVAL_PLAN.md for migration guide.");
    char *audio_query_json;
    size_t json_len;
    zend_long speaker_id = 3;
    zend_bool enable_upspeak = true;

    if (zend_parse_parameters(ZEND_NUM_ARGS(), "s|lb", &audio_query_json, &json_len, &speaker_id, &enable_upspeak) == FAILURE) {
        RETURN_FALSE;
    }

    if (!VOICEVOX_G(is_initialized)) {
        php_error_docref(NULL, E_WARNING, "VOICEVOX is not initialized");
        RETURN_FALSE;
    }

    if (!voicevox_synthesis_func) {
        php_error_docref(NULL, E_WARNING, "voicevox_synthesis function is not available");
        RETURN_FALSE;
    }

    VoicevoxSynthesisOptions opts = { .enable_interrogative_upspeak = enable_upspeak };
    uint8_t* wav_data = NULL;
    uintptr_t wav_length = 0;

    VoicevoxResultCode result = voicevox_synthesis_func(audio_query_json, (uint32_t)speaker_id, opts, &wav_length, &wav_data);

    if (result == 0 && wav_data != NULL && wav_length > 0) {
        zend_string *wav_string = zend_string_alloc(wav_length, 0);
        memcpy(ZSTR_VAL(wav_string), wav_data, wav_length);
        ZSTR_VAL(wav_string)[wav_length] = '\0';
        
        // VOICEVOXメモリを即座に解放
        if (voicevox_wav_free_func) {
            voicevox_wav_free_func(wav_data);
        }
        
        RETURN_STR(wav_string);
    } else {
        if (wav_data && voicevox_wav_free_func) {
            voicevox_wav_free_func(wav_data);
        }
        php_error_docref(NULL, E_WARNING, "Synthesis failed with code: %u", result);
        RETURN_FALSE;
    }
}

// PHP関数: voicevox_is_initialized
PHP_FUNCTION(voicevox_is_initialized)
{
    // 非推奨警告を発出
    php_error_docref(NULL, E_DEPRECATED, 
        "voicevox_is_initialized() is deprecated and will be REMOVED in v1.0.0 (scheduled: 2025-09-29). "
        "Use Voicevox\\Engine::getInstance()->isInitialized() instead. "
        "See PROCEDURAL_API_REMOVAL_PLAN.md for migration guide.");
    if (zend_parse_parameters_none() == FAILURE) {
        RETURN_FALSE;
    }

    RETURN_BOOL(VOICEVOX_G(is_initialized));
}

// モジュール初期化
PHP_MINIT_FUNCTION(voicevox)
{
    ZEND_INIT_MODULE_GLOBALS(voicevox, php_voicevox_init_globals, NULL);
    
    // 定数定義
    REGISTER_LONG_CONSTANT("VOICEVOX_RESULT_OK", 0, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("VOICEVOX_ACCELERATION_MODE_AUTO", 0, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("VOICEVOX_ACCELERATION_MODE_CPU", 1, CONST_CS | CONST_PERSISTENT);
    REGISTER_LONG_CONSTANT("VOICEVOX_ACCELERATION_MODE_GPU", 2, CONST_CS | CONST_PERSISTENT);

    // フラグ初期化
    voicevox_shutdown_called = false;
    atexit_registered = false;

    // OOP クラス初期化
    voicevox_oop_init(INIT_FUNC_ARGS_PASSTHRU);

    return SUCCESS;
}

// モジュール終了
PHP_MSHUTDOWN_FUNCTION(voicevox)
{
    // 安全なクリーンアップ
    if (VOICEVOX_G(is_initialized) && voicevox_finalize_func && !voicevox_shutdown_called) {
        voicevox_finalize_func();
        voicevox_shutdown_called = true;
    }
    
    VOICEVOX_G(is_initialized) = false;
    
    // パス文字列のクリーンアップ
    if (VOICEVOX_G(library_path)) {
        efree(VOICEVOX_G(library_path));
        VOICEVOX_G(library_path) = NULL;
    }
    
    if (VOICEVOX_G(dict_path)) {
        efree(VOICEVOX_G(dict_path));
        VOICEVOX_G(dict_path) = NULL;
    }
    
    // 関数ポインタをクリア
    voicevox_initialize_func = NULL;
    voicevox_tts_func = NULL;
    voicevox_audio_query_func = NULL;
    voicevox_synthesis_func = NULL;
    voicevox_finalize_func = NULL;
    voicevox_wav_free_func = NULL;
    voicevox_json_free_func = NULL;
    voicevox_get_version_func = NULL;
    
    // ライブラリハンドルは自動解放
    VOICEVOX_G(lib_handle) = NULL;

    return SUCCESS;
}

// モジュール情報
PHP_MINFO_FUNCTION(voicevox)
{
    php_info_print_table_start();
    php_info_print_table_header(2, "VOICEVOX Support", "enabled");
    php_info_print_table_row(2, "Extension Version", PHP_VOICEVOX_VERSION);
    php_info_print_table_row(2, "Status", VOICEVOX_G(is_initialized) ? "initialized" : "not initialized");
    php_info_print_table_row(2, "Signal Handling", "managed by wrapper script");
    
    if (VOICEVOX_G(library_path)) {
        php_info_print_table_row(2, "Library Path", VOICEVOX_G(library_path));
    }
    if (VOICEVOX_G(dict_path)) {
        php_info_print_table_row(2, "Dictionary Path", VOICEVOX_G(dict_path));
    }
    
    php_info_print_table_end();
}

// COMPILE_DL_VOICEVOXが定義されているので、これが実行される
#ifdef COMPILE_DL_VOICEVOX
ZEND_GET_MODULE(voicevox)
#endif
