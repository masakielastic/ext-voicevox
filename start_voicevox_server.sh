#!/bin/bash
# VOICEVOX Server Wrapper (Production Ready)
# ファイル名: start_voicevox_server.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VOICEVOX_EXT="$SCRIPT_DIR/modules/voicevox.so"
SERVER_SCRIPT="$SCRIPT_DIR/demo/voicevox_server.php"
PORT=${1:-8080}
HOST=${2:-localhost}

# ライブラリパス設定
export LD_LIBRARY_PATH="/home/masakielastic/.voicevox/squashfs-root/vv-engine:$LD_LIBRARY_PATH"

# ログファイル設定
LOG_DIR="$SCRIPT_DIR/logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/voicevox_server.log"
ERROR_LOG="$LOG_DIR/voicevox_error.log"

echo "=== VOICEVOX Server Startup ===" | tee "$LOG_FILE"
echo "Timestamp: $(date)" | tee -a "$LOG_FILE"
echo "Extension: $VOICEVOX_EXT" | tee -a "$LOG_FILE"
echo "Server Script: $SERVER_SCRIPT" | tee -a "$LOG_FILE"
echo "Host: $HOST" | tee -a "$LOG_FILE"
echo "Port: $PORT" | tee -a "$LOG_FILE"
echo "LD_LIBRARY_PATH: $LD_LIBRARY_PATH" | tee -a "$LOG_FILE"

# 拡張機能の確認
if [ ! -f "$VOICEVOX_EXT" ]; then
    echo "ERROR: VOICEVOX extension not found: $VOICEVOX_EXT" | tee -a "$ERROR_LOG"
    exit 1
fi

if [ ! -f "$SERVER_SCRIPT" ]; then
    echo "ERROR: Server script not found: $SERVER_SCRIPT" | tee -a "$ERROR_LOG"
    exit 1
fi

# 既存プロセスのチェック
EXISTING_PID=$(lsof -ti :$PORT 2>/dev/null || true)
if [ ! -z "$EXISTING_PID" ]; then
    echo "WARNING: Port $PORT is already in use by PID $EXISTING_PID" | tee -a "$LOG_FILE"
    read -p "Kill existing process? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        kill -TERM "$EXISTING_PID" 2>/dev/null || true
        sleep 2
        kill -KILL "$EXISTING_PID" 2>/dev/null || true
        echo "Existing process terminated" | tee -a "$LOG_FILE"
    else
        echo "Aborted" | tee -a "$ERROR_LOG"
        exit 1
    fi
fi

# トラップ設定（優雅な終了）
cleanup() {
    echo "" | tee -a "$LOG_FILE"
    echo "=== Shutting down VOICEVOX server ===" | tee -a "$LOG_FILE"
    echo "Timestamp: $(date)" | tee -a "$LOG_FILE"
    
    # PHPプロセスを探して終了
    PIDS=$(ps aux | grep "voicevox_server.php" | grep -v grep | awk '{print $2}')
    if [ ! -z "$PIDS" ]; then
        echo "Terminating PHP processes: $PIDS" | tee -a "$LOG_FILE"
        for PID in $PIDS; do
            echo "  Sending TERM signal to PID $PID" | tee -a "$LOG_FILE"
            kill -TERM "$PID" 2>/dev/null || true
            sleep 2
            
            # プロセスが残っている場合は強制終了
            if kill -0 "$PID" 2>/dev/null; then
                echo "  Sending KILL signal to PID $PID" | tee -a "$LOG_FILE"
                kill -KILL "$PID" 2>/dev/null || true
            else
                echo "  PID $PID terminated gracefully" | tee -a "$LOG_FILE"
            fi
        done
    else
        echo "No PHP processes found" | tee -a "$LOG_FILE"
    fi
    
    echo "Server shutdown complete at $(date)" | tee -a "$LOG_FILE"
    echo "=== End of Session ===" | tee -a "$LOG_FILE"
    echo "" | tee -a "$LOG_FILE"
    exit 0
}

# シグナルハンドラー設定
trap cleanup INT TERM

echo "Starting server..." | tee -a "$LOG_FILE"
echo "Press Ctrl+C to stop the server" | tee -a "$LOG_FILE"
echo ""

# サーバー起動（ログ付き）
echo "Server command: php -d extension=$VOICEVOX_EXT -S $HOST:$PORT $SERVER_SCRIPT" | tee -a "$LOG_FILE"
php -d extension="$VOICEVOX_EXT" -S "$HOST:$PORT" "$SERVER_SCRIPT" 2>> "$ERROR_LOG" &
SERVER_PID=$!

echo "Server started with PID: $SERVER_PID" | tee -a "$LOG_FILE"
echo "Server URL: http://$HOST:$PORT" | tee -a "$LOG_FILE"
echo "Log file: $LOG_FILE" | tee -a "$LOG_FILE"
echo "Error log: $ERROR_LOG" | tee -a "$LOG_FILE"
echo ""
echo "API Endpoints:"
echo "  GET  http://$HOST:$PORT/status"
echo "  GET  http://$HOST:$PORT/speakers"
echo "  POST http://$HOST:$PORT/tts"
echo "  POST http://$HOST:$PORT/audio_query"
echo "  POST http://$HOST:$PORT/synthesis"
echo ""
echo "Web UI: http://$HOST:$PORT/"
echo ""

# ヘルスチェック
sleep 3
if kill -0 $SERVER_PID 2>/dev/null; then
    echo "✓ Server is running normally" | tee -a "$LOG_FILE"
    
    # 基本的なヘルスチェック
    if curl -s "http://$HOST:$PORT/status" > /dev/null 2>&1; then
        echo "✓ API endpoint responding" | tee -a "$LOG_FILE"
    else
        echo "⚠ API endpoint not responding yet (still initializing?)" | tee -a "$LOG_FILE"
    fi
else
    echo "✗ Server failed to start" | tee -a "$ERROR_LOG"
    exit 1
fi

# サーバーの動作を監視
echo "Monitoring server process..." | tee -a "$LOG_FILE"
while kill -0 $SERVER_PID 2>/dev/null; do
    sleep 5
done

echo "Server process ended unexpectedly" | tee -a "$ERROR_LOG"
cleanup
