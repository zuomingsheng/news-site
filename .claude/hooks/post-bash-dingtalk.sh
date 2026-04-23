#!/bin/bash
# post-bash-dingtalk.sh
# Bash 命令执行后触发 - 检测后台任务完成并发送钉钉通知

set -e

# 配置文件路径
CONFIG_FILE="$(dirname "$0")/../dingtalk-config.txt"
DINGTALK_SCRIPT="$(dirname "$0")/notify-dingtalk.sh"

# 从环境变量获取任务信息（Claude Code 会传递这些）
TASK_COMMAND="${CLAUDE_TASK_COMMAND:-}"
TASK_EXIT_CODE="${CLAUDE_TASK_EXIT_CODE:-0}"
TASK_TYPE="${CLAUDE_TASK_TYPE:-}"

# 检测是否是长时间运行的后台任务类型
# 常见的长任务关键词
LONG_TASK_KEYWORDS="queue:work|queue:listen|serve|watch|poll|monitor|sleep|timeout"

should_notify() {
    # 检查是否是后台任务
    if [[ "$TASK_TYPE" == *"background"* ]] || [[ "$TASK_TYPE" == *"run_in_background"* ]]; then
        return 0
    fi

    # 检查命令是否包含长任务关键词
    if [[ -n "$TASK_COMMAND" ]] && echo "$TASK_COMMAND" | grep -qE "$LONG_TASK_KEYWORDS"; then
        return 0
    fi

    # 检查命令是否包含后台运行符号 &
    if [[ "$TASK_COMMAND" == *"&"* ]]; then
        return 0
    fi

    return 1
}

# 发送钉钉通知
send_dingtalk_notification() {
    local title="$1"
    local content="$2"

    if [[ ! -f "$DINGTALK_SCRIPT" ]]; then
        echo "[DingTalk] 通知脚本不存在，跳过通知"
        return 1
    fi

    # 加载配置并发送
    bash "$DINGTALK_SCRIPT" "$title" "$content"
}

# 主逻辑
main() {
    # 检查配置是否存在
    if [[ ! -f "$CONFIG_FILE" ]]; then
        echo "[DingTalk] 配置文件不存在，跳过通知"
        exit 0
    fi

    # 检查配置是否已填写
    APPKEY=$(grep "^DINGTALK_APPKEY=" "$CONFIG_FILE" | cut -d'=' -f2 | tr -d '\r')
    if [[ "$APPKEY" == "your_appkey_here" ]] || [[ -z "$APPKEY" ]]; then
        echo "[DingTalk] 配置未填写，跳过通知"
        exit 0
    fi

    # 判断是否需要发送通知
    if ! should_notify; then
        echo "[DingTalk] 非长任务，跳过通知"
        exit 0
    fi

    # 发送通知
    local status="✅ 完成"
    if [[ "$TASK_EXIT_CODE" != "0" ]]; then
        status="❌ 失败 (退出码: $TASK_EXIT_CODE)"
    fi

    send_dingtalk_notification \
        "后台任务 $status" \
        "命令: ${TASK_COMMAND:-未知}\n状态: $status\n时间: $(date '+%Y-%m-%d %H:%M:%S')"
}

main "$@"
