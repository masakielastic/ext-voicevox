# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## VOICEVOX PHP Extension Overview

This is a PHP extension that provides text-to-speech functionality using the VOICEVOX engine. The extension offers both procedural and object-oriented APIs for maximum flexibility.

## Development Commands

### Building
```bash
make                    # Build extension with default settings
make clean             # Clean build artifacts
make test              # Run all tests
```

### Testing
```bash
# Run all tests
make test

# Run specific tests
php -d extension=modules/voicevox.so tests/test_enhanced_oop.php
php -d extension=modules/voicevox.so tests/test_with_env.php
./run_tests.sh                                              # Comprehensive test suite

# Run PHPT tests only
php -d extension=modules/voicevox.so run-tests.php tests/
```

### Development Server
```bash
# Start OOP API server (RECOMMENDED)
php -d extension=modules/voicevox.so -S localhost:8080 demo/voicevox_server.php

# Start alternate OOP server
php -d extension=modules/voicevox.so -S localhost:8081 demo/new_server.php
```

## Architecture

### OOP-First Design
The extension primarily uses Object-Oriented API with legacy procedural support:

1. **Object-Oriented API** (`voicevox_oop.c`): **RECOMMENDED** - Modern, exception-based interface
   - `\Voicevox\Engine::getInstance()->initialize()`, `->tts()`, `->finalize()`, etc.
   - Exception handling with `\Voicevox\Exception\VoicevoxException`
   
2. **Procedural API** (`voicevox.c`): **DEPRECATED - SCHEDULED FOR REMOVAL**
   - ⚠️ **Removal Timeline**: Version 1.0.0 (3 months from 2025-06-29)
   - Legacy interface maintained for backward compatibility only
   - All new development should use OOP API
   - See `PROCEDURAL_API_REMOVAL_PLAN.md` for migration details

### Key Components
- **Dynamic Library Loading**: Uses `dlopen()` to load VOICEVOX shared library at runtime
- **Memory Management**: Immediate cleanup with multiple fallback mechanisms
- **Error Handling**: Comprehensive error reporting and graceful degradation
- **Thread Safety**: Proper TSRM integration for multi-threaded environments

### File Structure
- `voicevox.c` - Main procedural API implementation
- `voicevox_oop.c` - Object-oriented wrapper API
- `voicevox_compat.c` - Compatibility layer (minimal)
- `php_voicevox.h` - Main header with type definitions
- `php_voicevox_oop.h` - OOP-specific definitions

## Required Environment

### Environment Variables
Before using the extension, set these environment variables:

```bash
export VOICEVOX_LIB_PATH="/path/to/libvoicevox_core.so"
export VOICEVOX_DICT_PATH="/path/to/open_jtalk_dic_utf_8-1.11"
```

### Usage Patterns

#### Object-oriented Usage (RECOMMENDED)
```php
use Voicevox\Engine;
use Voicevox\Exception\VoicevoxException;

try {
    $engine = Engine::getInstance();
    $engine->initialize($lib_path, $dict_path);
    $wav_data = $engine->tts("Hello World", 3);
    $engine->finalize();
} catch (VoicevoxException $e) {
    echo "VOICEVOX Error: " . $e->getMessage();
}
```

#### Advanced TTS with AudioQuery (OOP)
```php
try {
    $engine = Engine::getInstance();
    $engine->initialize($lib_path, $dict_path);
    
    $audio_query = $engine->audioQuery("Hello World", 3);
    $query_data = json_decode($audio_query, true);
    $query_data['speed_scale'] = 1.2;  // Modify parameters
    $wav_data = $engine->synthesis(json_encode($query_data), 3);
    
    $engine->finalize();
} catch (VoicevoxException $e) {
    echo "VOICEVOX Error: " . $e->getMessage();
}
```

#### Procedural Usage (DEPRECATED - REMOVAL SCHEDULED)
```php
// WARNING: These functions are deprecated and will be REMOVED in v1.0.0
// Scheduled for removal: 3 months from 2025-06-29
// Use OOP API instead: \Voicevox\Engine::getInstance()
voicevox_initialize($lib_path, $dict_path);
$wav_data = voicevox_tts("Hello World", 3);
voicevox_finalize();
```

## Testing Notes

- All tests require VOICEVOX environment variables to be set
- PHPT tests (`.phpt` files) use PHP's built-in testing framework
- Custom tests (`.php` files) provide integration testing
- Use `run_tests.sh` for comprehensive testing with detailed reporting

## Commit Message Guidelines

This project follows [Conventional Commits](https://www.conventionalcommits.org/) specification for commit messages.

### Format
```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Types
- **feat**: A new feature for the user
- **fix**: A bug fix for the user
- **docs**: Documentation changes
- **style**: Code style changes (formatting, semicolons, etc.)
- **refactor**: Code refactoring without feature changes
- **perf**: Performance improvements
- **test**: Adding or updating tests
- **build**: Changes to build system or dependencies
- **ci**: Changes to CI configuration
- **chore**: Maintenance tasks

### Examples
```
feat(oop): add singleton pattern to Engine class
fix(memory): resolve memory leak in audio_query function
docs: update README with environment setup instructions
test: add comprehensive error handling tests
refactor(api): simplify procedural function signatures
```

### Scopes (optional)
- `api` - API changes
- `oop` - Object-oriented implementation
- `memory` - Memory management
- `test` - Testing infrastructure
- `build` - Build system

## Development Considerations

- The extension uses dynamic loading - no compile-time VOICEVOX dependency
- Memory is cleaned immediately after operations to prevent leaks
- Both APIs share the same underlying state and can be used together
- Error conditions are thoroughly tested and handled gracefully
- The singleton pattern in OOP API prevents initialization conflicts