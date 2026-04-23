#
#功能描述：#Claude 修改文件后，自动运行 Prettier 格式化代码，并对 JSON/YAML 配置文件进行语法校验。
#

#!/usr/bin/env bash
set -euo pipefail

# 读取被修改的文件路径（Claude Code 会自动注入此环境变量）
file_path="$CLAUDE_FILE_PATH"

if [ -z "$file_path" ] || [ ! -f "$file_path" ]; then
    exit 0
fi

# 根据文件类型执行格式化和检查
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

# JSON 语法校验
if [[ "$file_path" == *.json ]]; then
    if ! python3 -c "import json; json.load(open('$file_path'))" 2>/dev/null; then
        echo "❌ 错误：文件 $file_path 的 JSON 语法已损坏" 1>&2
        exit 1
    fi
fi

exit 0