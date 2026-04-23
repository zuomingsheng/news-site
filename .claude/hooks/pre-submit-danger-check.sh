#!/usr/bin/env bash
set -euo pipefail

# 读取 Claude Code 传入的 JSON 输入
INPUT=$(cat)

# 提取 prompt 字段内容
PROMPT=$(echo "$INPUT" | sed -n 's/.*"prompt":"\([^"]*\)".*/\1/p' || true)

# 如果提取失败，尝试直接匹配整行（容错）
if [[ -z "$PROMPT" ]]; then
    PROMPT="$INPUT"
fi

# 定义危险命令正则（支持大小写不敏感）
DANGER_PATTERNS='rm[[:space:]]+-rf|rm[[:space:]]+-fr|rd[[:space:]]+/s[[:space:]]+/q|format[[:space:]]+|del[[:space:]]+/f[[:space:]]+/s[[:space:]]+/q'

if echo "$PROMPT" | grep -qiE "$DANGER_PATTERNS"; then
    echo '[安全拦截] 检测到非常危险的删除命令，Claude Code 拒绝执行。如需删除文件，请直接在本地终端中手动操作。' >&2
    exit 1
fi

exit 0
