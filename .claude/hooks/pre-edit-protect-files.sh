#!/usr/bin/env bash
set -e

CONFIG_FILE=".claude/hooks/protected-files.json"
SCRIPT_FILE=".claude/hooks/check_protected.py"

input=$(cat)

if [ -z "$input" ]; then
    exit 0
fi

# Find Python interpreter
PYTHON_BIN=""
for candidate in \
    "/c/Users/admin/AppData/Local/Programs/Python/Python313/python.exe" \
    "/c/Users/admin/AppData/Local/Programs/Python/Python312/python.exe" \
    "/c/Users/admin/AppData/Local/Programs/Python/Python311/python.exe" \
    python3 \
    python
do
    if [ -f "$candidate" ]; then
        if "$candidate" -c "print('')" 2>/dev/null; then
            PYTHON_BIN="$candidate"
            break
        fi
    fi
done

if [ -z "$PYTHON_BIN" ]; then
    exit 0
fi

# Create temp file for input
input_file=$(mktemp)
printf '%s' "$input" > "$input_file"

# Run Python checker
"$PYTHON_BIN" "$SCRIPT_FILE" "$input_file" "$CONFIG_FILE"
exit_code=$?

rm -f "$input_file"
exit $exit_code
