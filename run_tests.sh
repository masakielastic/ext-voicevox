#!/bin/bash
# VOICEVOX Extension Test Suite Runner
# ファイル名: run_tests.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEST_DIR="$SCRIPT_DIR/tests"
RESULTS_DIR="$SCRIPT_DIR/test_results"
EXTENSION_PATH="$SCRIPT_DIR/modules/voicevox.so"

# 環境設定
export LD_LIBRARY_PATH="/home/masakielastic/.voicevox/squashfs-root/vv-engine:$LD_LIBRARY_PATH"

# カラー出力設定
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== VOICEVOX Extension Test Suite ===${NC}"
echo "Timestamp: $(date)"
echo "Extension: $EXTENSION_PATH"
echo "Test Directory: $TEST_DIR"
echo ""

# 結果ディレクトリ作成
mkdir -p "$RESULTS_DIR"
mkdir -p "$TEST_DIR"

# テストログファイル
LOG_FILE="$RESULTS_DIR/test_results_$(date +%Y%m%d_%H%M%S).log"
SUMMARY_FILE="$RESULTS_DIR/test_summary.txt"

# 拡張機能の確認
if [ ! -f "$EXTENSION_PATH" ]; then
    echo -e "${RED}ERROR: Extension not found: $EXTENSION_PATH${NC}" | tee "$LOG_FILE"
    echo "Please run 'make' first to build the extension."
    exit 1
fi

# PHP拡張機能のロードテスト
echo -e "${YELLOW}[SETUP]${NC} Testing extension loading..."
if php -d extension="$EXTENSION_PATH" -r "echo extension_loaded('voicevox') ? 'OK' : 'FAIL';" 2>/dev/null | grep -q "OK"; then
    echo -e "${GREEN}✓ Extension loads successfully${NC}" | tee -a "$LOG_FILE"
else
    echo -e "${RED}✗ Extension failed to load${NC}" | tee -a "$LOG_FILE"
    exit 1
fi

# テストカウンター
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
SKIPPED_TESTS=0

# PHPTテスト実行関数
run_phpt_test() {
    local test_file="$1"
    local test_name=$(basename "$test_file" .phpt)
    
    echo -e "${YELLOW}[TEST]${NC} Running $test_name..." | tee -a "$LOG_FILE"
    
    # PHPTテスト実行
    if php -d extension="$EXTENSION_PATH" run-tests.php "$test_file" &>/dev/null; then
        echo -e "${GREEN}✓ PASS${NC} $test_name" | tee -a "$LOG_FILE"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗ FAIL${NC} $test_name" | tee -a "$LOG_FILE"
        ((FAILED_TESTS++))
        
        # エラー詳細を記録
        echo "--- Error details for $test_name ---" >> "$LOG_FILE"
        php -d extension="$EXTENSION_PATH" run-tests.php "$test_file" >> "$LOG_FILE" 2>&1 || true
        echo "--- End error details ---" >> "$LOG_FILE"
    fi
    
    ((TOTAL_TESTS++))
}

# 独自テスト実行関数
run_custom_test() {
    local test_file="$1"
    local test_name=$(basename "$test_file" .php)
    
    echo -e "${YELLOW}[TEST]${NC} Running $test_name..." | tee -a "$LOG_FILE"
    
    if php -d extension="$EXTENSION_PATH" "$test_file" &>/dev/null; then
        echo -e "${GREEN}✓ PASS${NC} $test_name" | tee -a "$LOG_FILE"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗ FAIL${NC} $test_name" | tee -a "$LOG_FILE"
        ((FAILED_TESTS++))
        
        # エラー詳細を記録
        echo "--- Error details for $test_name ---" >> "$LOG_FILE"
        php -d extension="$EXTENSION_PATH" "$test_file" >> "$LOG_FILE" 2>&1 || true
        echo "--- End error details ---" >> "$LOG_FILE"
    fi
    
    ((TOTAL_TESTS++))
}

# 1. PHPTテスト実行
echo -e "\n${BLUE}=== Running PHPT Tests ===${NC}"
if [ -d "$TEST_DIR" ]; then
    for test_file in "$TEST_DIR"/*.phpt; do
        if [ -f "$test_file" ]; then
            run_phpt_test "$test_file"
        fi
    done
else
    echo -e "${YELLOW}No PHPT tests found in $TEST_DIR${NC}"
fi

# 2. 独自テスト実行
echo -e "\n${BLUE}=== Running Custom Tests ===${NC}"
for test_file in "$TEST_DIR"/*.php; do
    if [ -f "$test_file" ]; then
        run_custom_test "$test_file"
    fi
done

# 3. API統合テスト
echo -e "\n${BLUE}=== Running API Integration Tests ===${NC}"
if [ -f "$SCRIPT_DIR/test/voicevox_server.php" ]; then
    echo -e "${YELLOW}[TEST]${NC} API Server Integration..." | tee -a "$LOG_FILE"
    
    # サーバー起動（バックグラウンド）
    php -d extension="$EXTENSION_PATH" -S localhost:8081 "$SCRIPT_DIR/test/voicevox_server.php" &>/dev/null &
    SERVER_PID=$!
    
    # サーバー起動待機
    sleep 3
    
    # APIテスト
    if curl -s http://localhost:8081/status | grep -q "ok"; then
        echo -e "${GREEN}✓ PASS${NC} API Server Integration" | tee -a "$LOG_FILE"
        ((PASSED_TESTS++))
    else
        echo -e "${RED}✗ FAIL${NC} API Server Integration" | tee -a "$LOG_FILE"
        ((FAILED_TESTS++))
    fi
    
    # サーバー停止
    kill $SERVER_PID 2>/dev/null || true
    wait $SERVER_PID 2>/dev/null || true
    
    ((TOTAL_TESTS++))
fi

# 結果サマリー
echo -e "\n${BLUE}=== Test Results Summary ===${NC}" | tee -a "$LOG_FILE"
echo "Total Tests: $TOTAL_TESTS" | tee -a "$LOG_FILE"
echo -e "${GREEN}Passed: $PASSED_TESTS${NC}" | tee -a "$LOG_FILE"
echo -e "${RED}Failed: $FAILED_TESTS${NC}" | tee -a "$LOG_FILE"
echo -e "${YELLOW}Skipped: $SKIPPED_TESTS${NC}" | tee -a "$LOG_FILE"

# サマリーファイル作成
cat > "$SUMMARY_FILE" << EOF
VOICEVOX Extension Test Results
==============================
Date: $(date)
Extension: $EXTENSION_PATH

Results:
- Total Tests: $TOTAL_TESTS
- Passed: $PASSED_TESTS
- Failed: $FAILED_TESTS
- Skipped: $SKIPPED_TESTS
- Success Rate: $(( PASSED_TESTS * 100 / TOTAL_TESTS ))%

Log File: $LOG_FILE
EOF

echo ""
echo -e "${BLUE}Detailed results saved to: $LOG_FILE${NC}"
echo -e "${BLUE}Summary saved to: $SUMMARY_FILE${NC}"

# 終了コード設定
if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed!${NC}"
    exit 0
else
    echo -e "\n${RED}❌ Some tests failed. Check logs for details.${NC}"
    exit 1
fi
