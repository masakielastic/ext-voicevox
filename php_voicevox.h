#ifndef PHP_VOICEVOX_H
#define PHP_VOICEVOX_H

extern zend_module_entry voicevox_module_entry;
#define phpext_voicevox_ptr &voicevox_module_entry

#define PHP_VOICEVOX_VERSION "0.1.0"

#ifdef PHP_WIN32
#   define PHP_VOICEVOX_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#   define PHP_VOICEVOX_API __attribute__ ((visibility("default")))
#else
#   define PHP_VOICEVOX_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

// VOICEVOX関数宣言
PHP_FUNCTION(voicevox_initialize);
PHP_FUNCTION(voicevox_finalize);
PHP_FUNCTION(voicevox_get_version);
PHP_FUNCTION(voicevox_tts);
PHP_FUNCTION(voicevox_audio_query);
PHP_FUNCTION(voicevox_synthesis);
PHP_FUNCTION(voicevox_is_initialized);

// モジュール関数
PHP_MINIT_FUNCTION(voicevox);
PHP_MSHUTDOWN_FUNCTION(voicevox);
PHP_RINIT_FUNCTION(voicevox);
PHP_RSHUTDOWN_FUNCTION(voicevox);
PHP_MINFO_FUNCTION(voicevox);

// VOICEVOX型定義
typedef uint32_t VoicevoxResultCode;
typedef struct {
    uint32_t acceleration_mode;
    uint16_t cpu_num_threads;
    bool load_all_models;
    const char* open_jtalk_dict_dir;
} VoicevoxInitializeOptions;

typedef struct {
    bool kana;
} VoicevoxTtsOptions;

typedef struct {
    bool kana;
} VoicevoxAudioQueryOptions;

typedef struct {
    bool enable_interrogative_upspeak;
} VoicevoxSynthesisOptions;

ZEND_BEGIN_MODULE_GLOBALS(voicevox)
    void* lib_handle;
    bool is_initialized;
    char* library_path;
    char* dict_path;
ZEND_END_MODULE_GLOBALS(voicevox)

#ifdef ZTS
#define VOICEVOX_G(v) TSRMG(voicevox_globals_id, zend_voicevox_globals *, v)
#else
#define VOICEVOX_G(v) (voicevox_globals.v)
#endif

// 共有関数ポインタ宣言（OOP実装からアクセス可能）
extern VoicevoxResultCode (*voicevox_initialize_func)(VoicevoxInitializeOptions);
extern VoicevoxResultCode (*voicevox_tts_func)(const char*, uint32_t, VoicevoxTtsOptions, uintptr_t*, uint8_t**);
extern VoicevoxResultCode (*voicevox_audio_query_func)(const char*, uint32_t, VoicevoxAudioQueryOptions, char**);
extern VoicevoxResultCode (*voicevox_synthesis_func)(const char*, uint32_t, VoicevoxSynthesisOptions, uintptr_t*, uint8_t**);
extern void (*voicevox_finalize_func)(void);
extern void (*voicevox_wav_free_func)(uint8_t*);
extern void (*voicevox_json_free_func)(char*);
extern const char* (*voicevox_get_version_func)(void);

// OOP support
#include "php_voicevox_oop.h"

#endif /* PHP_VOICEVOX_H */
