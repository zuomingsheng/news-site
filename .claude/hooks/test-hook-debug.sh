#!/usr/bin/env bash
set -e

CONFIG_FILE=".claude/hooks/protected-files.json"
input=$(cat)
echo "DEBUG: input=$input" >&2

if command -v jq &>/dev/null; then
    file_path=$(echo "$input" | jq -r '.tool_input.file_path // .tool_response.filePath // empty')
else
    file_path=$(echo "$input" | php -r '$input=file_get_contents("php://stdin");$data=json_decode($input,true);echo $data["tool_input"]["file_path"]??$data["tool_response"]["filePath"]??"";')
fi

echo "DEBUG: file_path=$file_path" >&2

if [ -z "$file_path" ]; then
    echo "DEBUG: empty file_path" >&2
    exit 0
fi

if [ ! -f "$CONFIG_FILE" ]; then
    echo "DEBUG: config missing" >&2
    exit 0
fi

result=$(php -r "
\$file_path = '${file_path}';
\$config_file = '${CONFIG_FILE}';
\$config = json_decode(file_get_contents(\$config_file), true);
if (json_last_error() !== JSON_ERROR_NONE) { exit(0); }
\$blocked = false; \$reason = '';
\$basename = basename(\$file_path);
\$ext = pathinfo(\$file_path, PATHINFO_EXTENSION);
if (\$ext !== '') { \$ext = '.' . \$ext; }
if (!\$blocked && !empty(\$config['blocked_extensions'])) {
    foreach (\$config['blocked_extensions'] as \$ext_pat) {
        if (strcasecmp(\$ext, \$ext_pat) === 0) { \$blocked = true; \$reason = \"File type '\" . \$ext_pat . \"' is protected\"; break; }
    }
}
if (!\$blocked && !empty(\$config['blocked_files'])) {
    foreach (\$config['blocked_files'] as \$blocked_file) {
        \$blocked_file_trimmed = ltrim(\$blocked_file, '/');
        \$file_path_trimmed = ltrim(\$file_path, '/');
        \$basename_match = (\$basename === \$blocked_file);
        \$exact_match = (\$file_path === \$blocked_file || \$file_path_trimmed === \$blocked_file_trimmed);
        \$suffix_match = false;
        if (strlen(\$blocked_file_trimmed) > 0 && strlen(\$file_path_trimmed) >= strlen(\$blocked_file_trimmed)) {
            \$suffix_match = (substr(\$file_path_trimmed, -strlen(\$blocked_file_trimmed)) === \$blocked_file_trimmed);
        }
        if (\$basename_match || \$exact_match || \$suffix_match) {
            \$blocked = true;
            \$reason = \"File '\" . \$blocked_file . \"' is protected\";
            break;
        }
    }
}
if (!\$blocked && !empty(\$config['blocked_patterns'])) {
    foreach (\$config['blocked_patterns'] as \$pattern) {
        if (fnmatch(\$pattern, \$basename, FNM_CASEFOLD)) {
            \$blocked = true;
            \$reason = \"File matches protected pattern '\" . \$pattern . \"'\";
            break;
        }
    }
}
if (\$blocked) {
    echo json_encode([
        'continue' => false,
        'stopReason' => \$reason,
        'systemMessage' => 'Blocked: ' . \$reason,
        'hookSpecificOutput' => [
            'hookEventName' => 'PreToolUse',
            'permissionDecision' => 'deny',
            'permissionDecisionReason' => \$reason
        ]
    ]);
    exit(1);
}
" 2>&1)
exit_code=$?
echo "DEBUG: result=$result" >&2
echo "DEBUG: exit_code=$exit_code" >&2

if [ $exit_code -ne 0 ] && [ -n "$result" ]; then
    echo "$result"
    exit 1
fi
exit 0
