#!/bin/bash
# notify-dingtalk-task.sh
# 后台任务完成通知脚本
# 用法: 在长时间运行命令后调用此脚本

set -e

# 配置文件路径
CONFIG_FILE="$(dirname "$0")/../dingtalk-config.txt"
HOOK_SCRIPT="$(dirname "$0")/notify-dingtalk.sh"

# 获取上一个命令的退出状态
LAST_EXIT_CODE=$?

# 获取上一个命令信息
LAST_COMMAND="${1:-未知命令}"
LAST_EXIT_STATUS="${2:-$LAST_EXIT_CODE}"

# 判断是否成功
if [[ "$LAST_EXIT_STATUS" -eq 0 ]]; then
    STATUS="✅ 成功"
else
    STATUS="❌ 失败 (退出码: $LAST_EXIT_STATUS)"
fi

# 调用钉钉通知
if [[ -f "$HOOK_SCRIPT" ]]; then
    bash "$HOOK_SCRIPT" \
        "后台任务 $STATUS" \
        "命令: $LAST_COMMAND\n状态: $STATUS\n工作目录: $(pwd)"
else
    echo "[DingTalk] Hook 脚本不存在: $HOOK_SCRIPT"
fi
