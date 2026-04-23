#
#功能描述：在 Claude 完成长时间任务后发送系统通知
#

#!/usr/bin/env bash

if [[ "$OSTYPE" == "darwin"* ]]; then
    # 用 python 解析 session_id
    session_id=$(python3 -c "import sys, json; print(json.load(sys.stdin).get('session_id', 'unknown'))" 2>/dev/null || echo "unknown")
    
    osascript -e "display notification \"Claude Code 已完成任务\" with title \"🤖 Claude Code\" subtitle \"会话: ${session_id:0:8}\" sound name \"Glass\""
fi

exit 0