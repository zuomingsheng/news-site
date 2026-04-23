#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
检查文件是否受保护
用法: check_protected.py <input_json_file> <config_file>
"""

import sys
import json
import os
import re


def main():
    if len(sys.argv) < 3:
        sys.exit(0)

    input_file = sys.argv[1]
    config_file = sys.argv[2]

    # 读取输入 JSON
    try:
        with open(input_file, "r", encoding="utf-8") as f:
            data = json.load(f)
    except (IOError, json.JSONDecodeError, ValueError):
        sys.exit(0)

    # 提取 file_path（兼容多种格式）
    file_path = (
        data.get("tool_input", {}).get("file_path", "") or
        data.get("tool_response", {}).get("filePath", "") or
        data.get("filePath", "") or
        ""
    )

    if not file_path:
        sys.exit(0)

    # 配置文件不存在则放行
    if not os.path.isfile(config_file):
        sys.exit(0)

    # 读取配置
    try:
        with open(config_file, "r", encoding="utf-8") as f:
            config = json.load(f)
    except (json.JSONDecodeError, IOError):
        sys.exit(0)

    blocked = False
    reason = ""

    # Normalize path
    file_path_norm = file_path.lstrip("/")
    basename = os.path.basename(file_path)
    _, ext = os.path.splitext(basename)
    if ext:
        ext = ext.lower()

    # 1) Check extension
    blocked_exts = config.get("blocked_extensions", [])
    for ext_pat in blocked_exts:
        ext_pat_norm = ext_pat.lstrip(".").lower()
        ext_pat_norm = "." + ext_pat_norm
        if ext == ext_pat_norm:
            blocked = True
            reason = f"File type '{ext}' is protected"
            break

    # 2) 检查精确文件名
    if not blocked:
        blocked_files = config.get("blocked_files", [])
        for blocked_file in blocked_files:
            blocked_file_trimmed = blocked_file.lstrip("/")
            if (
                basename == blocked_file or
                file_path == blocked_file or
                file_path_norm == blocked_file_trimmed or
                file_path_norm.endswith("/" + blocked_file_trimmed) or
                file_path_norm.endswith("\\" + blocked_file_trimmed)
            ):
                blocked = True
                reason = f"File '{blocked_file}' is protected"
                break

    # 3) 检查通配符模式
    if not blocked:
        blocked_patterns = config.get("blocked_patterns", [])
        for pattern in blocked_patterns:
            # 转换为正则表达式
            regex_pattern = pattern.replace(".", r"\.").replace("*", ".*").replace("?", ".")
            try:
                if re.fullmatch(regex_pattern, basename, re.IGNORECASE):
                    blocked = True
                    reason = f"File matches protected pattern '{pattern}'"
                    break
            except re.error:
                pass

    # 输出结果
    if blocked:
        result = {
            "continue": False,
            "stopReason": reason,
            "systemMessage": "Blocked: " + reason,
            "hookSpecificOutput": {
                "hookEventName": "PreToolUse",
                "permissionDecision": "deny",
                "permissionDecisionReason": reason
            }
        }
        print(json.dumps(result))
        sys.exit(1)

    sys.exit(0)


if __name__ == "__main__":
    main()
