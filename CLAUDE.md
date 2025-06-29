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
php -d extension=modules/voicevox.so test_enhanced_oop.php
php -d extension=modules/voicevox.so test_with_env.php
./run_tests.sh                                              # Comprehensive test suite

# Run PHPT tests only
php -d extension=modules/voicevox.so run-tests.php tests/
```

### Development Server
```bash
# Start OOP API server
php -d extension=modules/voicevox.so -S localhost:8080 demo/new_server.php

# Start procedural API server  
php -d extension=modules/voicevox.so -S localhost:8081 demo/voicevox_server.php
```

## Architecture

### Dual API Design
The extension provides two complementary APIs:

1. **Procedural API** (`voicevox.c`): Core functionality with direct function calls
   - `voicevox_initialize()`, `voicevox_tts()`, `voicevox_finalize()`, etc.
   
2. **Object-Oriented API** (`voicevox_oop.c`): Wrapper around procedural API
   - `Voicevox\Engine` class with singleton pattern
   - `Voicevox\Exception\VoicevoxException` for error handling

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

#### Simple TTS (One-step)
```php
voicevox_initialize($lib_path, $dict_path);
$wav_data = voicevox_tts("Hello World", 3);
voicevox_finalize();
```

#### Advanced TTS (Two-step with parameter control)
```php
$audio_query = voicevox_audio_query("Hello World", 3);
$query_data = json_decode($audio_query, true);
$query_data['speedScale'] = 1.2;  // Modify parameters
$wav_data = voicevox_synthesis(json_encode($query_data), 3);
```

#### Object-oriented Usage
```php
$engine = \Voicevox\Engine::getInstance();
$engine->initialize($lib_path, $dict_path);
$wav_data = $engine->tts("Hello World", 3);
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