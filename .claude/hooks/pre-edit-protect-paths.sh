#
#功能描述：阻止 Claude 修改 .env、node_modules/、图片等敏感文件。
#

#!/usr/bin/env bash
set -eo pipefail  # 移除 -u，或手动检查变量

# 安全获取 CLAUDE_FILE_PATH，若未定义则为空
file_path="${CLAUDE_FILE_PATH:-}"

if [ -z "$file_path" ] || [ ! -f "$file_path" ]; then
    exit 0
fi

case "$file_path" in
    *.js|*.ts|*.jsx|*.tsx|*.json|*.css|*.md)
        if command -v npx &>/dev/null && [ -f "package.json" ]; then
            npx prettier --write "$file_path" 2>/dev/null || true
        fi
        ;;
    *.py)
        if command -v black &>/dev/null; then
            black "$file_path" 2>/dev/null || true
        fi
        ;;
    *.go)
        if command -v gofmt &>/dev/null; then
            gofmt -w "$file_path" 2>/dev/null || true
        fi
        ;;
esac

# JSON 语法校验（使用 jq 代替 python3，避免 python3 依赖）
if [[ "$file_path" == *.json ]]; then
    if ! jq empty "$file_path" 2>/dev/null; then
        echo "❌ 错误：文件 $file_path 的 JSON 语法已损坏" 1>&2
        exit 1
    fi
fi

exit 0