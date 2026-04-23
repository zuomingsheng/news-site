#!/bin/bash
# notify-dingtalk.sh
# 任务完成通知脚本 - 发送钉钉个人消息

set -e

# 设置 UTF-8 编码
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# 配置文件路径
CONFIG_FILE="$(dirname "$0")/../dingtalk-config.txt"

# 读取配置
load_config() {
    if [[ ! -f "$CONFIG_FILE" ]]; then
        echo "[DingTalk] 配置文件不存在: $CONFIG_FILE"
        exit 0
    fi

    APPKEY=$(grep "^DINGTALK_APPKEY=" "$CONFIG_FILE" | cut -d'=' -f2 | tr -d '\r')
    APPSECRET=$(grep "^DINGTALK_APPSECRET=" "$CONFIG_FILE" | cut -d'=' -f2 | tr -d '\r')
    AGENTID=$(grep "^DINGTALK_AGENTID=" "$CONFIG_FILE" | cut -d'=' -f2 | tr -d '\r')
    USERID=$(grep "^DINGTALK_USERID=" "$CONFIG_FILE" | cut -d'=' -f2 | tr -d '\r')

    # 验证必填配置
    if [[ "$APPKEY" == "your_appkey_here" ]] || [[ -z "$APPKEY" ]]; then
        echo "[DingTalk] 请先配置钉钉 AppKey"
        exit 0
    fi

    echo "[DingTalk] 配置读取成功"
}

# 获取 Access Token
get_access_token() {
    local token_response
    token_response=$(curl -s -X GET "https://oapi.dingtalk.com/gettoken?appkey=$APPKEY&appsecret=$APPSECRET")

    ACCESS_TOKEN=$(echo "$token_response" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)

    if [[ -z "$ACCESS_TOKEN" ]]; then
        echo "[DingTalk] 获取 Access Token 失败: $token_response"
        exit 1
    fi
    echo "[DingTalk] Access Token 获取成功"
}

# 发送消息
send_message() {
    local title="$1"
    local content="$2"
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    # 构建消息内容
    local message="【任务完成】

标题: $title
内容: $content
时间: $timestamp"

    # 使用 here-doc 直接发送，避免 sed 转义破坏中文
    local msg_response
    msg_response=$(curl -s -X POST "https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=$ACCESS_TOKEN" \
        -H "Content-Type: application/json; charset=utf-8" \
        --data-binary @- <<JSONEOF
{
    "agent_id": $AGENTID,
    "userid_list": "$USERID",
    "msgtype": "text",
    "msg": {
        "msgtype": "text",
        "text": {
            "content": "$message"
        }
    }
}
JSONEOF
)

    echo "[DingTalk] API 响应: $msg_response"

    if echo "$msg_response" | grep -q '"errcode":0'; then
        echo "[DingTalk] 消息发送成功"
        return 0
    fi

    echo "[DingTalk] 消息发送失败"
    return 1
}

# 主函数
main() {
    local title="${1:-任务完成}"
    local content="${2:-后台任务执行完成，请在 Claude Code 中查看结果}"

    load_config
    get_access_token
    send_message "$title" "$content"
}

main "$@"
