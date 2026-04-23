#
#功能描述：阻止 Claude 执行 rm -rf /、git reset --hard 等危险命令。
#

#!/usr/bin/env bash
set -euo pipefail

# 使用 python 解析 JSON 获取命令
cmd=$(python3 -c "import sys, json; print(json.load(sys.stdin).get('tool_input', {}).get('command', ''))" 2>/dev/null || echo "")

deny_patterns=(
    'rm\s+-rf\s+/'
    'git\s+reset\s+--hard'
    'curl\s+http'
    'sudo\s+'
)

for pat in "${deny_patterns[@]}"; do
    if echo "$cmd" | grep -Eiq "$pat"; then
        echo "🚫 已阻止命令：匹配到禁止模式 '$pat'。请使用更安全的替代方案，或解释为何必须执行此命令。" 1>&2
        exit 2
    fi
done

exit 0