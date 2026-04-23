#
#功能描述：Claude 完成任务后自动运行测试，如果测试失败则阻止会话结束并反馈错误，让 Claude 自动修复
#
#!/usr/bin/env bash
set -euo pipefail

# 使用 python 解析 stop_hook_active 字段
stop_hook_active=$(python3 -c "import sys, json; print(json.load(sys.stdin).get('stop_hook_active', False))" 2>/dev/null || echo "false")

if [ "$stop_hook_active" = "true" ]; then
    exit 0
fi

if [ -f "package.json" ]; then
    if npm test -- --watchAll=false --passWithNoTests 2>&1 | tail -20; then
        echo "✅ 所有测试通过！"
        exit 0
    else
        echo "❌ 测试失败。请在结束前修复失败的测试。" 1>&2
        exit 2
    fi
elif [ -f "pytest.ini" ] || [ -d "tests" ]; then
    if python -m pytest -x 2>&1 | tail -20; then
        echo "✅ 所有测试通过！"
        exit 0
    else
        echo "❌ 测试失败。请在结束前修复失败的测试。" 1>&2
        exit 2
    fi
fi

exit 0