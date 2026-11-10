#!/bin/bash
# Checking Root Access
if [[ $EUID -ne 0 ]]; then
    echo -e "\033[31m[ERROR]\033[0m Please run this script as \033[1mroot\033[0m."
    exit 1
fi

# Debian minimal images (13 in particular) ship neither curl nor wget; the
# self-update below needs one of them before anything else can run.
if ! command -v curl >/dev/null 2>&1 && ! command -v wget >/dev/null 2>&1; then
    apt-get update -o DPkg::Lock::Timeout=180 >/dev/null 2>&1
    apt-get install -y -o DPkg::Lock::Timeout=180 curl ca-certificates >/dev/null 2>&1 \
        || { echo -e "\033[31m[ERROR]\033[0m Neither curl nor wget is available and apt install failed. Install curl manually and re-run."; exit 1; }
fi

INSTALL_LOG="/tmp/mirza_install.log"

export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a
export NEEDRESTART_SUSPEND=1
export APT_LISTCHANGES_FRONTEND=none

# ── Progress / ETA state ─────────────────────────────────────
ETA_REMAINING=0   # estimated seconds left for the whole install
STEP_NO=0         # how many steps have started
STEP_TOTAL=0      # total steps planned for this run (0 = unknown)

# Seconds -> "9s" or "2m05s"
_fmt_secs() {
    local s=$1
    [ "$s" -lt 0 ] && s=0
    if [ "$s" -lt 60 ]; then printf '%ds' "$s"; else printf '%dm%02ds' $((s / 60)) $((s % 60)); fi
}

# Filled/empty bar of WIDTH chars at PCT percent
_bar() {
    local pct=$1 width=${2:-14} filled
    [ "$pct" -gt 100 ] && pct=100; [ "$pct" -lt 0 ] && pct=0
    filled=$(( pct * width / 100 ))
    printf '%s%s%s%s%s' "$C_BORDER" "$(_repeat "█" "$filled")" "$C_DIM" "$(_repeat "░" $(( width - filled )))" "$CR"
}

# Expected duration (seconds) for a step, matched by its label.
# Keeps the per-step bar and the overall ETA in sync.
_step_eta() {
    case "$1" in
        "Preparing package manager"*)        echo 5  ;;
        "Adding PHP repository"*|"Retrying PHP repository"*) echo 15 ;;
        "Updating & upgrading"*|"Re-running system update"*) echo 120 ;;
        "Installing base tools"*)            echo 25 ;;
        "Ensuring cron"*)                    echo 10 ;;
        "Installing PHP dependencies"*)      echo 60 ;;
        "Installing PHP "*)                  echo 30 ;;
        "Installing web stack"*)             echo 90 ;;
        "Repairing broken MySQL"*)           echo 90 ;;
        "Re-installing web stack"*)          echo 90 ;;
        "Installing phpMyAdmin"*)            echo 40 ;;
        "Installing extra modules"*)         echo 25 ;;
        "Enabling & starting services"*)     echo 8  ;;
        "Configuring firewall"*)             echo 15 ;;
        "Restarting Apache"*)                echo 5  ;;
        "Setting PHP as the active"*|"Setting PHP "*) echo 6  ;;
        "Downloading Mirza"*)                echo 20 ;;
        "Extracting source files"*)          echo 5  ;;
        "Configuring MySQL root access"*)    echo 10 ;;
        "Opening firewall ports"*)           echo 4  ;;
        "Stopping Apache"*)                  echo 4  ;;
        "Installing Let's Encrypt"*|"Installing certbot"*) echo 25 ;;
        "Requesting SSL certificate"*)       echo 25 ;;
        "Installing Apache certbot plugin"*) echo 25 ;;
        "Configuring SSL on Apache"*)        echo 20 ;;
        "Enabling & starting Apache"*|"Starting Apache"*) echo 5 ;;
        "Configuring Apache virtual hosts"*) echo 6  ;;
        "Creating database & user"*)         echo 5  ;;
        "Setting Telegram webhook"*)         echo 5  ;;
        "Backing up vpnbots"*)               echo 5  ;;
        "Restoring vpnbots"*)                echo 5  ;;
        "Setting vpnbot webhooks"*)          echo 10 ;;
        "Initializing database tables"*)     echo 15 ;;
        *)                                   echo 8  ;;
    esac
}

# Plan the run: count pending steps + total expected time (skips done phases).
_phase_line() {
    local names=(DEPS FILES DBROOT SSL VHOST DB CONFIG WEBHOOK) labels=(Packages Files MySQL SSL Apache Database Config Webhook)
    local line="" sep=" " i
    if [ "$UI_W" -lt 80 ]; then
        for i in "${!names[@]}"; do
            [ "${names[$i]}" = "$1" ] && printf " ${C_DIM}Phase %s of 8 ·${CR} ${C_KEY}%s${CR}\n" "$((i + 1))" "${labels[$i]}"
        done
        return
    fi
    for i in "${!names[@]}"; do
        if phase_done "${names[$i]}" || [ "$1" = COMPLETE ]; then line+="${sep}${C_OK}✔ ${labels[$i]}${CR}"
        elif [ "${names[$i]}" = "$1" ]; then line+="${sep}${C_KEY}◐ ${labels[$i]}${CR}"
        else line+="${sep}${C_DIM}○ ${labels[$i]}${CR}"; fi
        sep="  "
    done
    printf '%b\n' "$line"
}

phase_header() {
    local pct=0 left=""
    [ "$STEP_TOTAL" -gt 0 ] && pct=$(( STEP_NO * 100 / STEP_TOTAL ))
    [ "$ETA_REMAINING" -gt 0 ] && left="${C_DIM}~$(_fmt_secs "$ETA_REMAINING") left${CR} "
    echo ""
    _phase_line "$1"
    _lr " ${C_DIM}Overall${CR} $(_bar "$pct" 36) ${pct}%" "$left"
    _rule
    printf " ${C_BORDER}%s${CR}\n" "${2^^}"
}

install_banner() {
    clear
    banner "${C_DIM}Installing${CR} ${C_TITLE}$(state_get SRC_LABEL)${CR}" "${C_DIM}step${CR} ${C_TITLE}$((STEP_NO + 1))${CR} ${C_DIM}of ${STEP_TOTAL}${CR}"
}

plan_eta() {
    STEP_TOTAL=0; ETA_REMAINING=0; STEP_NO=0
    phase_done DEPS    || { STEP_TOTAL=$((STEP_TOTAL + 12)); ETA_REMAINING=$((ETA_REMAINING + 388)); }
    STEP_TOTAL=$((STEP_TOTAL + 1)); ETA_REMAINING=$((ETA_REMAINING + 10));
    phase_done FILES   || { STEP_TOTAL=$((STEP_TOTAL + 3));  ETA_REMAINING=$((ETA_REMAINING + 85)); }
    phase_done DBROOT  || { STEP_TOTAL=$((STEP_TOTAL + 1));  ETA_REMAINING=$((ETA_REMAINING + 10)); }
    if ! phase_done SSL; then
        if [ -f "/etc/letsencrypt/live/$(state_get DOMAIN)/fullchain.pem" ]; then
            STEP_TOTAL=$((STEP_TOTAL + 1)); ETA_REMAINING=$((ETA_REMAINING + 5))
        else
            STEP_TOTAL=$((STEP_TOTAL + 7)); ETA_REMAINING=$((ETA_REMAINING + 108))
        fi
    fi
    phase_done VHOST   || { STEP_TOTAL=$((STEP_TOTAL + 1)); ETA_REMAINING=$((ETA_REMAINING + 6)); }
    phase_done DB      || { STEP_TOTAL=$((STEP_TOTAL + 1)); ETA_REMAINING=$((ETA_REMAINING + 5)); }
    phase_done WEBHOOK || { STEP_TOTAL=$((STEP_TOTAL + 3)); ETA_REMAINING=$((ETA_REMAINING + 25)); }
}

run_step() {
    local msg="$1"
    local cmd="$2"
    local eta="${3:-$(_step_eta "$msg")}"
    [ "$eta" -lt 1 ] && eta=1
    STEP_NO=$((STEP_NO + 1))
    LAST_STEP_MSG="$msg"
    LAST_STEP_CMD="$cmd"
    : > "$INSTALL_LOG"
    local start; start=$(date +%s)
    bash -c "$cmd" >> "$INSTALL_LOG" 2>&1 &
    local pid=$!
    local frames=("⠋" "⠙" "⠹" "⠸" "⠼" "⠴" "⠦" "⠧" "⠇" "⠏")
    local n=${#frames[@]} i=0 live=0 el pct left lefttxt head bar logs
    [ -t 1 ] && live=1
    [ "$live" = 1 ] && tput civis 2>/dev/null
    while kill -0 "$pid" 2>/dev/null; do
        if [ "$live" = 1 ]; then
            el=$(( $(date +%s) - start ))
            pct=$(( el * 100 / eta ))
            [ "$pct" -gt 95 ] && pct=95          # don't show full until it really finishes
            left=$(( eta - el ))
            if [ "$left" -gt 0 ]; then lefttxt="~$(_fmt_secs $left) left"; else lefttxt="finishing…"; fi
            head="$msg"
            [ "${#head}" -gt $(( UI_W - 26 )) ] && head="${head:0:$(( UI_W - 27 ))}…"
            bar=""
            [ $(( ${#head} + 46 )) -le "$UI_W" ] && bar="  ${C_BORDER}▕${CR}$(_bar "$pct" 14)${C_BORDER}▏${CR}"
            logs=$(tail -c 4000 "$INSTALL_LOG" 2>/dev/null | tr '\r\t' '\n ' | sed 's/\x1b\[[0-9;?]*[A-Za-z]//g' \
                | grep -v '^[[:space:]]*$' | tail -n 2 | cut -c1-$(( UI_W - 6 )))
            printf '\r\033[J%s\n' "$(_lr " ${C_KEY}${frames[$i]}${CR} ${C_TITLE}${head}${CR}${bar}" "${C_DIM}$(_fmt_secs $el) · ${lefttxt}${CR} ")"
            printf "   ${C_DIM}│ %s${CR}\n   ${C_DIM}│ %s${CR}\033[2A\r" "$(sed -n 1p <<< "$logs")" "$(sed -n 2p <<< "$logs")"
            i=$(( (i + 1) % n ))
        fi
        sleep 0.2
    done
    wait "$pid"
    local rc=$?
    LAST_STEP_RC=$rc
    el=$(( $(date +%s) - start ))
    if [ "$live" = 1 ]; then
        tput cnorm 2>/dev/null
        printf '\r\033[J'
    fi
    if [ "$ETA_REMAINING" -gt 0 ]; then
        ETA_REMAINING=$(( ETA_REMAINING - eta )); [ "$ETA_REMAINING" -lt 0 ] && ETA_REMAINING=0
    fi
    if [ "$rc" -eq 0 ]; then
        _lr " ${C_OK}✔${CR} ${msg}" "${C_DIM}$(_fmt_secs $el)${CR} "
    else
        _lr " ${C_BAD}✘${CR} ${C_TITLE}${msg}${CR}" "${C_DIM}$(_fmt_secs $el)${CR} "
    fi
    return "$rc"
}

show_step_error() {
    local lines hint l
    lines=$(tr '\r' '\n' < "$INSTALL_LOG" 2>/dev/null | sed 's/\x1b\[[0-9;?]*[A-Za-z]//g' \
        | grep -iE '^(E:|W:|error|fatal|dpkg: error)|failed' | tail -n 4)
    [ -z "$lines" ] && lines=$(tr '\r' '\n' < "$INSTALL_LOG" 2>/dev/null | grep -v '^[[:space:]]*$' | tail -n 4)
    if grep -qiE 'Could not get lock|dpkg frontend lock|dpkg was interrupted' "$INSTALL_LOG" 2>/dev/null; then
        hint="Another apt or dpkg process is running, or one was interrupted. Wait a minute and retry."
    elif grep -qiE 'No space left on device' "$INSTALL_LOG" 2>/dev/null; then
        hint="The disk is full. Free some space and retry."
    elif grep -qiE 'Temporary failure resolving|Could not resolve|Network is unreachable|Connection timed out|Failed to connect' "$INSTALL_LOG" 2>/dev/null; then
        hint="The server could not reach the internet or DNS failed. Check the network and retry."
    elif grep -qiE 'Unable to locate package php|php[0-9.]+[^ ]* has no installation candidate' "$INSTALL_LOG" 2>/dev/null; then
        hint="This PHP package is not available from apt on this server."
    elif grep -qi 'mysql' "$INSTALL_LOG" 2>/dev/null && grep -qiE 'dpkg: error processing|Sub-process .* returned an error' "$INSTALL_LOG" 2>/dev/null; then
        hint="A previous MySQL install may have left broken files behind."
    elif grep -qiE 'too many certificates|DNS problem|Timeout during connect|Invalid response from' "$INSTALL_LOG" 2>/dev/null; then
        hint="Let's Encrypt could not verify the domain. Check that it points to this server and ports 80 and 443 are open."
    fi
    echo ""
    printf " ${C_BAD}┃ What went wrong${CR}\n"
    while IFS= read -r l; do
        printf " ${C_BAD}┃${CR} %s\n" "${l:0:$(( UI_W - 4 ))}"
    done <<< "${lines:-No output was captured.}"
    if [ -n "$hint" ]; then
        printf " ${C_BAD}┃${CR}\n ${C_BAD}┃ Likely cause${CR}\n ${C_BAD}┃${CR} %s\n" "$hint"
    fi
    printf " ${C_BAD}┃${CR} ${C_DIM}Full log: %s${CR}\n" "$INSTALL_LOG"
}

# ── Menu UI helpers ──────────────────────────────────────────
_measure() {
    TERM_W=$(stty size 2>/dev/null < /dev/tty | awk '{print $2}')
    [[ "$TERM_W" =~ ^[0-9]+$ ]] || TERM_W=$(tput cols 2>/dev/null)
    [[ "$TERM_W" =~ ^[0-9]+$ ]] || TERM_W=80
    [ "$TERM_W" -gt 80 ] && TERM_W=80
    [ "$TERM_W" -lt 40 ] && TERM_W=40
    UI_W=$TERM_W
}

C_BORDER=$'\033[1;36m'; C_TITLE=$'\033[1;37m'; C_DIM=$'\033[0;37m'
C_KEY=$'\033[1;33m';    C_TXT=$'\033[0;37m';   C_OK=$'\033[1;32m'
C_BAD=$'\033[1;31m';    C_WARN=$'\033[1;33m';  C_PROMPT=$'\033[1;36m'
CR=$'\033[0m'
_measure

_repeat() { local ch="$1" n="$2" out="" i; for ((i=0;i<n;i++)); do out+="$ch"; done; printf '%s' "$out"; }
# Horizontal rules (left-aligned, no right edge to drift)
_rule()   { printf "${C_BORDER}%s${CR}\n" "$(_repeat "─" "$UI_W")"; }
_drule()  { printf "${C_BORDER}%s${CR}\n" "$(_repeat "━" "$UI_W")"; }
_vlen()   { local LC_ALL=C.UTF-8 plain; plain=$(printf '%b' "$1" | sed 's/\x1b\[[0-9;]*m//g'); echo "${#plain}"; }
_lr() {
    local gap=$(( UI_W - $(_vlen "$1") - $(_vlen "$2") ))
    [ "$gap" -lt 1 ] && gap=1
    printf '%b%*s%b\n' "$1" "$gap" "" "$2"
}
_cols() {
    local out="" pad
    while [ $# -gt 1 ]; do
        out+="$2"
        if [ $# -gt 2 ]; then
            pad=$(( $1 - $(_vlen "$2") ))
            [ "$pad" -lt 1 ] && pad=1
            out+="$(printf '%*s' "$pad" "")"
        fi
        shift 2
    done
    printf '%b\n' "$out"
}
# Banner: rules + left-aligned title (no full box)
banner() {
    echo
    _lr " ${C_OK}▌${CR} ${C_TITLE}MIRZA PRO${CR}  ${1:-${C_DIM}VPN subscription bot${CR}}" "${2:-} "
    _drule
}
# Menu item row: [n] label  (left-aligned, no right border)
_mi()     { printf " ${C_KEY}[%s]${CR} ${C_TXT}%b${CR}\n" "$1" "$2"; }

# ── DNS auto-fix (used early, before any download) ───────────
RESOLV="/etc/resolv.conf"
DNS_SERVERS=("1.1.1.1" "8.8.8.8" "9.9.9.9")

dns_works() {
    getent hosts github.com       >/dev/null 2>&1 && return 0
    getent hosts api.telegram.org >/dev/null 2>&1 && return 0
    return 1
}

ensure_dns() {
    dns_works && return 0
    echo -e " ${C_WARN}!${CR} ${C_WARN}DNS resolution failed - configuring public DNS...${CR}"
    if [ -L "$RESOLV" ]; then
        rm -f "$RESOLV" 2>/dev/null
    elif [ -f "$RESOLV" ] && [ ! -f "${RESOLV}.mirza.bak" ]; then
        cp -a "$RESOLV" "${RESOLV}.mirza.bak" 2>/dev/null
    fi
    { local d; for d in "${DNS_SERVERS[@]}"; do echo "nameserver $d"; done; } > "$RESOLV" 2>/dev/null
    if command -v resolvectl >/dev/null 2>&1; then
        local ifc; ifc=$(ip route show default 2>/dev/null | awk '/default/{print $5; exit}')
        [ -n "$ifc" ] && resolvectl dns "$ifc" "${DNS_SERVERS[@]}" 2>/dev/null || true
    fi
    sleep 1
    dns_works && { echo -e " ${C_OK}●${CR} ${C_OK}DNS is now working.${CR}"; return 0; }
    echo -e " ${C_BAD}●${CR} ${C_BAD}DNS still failing after applying public resolvers.${CR}"
    return 1
}

# Ensure /usr/local/bin/mirza points at the master script
_link_mirza() {
    local master="$1" link="$2"
    chmod +x "$master" 2>/dev/null
    if [ ! -e "$link" ] || [ "$(readlink -f "$link" 2>/dev/null)" != "$(readlink -f "$master" 2>/dev/null)" ]; then
        ln -sf "$master" "$link"
    fi
    chmod +x "$link" 2>/dev/null
}

# Self-update: every run, fetch the latest script from GitHub, validate it,
# install it to /root/install.sh, link it into /usr/local/bin, and re-exec.
function self_update_script() {
    local MASTER_PATH="/root/install.sh"
    local BIN_LINK="/usr/local/bin/mirza"
    local URL="https://raw.githubusercontent.com/mahdiMGF2/mirzabot/main/install.sh"
    local TEMP_FILE="/tmp/mirzabot_update.sh"

    # Make sure DNS works before reaching GitHub
    ensure_dns >/dev/null 2>&1

    echo -e "\e[33mChecking for the latest script version...\033[0m"
    rm -f "$TEMP_FILE"
    curl -fsSL --max-time 15 -o "$TEMP_FILE" "$URL" 2>/dev/null \
        || wget -q -O "$TEMP_FILE" "$URL" 2>/dev/null

    # Normalize line endings so a CRLF download can never break bash
    [ -f "$TEMP_FILE" ] && sed -i 's/\r$//' "$TEMP_FILE"

    # Validate the download is a complete, valid bash script (not a 404/HTML/partial)
    local valid=0
    if [ -s "$TEMP_FILE" ] \
       && head -n1 "$TEMP_FILE" | grep -q '^#!/bin/bash' \
       && grep -q 'process_arguments' "$TEMP_FILE" \
       && bash -n "$TEMP_FILE" 2>/dev/null; then
        valid=1
    fi

    if [ "$valid" -ne 1 ]; then
        echo -e "\e[91mWarning: could not fetch a valid update (offline / bad download). Using current version.\033[0m"
        rm -f "$TEMP_FILE"
        if [ ! -f "$MASTER_PATH" ]; then
            echo -e "\e[91mCritical: cannot install the script for the first time without internet.\033[0m"
            exit 1
        fi
        _link_mirza "$MASTER_PATH" "$BIN_LINK"
        return 0
    fi

    local LOCAL_HASH REMOTE_HASH
    if [ -f "$MASTER_PATH" ]; then
        LOCAL_HASH=$(md5sum "$MASTER_PATH" | awk '{print $1}')
    else
        LOCAL_HASH="not_installed"
    fi
    REMOTE_HASH=$(md5sum "$TEMP_FILE" | awk '{print $1}')

    if [ "$LOCAL_HASH" != "$REMOTE_HASH" ]; then
        if [ "$LOCAL_HASH" = "not_installed" ]; then
            echo -e "\e[32mInstalling script to the system...\033[0m"
        else
            echo -e "\e[32mNew version found - updating...\033[0m"
        fi
        install -m 0755 "$TEMP_FILE" "$MASTER_PATH" 2>/dev/null || { mv "$TEMP_FILE" "$MASTER_PATH"; chmod +x "$MASTER_PATH"; }
        rm -f "$TEMP_FILE"
        _link_mirza "$MASTER_PATH" "$BIN_LINK"
        echo -e "\e[32mUpdated. Restarting with the latest version...\033[0m"
        sleep 1
        exec bash "$MASTER_PATH" "$@"
    fi

    # Already up to date - just make sure it is linked under /usr/local/bin
    rm -f "$TEMP_FILE"
    _link_mirza "$MASTER_PATH" "$BIN_LINK"
    echo -e "\e[32mScript is up to date.\033[0m"
}
self_update_script "$@"

# ── Repo / paths ─────────────────────────────────────────────
BOT_DIR_DEFAULT="/var/www/html/mirzaprobotconfig"
CONFIG_FILE_DEFAULT="$BOT_DIR_DEFAULT/config.php"
GIT_REPO="mahdiMGF2/mirzabot"
LATEST_CACHE="/tmp/.mirza_latest_version"
IP_CACHE="/tmp/.mirza_server_ip"

# ── Resumable-install state engine ───────────────────────────
# Survives reboots / network drops. Lets a failed install resume
# from the last completed phase instead of starting from scratch.
STATE_DIR="/root/confmirza"
STATE_FILE="$STATE_DIR/.mirza_install_state"

state_init() {
    mkdir -p "$STATE_DIR" 2>/dev/null
    if [ ! -f "$STATE_FILE" ]; then
        : > "$STATE_FILE"
        chmod 600 "$STATE_FILE" 2>/dev/null
    fi
}

# state_set KEY VALUE  -> store a persistent answer (domain/token/etc.)
state_set() {
    state_init
    sed -i "/^$1=/d" "$STATE_FILE" 2>/dev/null
    printf '%s=%s\n' "$1" "$2" >> "$STATE_FILE"
}

# state_get KEY -> echo the stored value (empty if missing)
state_get() {
    [ -f "$STATE_FILE" ] || return 0
    grep -E "^$1=" "$STATE_FILE" 2>/dev/null | tail -1 | cut -d= -f2-
}

# phase_done NAME -> 0 if the phase already completed successfully
phase_done() {
    [ -f "$STATE_FILE" ] && grep -qxF "PHASE:$1" "$STATE_FILE" 2>/dev/null
}

# mark_phase NAME -> record a phase as completed
mark_phase() {
    state_init
    grep -qxF "PHASE:$1" "$STATE_FILE" 2>/dev/null || echo "PHASE:$1" >> "$STATE_FILE"
}

# has_resumable_state -> 0 if an unfinished install is on disk
has_resumable_state() {
    [ -f "$STATE_FILE" ] || return 1
    { grep -q '^PHASE:' "$STATE_FILE" 2>/dev/null || grep -q '^STARTED=' "$STATE_FILE" 2>/dev/null; } \
        && ! phase_done COMPLETE
}

state_clear() { rm -f "$STATE_FILE" 2>/dev/null; }

# ── apt/dpkg recovery ────────────────────────────────────────
# A previous interrupted apt run (or Ubuntu's background
# unattended-upgrades) can hold the dpkg lock, making the next
# apt command hang forever. This waits for any LIVE apt to finish,
# clears locks left by a DEAD process, then repairs dpkg state.
apt_recover() {
    local i=0
    # 1) If a real apt/dpkg is running (e.g. unattended-upgrades), wait for it
    if pgrep -x 'apt|apt-get|dpkg|unattended-upgr' >/dev/null 2>&1; then
        echo "Another apt/dpkg process is running; waiting up to 3 minutes for it to finish..."
        while pgrep -x 'apt|apt-get|dpkg|unattended-upgr' >/dev/null 2>&1; do
            sleep 3; i=$((i + 1)); [ "$i" -ge 60 ] && break
        done
    fi
    # 2) Disable Ubuntu auto-update timers during install so they cannot re-grab the lock
    systemctl stop apt-daily.service apt-daily-upgrade.service \
        unattended-upgrades.service >/dev/null 2>&1
    systemctl stop apt-daily.timer apt-daily-upgrade.timer >/dev/null 2>&1
    # 3) No live holder now -> remove stale locks left by the crashed run
    if ! pgrep -x 'apt|apt-get|dpkg|unattended-upgr' >/dev/null 2>&1; then
        rm -f /var/lib/apt/lists/lock /var/cache/apt/archives/lock \
              /var/lib/dpkg/lock /var/lib/dpkg/lock-frontend 2>/dev/null
    fi
    # 4) Repair any half-configured packages from the interruption
    DEBIAN_FRONTEND=noninteractive dpkg --configure -a >/dev/null 2>&1
    return 0
}
export -f apt_recover

OS_ID=""; OS_VERSION_ID=""; OS_CODENAME=""; OS_PRETTY=""
detect_os() {
    [ -n "$OS_ID" ] && return 0
    [ -f /etc/os-release ] || return 1
    local fields
    fields=$(. /etc/os-release 2>/dev/null; printf '%s\t%s\t%s\t%s' \
        "${ID:-}" "${VERSION_ID:-}" "${UBUNTU_CODENAME:-${VERSION_CODENAME:-}}" "${PRETTY_NAME:-unknown}")
    IFS=$'\t' read -r OS_ID OS_VERSION_ID OS_CODENAME OS_PRETTY <<< "$fields"
    return 0
}
export -f detect_os

os_major() {
    detect_os
    local m="${OS_VERSION_ID%%.*}"
    case "$m" in ''|*[!0-9]*) echo 0 ;; *) echo "$m" ;; esac
}
export -f os_major

# Database engine per distro: Ubuntu ships MySQL, Debian ships MariaDB.
# Exported so run_step's `bash -c` subshells see them.
set_db_vars() {
    detect_os
    if [ "$OS_ID" = "debian" ]; then
        DB_PKG=mariadb-server; DB_SERVICE=mariadb
    else
        DB_PKG=mysql-server; DB_SERVICE=mysql
    fi
    export DB_PKG DB_SERVICE
}

# Check a repo publishes the given series. $1=series, $2=repo base (default: ondrej PPA).
php_ppa_has_series() {
    [ -n "$1" ] || return 1
    local base="${2:-https://ppa.launchpadcontent.net/ondrej/php/ubuntu}" try
    for try in 1 2 3; do
        curl -fsSL --max-time 10 -o /dev/null \
            "$base/dists/$1/Release" 2>/dev/null && return 0
        sleep 2
    done
    return 1
}
export -f php_ppa_has_series

php_repo_disable() {
    local f n=0
    for f in /etc/apt/sources.list.d/*ondrej*php*.sources /etc/apt/sources.list.d/*ondrej*php*.list \
             /etc/apt/sources.list.d/*sury*php*.sources /etc/apt/sources.list.d/*sury*php*.list; do
        [ -f "$f" ] || continue
        mv -f "$f" "$f.disabled-by-mirza" && n=$((n + 1))
    done
    [ "$n" -gt 0 ]
}
export -f php_repo_disable

setup_php_repo() {
    detect_os
    export DEBIAN_FRONTEND=noninteractive
    if [ "$OS_ID" = "debian" ]; then
        # On Debian: packages.sury.org (same maintainer as the Ubuntu PPA,
        # which builds PHP 8.5 for bookworm + trixie). PPAs don't exist there.
        apt-get update -o DPkg::Lock::Timeout=180 >/dev/null 2>&1
        apt-get install -y ca-certificates curl gnupg -o DPkg::Lock::Timeout=180 || return 1
        # Does sury actually build for this release? If not, fall back to the
        # PHP shipped with this Debian release.
        if [ -n "$OS_CODENAME" ] && ! php_ppa_has_series "$OS_CODENAME" https://packages.sury.org/php; then
            echo "sury/php publishes no packages for '$OS_CODENAME' - disabling the repo and using the PHP shipped with $OS_PRETTY."
            php_repo_disable || echo "Warning: no sury/php source file found to disable."
            return 0
        fi
        mkdir -p /etc/apt/keyrings
        curl -fsSL --max-time 30 https://packages.sury.org/php/apt.gpg \
            -o /etc/apt/keyrings/deb.sury.org-php.gpg || return 1
        printf 'Types: deb\nURIs: https://packages.sury.org/php/\nSuites: %s\nComponents: main\nSigned-By: /etc/apt/keyrings/deb.sury.org-php.gpg\n' "${OS_CODENAME:-bookworm}" \
            > /etc/apt/sources.list.d/sury-php.sources
        return 0
    fi
    # add-apt-repository lives in software-properties-common - minimal cloud
    # images (and the 26.04 minimal image in particular) do not ship it.
    if ! command -v add-apt-repository >/dev/null 2>&1; then
        apt-get update -o DPkg::Lock::Timeout=180 >/dev/null 2>&1
        apt-get install -y software-properties-common ca-certificates curl gnupg \
            -o DPkg::Lock::Timeout=180 || return 1
    fi
    add-apt-repository -y ppa:ondrej/php || \
        LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php || return 1

    # Does the PPA actually build for this release? Pinning to an older series
    if [ -n "$OS_CODENAME" ] && ! php_ppa_has_series "$OS_CODENAME"; then
        echo "ondrej/php publishes no packages for '$OS_CODENAME' - disabling the PPA and using the PHP shipped with $OS_PRETTY."
        php_repo_disable || echo "Warning: no ondrej/php source file found to disable."
    fi
    return 0
}
export -f setup_php_repo

export PHP_TARGET_VER="8.5"
export PHP_MIN_VERSION="8.5.12"
[ "$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;' 2>/dev/null)" = "8.4" ] && export PHP_TARGET_VER="8.4" PHP_MIN_VERSION="8.4.27"

# 0 when apt has an installable candidate for this package.
_apt_has_candidate() {
    local cand
    cand=$(apt-cache policy "$1" 2>/dev/null | awk '/Candidate:/{print $2; exit}')
    [ -n "$cand" ] && [ "$cand" != "(none)" ]
}
export -f _apt_has_candidate

php_candidate_ok() {
    local cand
    cand=$(apt-cache policy "php$PHP_TARGET_VER" 2>/dev/null | awk '/Candidate:/{print $2; exit}')
    echo "php$PHP_TARGET_VER candidate: ${cand:-none} (required >= $PHP_MIN_VERSION)"
    [ -n "$cand" ] && [ "$cand" != "(none)" ] || return 1
    _apt_has_candidate "libapache2-mod-php$PHP_TARGET_VER" || return 1
    dpkg --compare-versions "${cand#*:}" ge "$PHP_MIN_VERSION"
}
export -f php_candidate_ok

select_php_target() {
    if PHP_TARGET_VER=8.5 PHP_MIN_VERSION=8.5.12 php_candidate_ok >/dev/null; then
        export PHP_TARGET_VER=8.5 PHP_MIN_VERSION=8.5.12
    elif PHP_TARGET_VER=8.4 PHP_MIN_VERSION=8.4.27 php_candidate_ok >/dev/null; then
        export PHP_TARGET_VER=8.4 PHP_MIN_VERSION=8.4.27
    fi
}

php_version_ok() {
    "php$PHP_TARGET_VER" -r 'echo PHP_VERSION, PHP_EOL; exit(version_compare(PHP_VERSION, getenv("PHP_MIN_VERSION"), ">=") ? 0 : 1);'
}
export -f php_version_ok

_apache_php_ver() {
    local mod
    [ -d /etc/apache2/mods-enabled ] || return 0
    mod=$(ls /etc/apache2/mods-enabled | sed -n 's/^php\([0-9.]*\)\.load$/\1/p' | head -1)
    dpkg-query -W -f='${Version}' "libapache2-mod-php${mod:-none}" 2>/dev/null
}
export -f _apache_php_ver

upgrade_php_if_needed() {
    local V="$PHP_TARGET_VER" current apache_ver apache_short cand cand_short source
    local old_pkgs old_vers old_count pma_installed _ans activate_cmd n=3 now_cli now_apache
    detect_os
    source="Ubuntu archive"; [ "$OS_ID" = "debian" ] && source="sury/php archive"
    current=$(php -r 'echo PHP_VERSION;' 2>/dev/null)
    apache_ver=$(_apache_php_ver)
    apache_short=${apache_ver#*:}; apache_short=${apache_short%%[-+~]*}
    if [ -n "$current" ] && dpkg --compare-versions "$current" ge "$PHP_MIN_VERSION" \
        && { [ ! -d /etc/apache2/mods-enabled ] || { [ -n "$apache_ver" ] && dpkg --compare-versions "${apache_ver#*:}" ge "$PHP_MIN_VERSION"; }; }; then
        _kv "PHP" "${C_OK}●${CR} ${current} ${C_DIM}is up to date (${PHP_MIN_VERSION}+)${CR}"
        return 0
    fi

    run_step "Adding PHP repository" \
        "setup_php_repo && apt-get update --allow-releaseinfo-change -o DPkg::Lock::Timeout=180" \
        || { show_step_error; return 1; }
    select_php_target
    V="$PHP_TARGET_VER"
    cand=$(apt-cache policy "php$V" 2>/dev/null | awk '/Candidate:/{print $2; exit}')
    [ "$cand" = "(none)" ] && cand=""
    cand_short=${cand#*:}; cand_short=${cand_short%%[-+~]*}
    case "$OS_ID" in
        debian) apt-cache policy "php$V" 2>/dev/null | grep -q 'sury' && source="sury/php" ;;
        *)      apt-cache policy "php$V" 2>/dev/null | grep -q 'ondrej' && source="ondrej/php" ;;
    esac
    old_pkgs=$(dpkg-query -W -f='${db:Status-Abbrev} ${Package}\n' 2>/dev/null \
        | awk -v keep="$V" '$1 ~ /^.i/ && match($2, /php[0-9]+\.[0-9]+/) { if (substr($2, RSTART + 3, RLENGTH - 3) != keep) print $2 }')
    old_vers=$(echo "$old_pkgs" | grep -oE 'php[0-9]+\.[0-9]+' | sort -u | tr '\n' ' ')
    old_count=$(echo "$old_pkgs" | grep -c .)

    now_cli="${C_BAD}${current:-none}${CR}"
    [ -n "$current" ] && dpkg --compare-versions "$current" ge "$PHP_MIN_VERSION" && now_cli="${C_OK}${current}${CR}"
    now_apache="${C_BAD}${apache_short:-none}${CR}"
    [ -n "$apache_ver" ] && dpkg --compare-versions "${apache_ver#*:}" ge "$PHP_MIN_VERSION" && now_apache="${C_OK}${apache_short}${CR}"
    echo ""
    if [ "$UI_W" -ge 80 ]; then
        _cols 41 " ${C_BORDER}NOW${CR}" 39 "${C_BORDER}AFTER${CR}"
        _cols 41 " ${C_DIM}CLI     ${CR} ${now_cli}" 39 "${C_DIM}CLI     ${CR} ${C_OK}${cand_short:-none}${CR}"
        _cols 41 " ${C_DIM}Apache  ${CR} ${now_apache}" 39 "${C_DIM}Apache  ${CR} ${C_OK}${cand_short:-none}${CR}"
        _cols 41 "" 39 "${C_DIM}Source  ${CR} ${source}"
    else
        _kv "CLI now" "$now_cli"
        _kv "Apache now" "$now_apache"
        _kv "After" "${C_OK}${cand_short:-none}${CR} ${C_DIM}from ${source}${CR}"
    fi
    if [ -z "$cand" ] || ! _apt_has_candidate "libapache2-mod-php$V" || ! dpkg --compare-versions "${cand#*:}" ge "$PHP_MIN_VERSION"; then
        echo ""
        printf " ${C_BAD}▲ PHP %s+ is not available from apt on this server. Nothing was changed.${CR}\n" "$PHP_MIN_VERSION"
        return 1
    fi
    _rule
    printf " ${C_TITLE}This will${CR}\n"
    printf "   ${C_KEY}1${CR}  Install PHP %s with the modules the bot uses\n" "$V"
    printf "      ${C_DIM}mysql mbstring zip gd curl intl xml bcmath soap ssh2${CR}\n"
    printf "   ${C_KEY}2${CR}  Switch Apache and the ${C_TITLE}php${CR} command to %s\n" "$V"
    if [ -n "$old_pkgs" ]; then
        printf "   ${C_KEY}3${CR}  Remove %s ${C_DIM}(%s packages). phpMyAdmin is kept.${CR}\n" "${old_vers% }" "$old_count"
        n=4
    fi
    printf "   ${C_KEY}%s${CR}  Restart Apache. The bot is back in about a minute.\n" "$n"
    echo ""
    printf " ${C_DIM}Your bot files, config.php and database are not touched.${CR}\n"
    _rule
    printf " ${C_PROMPT}❯${CR} Upgrade PHP now? ${C_DIM}[y/N]${CR}: "
    read -r _ans
    if [[ ! "$_ans" =~ ^[Yy]$ ]]; then
        printf " ${C_DIM}PHP was not changed.${CR}\n"
        return 1
    fi
    echo ""

    pma_installed=0
    _pkg_installed phpmyadmin && pma_installed=1

    run_step "Installing PHP ${V} and modules" \
        "DEBIAN_FRONTEND=noninteractive apt-get install -y -o DPkg::Lock::Timeout=180 php${V} php${V}-cli php${V}-fpm php${V}-mysql libapache2-mod-php${V} php${V}-mbstring php${V}-zip php${V}-gd php${V}-curl php${V}-intl php${V}-xml php${V}-bcmath" \
        || { show_step_error; return 1; }
    run_step "Verifying installed PHP is ${PHP_MIN_VERSION}+" "php_version_ok" \
        || { show_step_error; return 1; }
    run_step "Installing extra modules (php-soap, php-ssh2)" \
        "DEBIAN_FRONTEND=noninteractive apt-get install -y -o DPkg::Lock::Timeout=180 php${V}-soap php${V}-ssh2" \
        || show_step_error

    activate_cmd="a2dismod ${old_vers} mpm_event mpm_worker 2>/dev/null; a2enmod php${V} mpm_prefork && update-alternatives --set php /usr/bin/php${V} && systemctl restart apache2"
    run_step "Setting PHP ${V} as the active version" "$activate_cmd" \
        || { show_step_error; return 1; }

    if [ -n "$old_pkgs" ]; then
        run_step "Removing old PHP (${old_vers% })" \
            "DEBIAN_FRONTEND=noninteractive apt-get remove -y -o DPkg::Lock::Timeout=180 $(echo $old_pkgs)" \
            || show_step_error
    fi
    if [ "$pma_installed" = 1 ] && ! _pkg_installed phpmyadmin; then
        run_step "Reinstalling phpMyAdmin" "DEBIAN_FRONTEND=noninteractive apt-get install -y phpmyadmin" \
            || show_step_error
        run_step "Re-applying PHP ${V} as the active version" "$activate_cmd" \
            || { show_step_error; return 1; }
    fi
    echo ""
    printf " ${C_OK}✔ PHP %s is now active.${CR}\n" "$(php -r 'echo PHP_VERSION;' 2>/dev/null)"
    return 0
}

# Configure MySQL root login (all output captured by run_step's log).
setup_mysql_root() {
    sudo mkdir -p /root/confmirza || return 1
    sudo chmod 700 /root/confmirza || return 1
    touch /root/confmirza/dbrootmirza.txt || return 1
    sudo chmod 600 /root/confmirza/dbrootmirza.txt || return 1
    local randomdbpasstxt passs userrr RANDOM_NUMBER
    randomdbpasstxt=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)
    RANDOM_NUMBER=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | cut -c1-12)
    echo "\$user = 'root';"               >> /root/confmirza/dbrootmirza.txt
    echo "\$pass = '${randomdbpasstxt}';" >> /root/confmirza/dbrootmirza.txt
    echo "\$path = '${RANDOM_NUMBER}';"   >> /root/confmirza/dbrootmirza.txt
    passs=$(grep '$pass' /root/confmirza/dbrootmirza.txt | cut -d"'" -f2)
    userrr=$(grep '$user' /root/confmirza/dbrootmirza.txt | cut -d"'" -f2)
    local alter_ok=0
    if sudo mysql -u "$userrr" -p"$passs" -e "alter user '$userrr'@'localhost' identified with mysql_native_password by '$passs';FLUSH PRIVILEGES;"; then
        alter_ok=1
    elif sudo mysql -e "alter user '$userrr'@'localhost' identified with mysql_native_password by '$passs';FLUSH PRIVILEGES;"; then
        alter_ok=1
    elif sudo mysql -e "alter user '$userrr'@'localhost' identified with caching_sha2_password by '$passs';FLUSH PRIVILEGES;"; then
        alter_ok=1
    elif sudo mysql -e "alter user '$userrr'@'localhost' identified by '$passs';FLUSH PRIVILEGES;"; then
        alter_ok=1
    fi
    if [ "$alter_ok" -eq 1 ]; then
        echo "SELECT 1" | mysql -u"$userrr" -p"$passs" >/dev/null 2>&1 && return 0
    fi

    local dropin_dir=""
    local d
    for d in /etc/mysql/mysql.conf.d /etc/mysql/mariadb.conf.d /etc/mysql/conf.d; do
        [ -d "$d" ] && { dropin_dir="$d"; break; }
    done
    [ -n "$dropin_dir" ] || return 1
    local dropin="$dropin_dir/zz-mirza-recovery.cnf"
    printf '[mysqld]\nskip-grant-tables\n' | sudo tee "$dropin" >/dev/null || return 1
    sudo systemctl restart "${DB_SERVICE:-mysql}"
    sudo mysql <<EOF
FLUSH PRIVILEGES;
DROP USER IF EXISTS 'root'@'localhost';
CREATE USER 'root'@'localhost' IDENTIFIED BY '${passs}';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF
    sudo rm -f "$dropin"
    # Older installer versions appended the option to mysqld.cnf directly.
    sudo sed -i '/^skip-grant-tables/d' /etc/mysql/mysql.conf.d/mysqld.cnf 2>/dev/null
    sudo systemctl restart "${DB_SERVICE:-mysql}"
    echo "SELECT 1" | mysql -u"$userrr" -p"$passs" >/dev/null 2>&1 || return 1
    return 0
}
export -f setup_mysql_root

# Install Composer to /usr/local/bin/composer when it is not already available.
# The installer is verified against the official signature before it is run.
ensure_composer() {
    if command -v composer >/dev/null 2>&1; then
        return 0
    fi

    local php_bin setup expected actual
    php_bin="$(command -v php)" || return 1
    setup="$(mktemp /tmp/composer-setup.XXXXXX.php)"

    expected="$("$php_bin" -r "echo @file_get_contents('https://composer.github.io/installer.sig');" 2>/dev/null | tr -d '[:space:]')"
    if ! "$php_bin" -r "exit(@copy('https://getcomposer.org/installer', '$setup') ? 0 : 1);"; then
        rm -f "$setup"
        echo "Failed to download the Composer installer." >&2
        return 1
    fi

    actual="$("$php_bin" -r "echo hash_file('sha384', '$setup');" 2>/dev/null | tr -d '[:space:]')"
    if [ -z "$expected" ] || [ "$expected" != "$actual" ]; then
        rm -f "$setup"
        echo "Composer installer signature mismatch - refusing to run it." >&2
        return 1
    fi

    "$php_bin" "$setup" --quiet --install-dir=/usr/local/bin --filename=composer
    local rc=$?
    rm -f "$setup"
    [ "$rc" -eq 0 ] && command -v composer >/dev/null 2>&1
}
export -f ensure_composer

# Detect the active CLI PHP major.minor (e.g. 8.5). Empty on failure.
active_php_ver() {
    php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null
}
export -f active_php_ver

ensure_php_exts_for_composer() {
    local ver pkgs
    ver="$(active_php_ver)"
    if [ -z "$ver" ]; then
        echo "PHP CLI not found - cannot install required extensions." >&2
        return 1
    fi

    if php -m 2>/dev/null | grep -qi '^mbstring$' \
        && php -m 2>/dev/null | grep -qi '^dom$' \
        && php -m 2>/dev/null | grep -qi '^pdo_mysql$'; then
        return 0
    fi

    pkgs="php${ver}-mysql php${ver}-mbstring php${ver}-xml php${ver}-zip php${ver}-gd php${ver}-curl php${ver}-intl php${ver}-bcmath"
    echo "Ensuring PHP ${ver} extensions for Composer: ${pkgs}"
    DEBIAN_FRONTEND=noninteractive apt-get install -y $pkgs || {
        echo "Failed to install PHP ${ver} extensions required by Composer." >&2
        return 1
    }

    if ! php -m 2>/dev/null | grep -qi '^mbstring$' \
        || ! php -m 2>/dev/null | grep -qi '^dom$' \
        || ! php -m 2>/dev/null | grep -qi '^pdo_mysql$'; then
        echo "PHP ${ver} is missing mbstring, dom and/or pdo_mysql after package install." >&2
        echo "Run: php -m | grep -E 'mbstring|dom|pdo_mysql'  and php --ini" >&2
        return 1
    fi
    return 0
}
export -f ensure_php_exts_for_composer

# Build vendor/ from composer.json + composer.lock. vendor/ is not shipped in the
# release archive, so this must run on every install, update and migration.
install_php_deps() {
    local dir="$1"

    if [ ! -f "$dir/composer.json" ]; then
        echo "No composer.json in $dir - skipping dependency installation."
        return 0
    fi

    ensure_php_exts_for_composer || return 1
    ensure_composer || return 1

    COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1 \
        composer install --working-dir="$dir" \
        --no-dev --optimize-autoloader --prefer-dist --no-progress || return 1

    if [ ! -f "$dir/vendor/autoload.php" ]; then
        echo "composer install finished but $dir/vendor/autoload.php is missing." >&2
        return 1
    fi

    chown -R www-data:www-data "$dir/vendor" 2>/dev/null
    return 0
}
export -f install_php_deps

# True if a package is installed and configured.
_pkg_installed() { dpkg-query -W -f='${Status}' "$1" 2>/dev/null | grep -q 'install ok installed'; }

_pkg_installed_glob() {
    dpkg-query -W -f='${Package} ${Status}\n' "$1" 2>/dev/null | grep -q 'install ok installed'
}

_crontab_present() {
    command -v crontab >/dev/null 2>&1 || [ -x /usr/bin/crontab ] || [ -x /usr/sbin/crontab ]
}

_cron_unit_name() {
    if [ -f /lib/systemd/system/cron.service ] || [ -f /usr/lib/systemd/system/cron.service ]; then
        echo cron
    elif [ -f /lib/systemd/system/crond.service ] || [ -f /usr/lib/systemd/system/crond.service ]; then
        echo crond
    fi
}

_cron_daemon_active() {
    local unit
    unit="$(_cron_unit_name)"
    if [ -n "$unit" ] && systemctl is-active --quiet "$unit" 2>/dev/null; then
        return 0
    fi
    pgrep -x cron >/dev/null 2>&1 || pgrep -x crond >/dev/null 2>&1
}

# Install cron when crontab/daemon is missing, then enable + start it and
# allow www-data to register jobs (PHP activecron() uses crontab as www-data).
ensure_cron() {
    export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin${PATH:+:$PATH}"
    hash -r 2>/dev/null || true

    if ! _crontab_present; then
        DEBIAN_FRONTEND=noninteractive apt-get install -y -o DPkg::Lock::Timeout=180 cron \
            || DEBIAN_FRONTEND=noninteractive apt-get install -y -o DPkg::Lock::Timeout=180 cronie \
            || return 1
        hash -r 2>/dev/null || true
        systemctl daemon-reload 2>/dev/null || true
    elif ! _cron_daemon_active; then
        if ! dpkg-query -W -f='${Status}' cron 2>/dev/null | grep -q 'install ok installed' \
            && ! dpkg-query -W -f='${Status}' cronie 2>/dev/null | grep -q 'install ok installed'; then
            DEBIAN_FRONTEND=noninteractive apt-get install -y -o DPkg::Lock::Timeout=180 cron \
                || DEBIAN_FRONTEND=noninteractive apt-get install -y -o DPkg::Lock::Timeout=180 cronie \
                || return 1
            hash -r 2>/dev/null || true
            systemctl daemon-reload 2>/dev/null || true
        fi
    fi

    if [ -f /etc/cron.allow ]; then
        grep -qx 'www-data' /etc/cron.allow 2>/dev/null || echo 'www-data' >> /etc/cron.allow
    fi

    local unit
    unit="$(_cron_unit_name)"
    if [ -n "$unit" ]; then
        systemctl unmask "$unit" >/dev/null 2>&1 || true
        systemctl enable "$unit" >/dev/null 2>&1 || true
        systemctl start "$unit" || return 1
        if ! systemctl is-active --quiet "$unit"; then
            sleep 1
            systemctl start "$unit" || return 1
            systemctl is-active --quiet "$unit" || return 1
        fi
    else
        service cron start 2>/dev/null || service crond start 2>/dev/null || true
        _cron_daemon_active || return 1
    fi

    _crontab_present || return 1
}
export -f _crontab_present _cron_unit_name _cron_daemon_active ensure_cron

# Refuse to install on a server that already has conflicting software.
# Only runs on a brand-new install (never on resume / Mirza's own partial state).
precheck_fresh_server() {
    local found=()
    _pkg_installed apache2 && found+=("apache2 (web server)")
    { _pkg_installed nginx || _pkg_installed nginx-core || _pkg_installed nginx-full; } && found+=("nginx (web server)")
    { _pkg_installed mysql-server || _pkg_installed_glob 'mysql-server-[0-9]*'; } && found+=("mysql-server")
    { _pkg_installed mariadb-server || _pkg_installed_glob 'mariadb-server-[0-9]*'; } && found+=("mariadb-server")
    _pkg_installed phpmyadmin && found+=("phpMyAdmin")
    # Known VPN panels
    { [ -d /opt/marzban ] || [ -d /var/lib/marzban ]; } && found+=("Marzban panel")
    { [ -d /opt/hiddify-manager ] || [ -d /opt/hiddify-config ]; } && found+=("Hiddify panel")

    if [ ${#found[@]} -gt 0 ]; then
        clear
        banner
        _sec "Server is not clean"
        printf " ${C_BAD}●${CR} ${C_BAD}This installer needs a fresh server with no other software installed.${CR}\n"
        printf " ${C_DIM}Detected conflicting components:${CR}\n"
        local f
        for f in "${found[@]}"; do printf "      ${C_WARN}-${CR} ${C_TXT}%s${CR}\n" "$f"; done
        echo ""
        printf " ${C_TXT}Use a clean Ubuntu 22.04/24.04/26.04 or Debian 12/13 server (no web server, database, or panel)${CR}\n"
        printf " ${C_TXT}or reinstall the OS, then run the installer again.${CR}\n"
        return 1
    fi
    return 0
}


repair_mysql() {
    export DEBIAN_FRONTEND=noninteractive
    systemctl stop "${DB_SERVICE:-mysql}" 2>/dev/null
    # 1) Gentle fix first
    dpkg --configure -a >/dev/null 2>&1
    apt-get install -f -y >/dev/null 2>&1
    if dpkg-query -W -f='${Package} ${Status}\n' 'mysql-server-[0-9]*' 'mariadb-server-[0-9]*' 2>/dev/null | grep -q 'install ok installed'; then
        return 0
    fi
    # 2) Hard reset: purge MySQL/MariaDB and wipe its (empty) data dir, then reinstall fresh
    apt-get purge -y 'mysql-server*' 'mysql-client*' 'mysql-community*' 'mariadb-server*' mysql-common >/dev/null 2>&1
    apt-get autoremove -y >/dev/null 2>&1
    rm -rf /var/lib/mysql /var/log/mysql /etc/mysql
    dpkg --configure -a >/dev/null 2>&1
    apt-get update --allow-releaseinfo-change >/dev/null 2>&1
    return 0
}
export -f repair_mysql


install_pause() {
    local where="$1" choice retry=0
    [ "${LAST_STEP_RC:-0}" -ne 0 ] && [ -n "$LAST_STEP_CMD" ] && retry=1
    while true; do
        echo ""
        printf " ${C_OK}●${CR} ${C_DIM}Completed steps are saved. Nothing has to run twice.${CR}\n\n"
        if [ "$retry" = 1 ]; then
            printf "   ${C_KEY}[R]${CR} Retry    ${C_KEY}[L]${CR} Show full log    ${C_KEY}[Q]${CR} Quit and resume later\n\n"
            printf " ${C_PROMPT}❯${CR} Choose ${C_DIM}[R/l/q]${CR}: "
        else
            printf "   ${C_KEY}[L]${CR} Show full log    ${C_KEY}[Q]${CR} Quit and resume later\n\n"
            printf " ${C_PROMPT}❯${CR} Choose ${C_DIM}[l/Q]${CR}: "
        fi
        read -r choice || choice=q
        [ -z "$choice" ] && { [ "$retry" = 1 ] && choice=r || choice=q; }
        case "$choice" in
            [Rr])
                [ "$retry" = 1 ] || continue
                echo ""
                STEP_NO=$((STEP_NO - 1))
                run_step "$LAST_STEP_MSG" "$LAST_STEP_CMD" && return 0
                show_step_error
                ;;
            [Ll])
                if command -v less >/dev/null 2>&1; then less -R +G "$INSTALL_LOG"; else tail -n 60 "$INSTALL_LOG"; fi
                ;;
            *) break ;;
        esac
    done
    echo ""
    _drule
    printf " ${C_WARN}●${CR} ${C_TITLE}Installation paused${CR} ${C_DIM}(%s)${CR}\n" "$where"
    printf " ${C_TXT}Run it again to continue from this step:${CR} ${C_KEY}mirza install${CR}\n"
    printf " ${C_DIM}Values you already entered (domain, token, ...) will not be asked again.${CR}\n"
    _drule
    echo ""
    exit 1
}

# Colored status dot
_dot() {
    case "$1" in
        ok)   printf "${C_OK}●${CR}"  ;;
        bad)  printf "${C_BAD}●${CR}" ;;
        warn) printf "${C_WARN}●${CR}";;
        *)    printf "${C_DIM}●${CR}" ;;
    esac
}

# Dashboard section header + key/value row helpers
_sec() { printf "\n ${C_BORDER}%s${CR}\n" "${1^^}"; }
_kv()  { printf " ${C_DIM}%-12s${CR} %b${CR}\n" "$1" "$2"; }

# Read the installed version from the source 'version' file
get_installed_version() {
    if [ -f "$BOT_DIR_DEFAULT/version" ]; then
        tr -d ' \t\r\n' < "$BOT_DIR_DEFAULT/version"
    else
        echo ""
    fi
}

# Get latest version (newest git tag) from GitHub, cached for 1 hour
get_latest_version() {
    if [ -f "$LATEST_CACHE" ] && [ $(( $(date +%s) - $(stat -c %Y "$LATEST_CACHE" 2>/dev/null || echo 0) )) -lt 3600 ]; then
        cat "$LATEST_CACHE"
        return
    fi
    local tags v
    tags=$(curl -fsSL --max-time 6 "https://api.github.com/repos/${GIT_REPO}/tags" 2>/dev/null)
    if [ -n "$tags" ]; then
        if command -v jq >/dev/null 2>&1; then
            v=$(echo "$tags" | jq -r '.[].name' 2>/dev/null | sort -V | tail -1)
        else
            v=$(echo "$tags" | grep -oE '"name"[[:space:]]*:[[:space:]]*"[^"]+"' | sed -E 's/.*"([^"]+)".*/\1/' | sort -V | tail -1)
        fi
    fi
    if [ -n "$v" ]; then
        echo "$v" > "$LATEST_CACHE"
        echo "$v"
    fi
}

# Print all release tags, newest first (one per line)
list_tags_desc() {
    local tags
    tags=$(curl -fsSL --max-time 8 "https://api.github.com/repos/${GIT_REPO}/tags" 2>/dev/null)
    [ -z "$tags" ] && return 1
    if command -v jq >/dev/null 2>&1; then
        echo "$tags" | jq -r '.[].name' 2>/dev/null | sort -Vr
    else
        echo "$tags" | grep -oE '"name"[[:space:]]*:[[:space:]]*"[^"]+"' | sed -E 's/.*"([^"]+)".*/\1/' | sort -Vr
    fi
}

# Choose which source to download.
# Sets globals: SRC_ZIP_URL, SRC_LABEL
# Honors flags ARG_CHANNEL (beta|release|auto) and ARG_VERSION (tag) for non-interactive use.
# Returns: 0 = chosen, 1 = error, 2 = back to menu
choose_source() {
    SRC_ZIP_URL=""; SRC_LABEL=""
    local beta="https://github.com/${GIT_REPO}/archive/refs/heads/main.zip"
    local tagbase="https://github.com/${GIT_REPO}/archive/refs/tags"

    # ── Non-interactive (flags) ──────────────────────────────
    if [ -n "$ARG_VERSION" ]; then
        # Verify the requested tag actually exists (when the list is reachable)
        local _avail; _avail=$(list_tags_desc)
        if [ -n "$_avail" ] && ! echo "$_avail" | grep -qx "$ARG_VERSION"; then
            echo -e " ${C_BAD}●${CR} ${C_BAD}Version '${ARG_VERSION}' not found.${CR}"
            echo -e " ${C_DIM}Available:${CR} $(echo "$_avail" | tr '\n' ' ')"
            return 1
        fi
        SRC_ZIP_URL="${tagbase}/${ARG_VERSION}.zip"; SRC_LABEL="Release ${ARG_VERSION}"; return 0
    fi
    if [ -n "$ARG_CHANNEL" ]; then
        case "$ARG_CHANNEL" in
            beta|main)      SRC_ZIP_URL="$beta"; SRC_LABEL="Beta (main)"; return 0 ;;
            release|auto|latest|stable)
                local l; l=$(get_latest_version)
                if [ -n "$l" ]; then SRC_ZIP_URL="${tagbase}/${l}.zip"; SRC_LABEL="Release ${l}";
                else SRC_ZIP_URL="$beta"; SRC_LABEL="Beta (main)"; fi
                return 0 ;;
            *) echo -e " ${C_BAD}Unknown channel: ${ARG_CHANNEL}${CR}"; return 1 ;;
        esac
    fi

    # ── Interactive ──────────────────────────────────────────
    _sec "Select version"
    _mi "1" "Automatic  ${C_DIM}(latest stable release)${CR}"
    _mi "2" "Choose a specific release version"
    _mi "3" "Beta       ${C_DIM}(latest main branch - may be unstable)${CR}"
    _mi "0" "Back to menu"
    echo ""
    printf " ${C_PROMPT}❯${CR} Select ${C_DIM}[0-3]${CR}: "
    local S; read -r S
    case "$S" in
        0) return 2 ;;
        1)
            local l; l=$(get_latest_version)
            if [ -n "$l" ]; then SRC_ZIP_URL="${tagbase}/${l}.zip"; SRC_LABEL="Release ${l}";
            else
                echo -e " ${C_WARN}Could not detect latest release; falling back to Beta.${CR}"
                SRC_ZIP_URL="$beta"; SRC_LABEL="Beta (main)"
            fi
            return 0 ;;
        2)
            echo ""
            echo -e " ${C_DIM}Fetching available versions...${CR}"
            local TAGS=(); mapfile -t TAGS < <(list_tags_desc)
            if [ "${#TAGS[@]}" -eq 0 ]; then
                echo -e " ${C_BAD}●${CR} ${C_BAD}Could not fetch release list (offline or rate-limited).${CR}"
                return 1
            fi
            _sec "Available versions"
            local i=1 t
            for t in "${TAGS[@]}"; do
                if [ "$i" -eq 1 ]; then _mi "$i" "${t}  ${C_OK}(latest)${CR}"; else _mi "$i" "$t"; fi
                i=$((i+1))
            done
            _mi "0" "Back to menu"
            echo ""
            printf " ${C_PROMPT}❯${CR} Select version ${C_DIM}[default: 1]${CR}: "
            local V; read -r V; [ -z "$V" ] && V=1
            [ "$V" = "0" ] && return 2
            if ! [[ "$V" =~ ^[0-9]+$ ]] || [ "$V" -lt 1 ] || [ "$V" -gt "${#TAGS[@]}" ]; then
                echo -e " ${C_BAD}Invalid selection.${CR}"; return 1
            fi
            local c="${TAGS[$((V-1))]}"
            SRC_ZIP_URL="${tagbase}/${c}.zip"; SRC_LABEL="Release ${c}"
            return 0 ;;
        3) SRC_ZIP_URL="$beta"; SRC_LABEL="Beta (main)"; return 0 ;;
        *) echo -e " ${C_BAD}Invalid selection.${CR}"; return 1 ;;
    esac
}

# Get public server IP, cached for 1 hour (falls back to local IP)
get_server_ip() {
    if [ -f "$IP_CACHE" ] && [ $(( $(date +%s) - $(stat -c %Y "$IP_CACHE" 2>/dev/null || echo 0) )) -lt 3600 ]; then
        cat "$IP_CACHE"
        return
    fi
    local ip
    ip=$(curl -fsSL --max-time 4 ifconfig.me 2>/dev/null)
    [ -z "$ip" ] && ip=$(curl -fsSL --max-time 4 https://api.ipify.org 2>/dev/null)
    [ -z "$ip" ] && ip=$(hostname -I 2>/dev/null | awk '{print $1}')
    [ -z "$ip" ] && ip="n/a"
    echo "$ip" > "$IP_CACHE"
    echo "$ip"
}

# ── Dashboard sections ───────────────────────────────────────
function show_logo() {
    _measure
    local inst latest installed=0 domain="" ssl_days="" token info="" ok="" url="" pending=0 err="" hook_state="" hook_bad=0
    local php_v apache_ver apache_short php_txt apache_s mysql_s cron_s ip os mem_t mem_u mem_p mem_txt disk_u disk_t disk_p load cores up
    local al_text=() al_key=() i
    DASH_PHP_BAD=0; DASH_UPDATE=0; DASH_SSL=0

    inst=$(get_installed_version)
    latest=$(get_latest_version)
    [ -f "$CONFIG_FILE_DEFAULT" ] && installed=1
    if [ "$installed" = 1 ]; then
        domain=$(grep '^\$domainhosts' "$CONFIG_FILE_DEFAULT" | cut -d"'" -f2 | cut -d'/' -f1)
        if [ -n "$domain" ] && [ -f "/etc/letsencrypt/live/$domain/cert.pem" ]; then
            ssl_days=$(( ( $(date -d "$(openssl x509 -enddate -noout -in "/etc/letsencrypt/live/$domain/cert.pem" 2>/dev/null | cut -d= -f2)" +%s 2>/dev/null || echo 0) - $(date +%s) ) / 86400 ))
        fi
        token=$(grep '^\$APIKEY' "$CONFIG_FILE_DEFAULT" | cut -d"'" -f2)
        [ -n "$token" ] && info=$(curl -fsSL --max-time 8 "https://api.telegram.org/bot${token}/getWebhookInfo" 2>/dev/null)
        if [ -n "$info" ]; then
            if command -v jq >/dev/null 2>&1; then
                ok=$(echo "$info"     | jq -r '.ok')
                url=$(echo "$info"    | jq -r '.result.url // empty')
                pending=$(echo "$info"| jq -r '.result.pending_update_count // 0')
                err=$(echo "$info"    | jq -r '.result.last_error_message // empty')
            else
                ok=$(echo "$info"     | grep -oE '"ok":[[:space:]]*(true|false)' | grep -oE '(true|false)')
                url=$(echo "$info"    | grep -oE '"url":[[:space:]]*"[^"]*"' | sed -E 's/.*"url":[[:space:]]*"([^"]*)".*/\1/')
                pending=$(echo "$info"| grep -oE '"pending_update_count":[[:space:]]*[0-9]+' | grep -oE '[0-9]+$')
                err=$(echo "$info"    | grep -oE '"last_error_message":[[:space:]]*"[^"]*"' | sed -E 's/.*"last_error_message":[[:space:]]*"([^"]*)".*/\1/')
                [ -z "$pending" ] && pending=0
            fi
        fi
        if [ -z "$token" ]; then hook_state="token missing"
        elif [ -z "$info" ]; then hook_state="Telegram unreachable"
        elif [ "$ok" != "true" ]; then hook_state="API error"
        elif [ -z "$url" ]; then hook_state="not set"
        fi
        [ -n "$hook_state" ] && hook_bad=1
    fi

    php_v=$(php -r 'echo PHP_VERSION;' 2>/dev/null)
    apache_ver=$(_apache_php_ver)
    apache_short=${apache_ver#*:}; apache_short=${apache_short%%[-+~]*}
    if [ -z "$php_v" ] || ! dpkg --compare-versions "$php_v" ge "$PHP_MIN_VERSION"; then
        DASH_PHP_BAD=1; php_txt="${C_BAD}▲ ${php_v:-none}${CR} ${C_DIM}(need ${PHP_MIN_VERSION}+)${CR}"
    elif [ -d /etc/apache2/mods-enabled ] && { [ -z "$apache_ver" ] || ! dpkg --compare-versions "${apache_ver#*:}" ge "$PHP_MIN_VERSION"; }; then
        DASH_PHP_BAD=1; php_txt="${C_BAD}▲ Apache ${apache_short:-none}${CR} ${C_DIM}(need ${PHP_MIN_VERSION}+)${CR}"
    else
        php_txt="${C_OK}●${CR} ${php_v}"
    fi
    apache_s=$(systemctl is-active apache2 2>/dev/null); [ -z "$apache_s" ] && apache_s=inactive
    mysql_s=$(systemctl is-active mysql 2>/dev/null); [ -z "$mysql_s" ] && mysql_s=inactive
    cron_s=$(systemctl is-active "$(_cron_unit_name)" 2>/dev/null); [ -z "$cron_s" ] && cron_s=inactive
    ip=$(get_server_ip)
    if [ -f /etc/os-release ]; then os=$(. /etc/os-release; echo "${NAME} ${VERSION_ID}"); else os="Unknown"; fi
    read -r mem_t mem_u < <(free -m 2>/dev/null | awk '/^Mem:/{print $2, $3}')
    if [ -n "$mem_t" ] && [ "$mem_t" -gt 0 ] 2>/dev/null; then mem_p=$(( mem_u * 100 / mem_t )); else mem_p=0; mem_t=0; mem_u=0; fi
    mem_txt=$(awk -v u="$mem_u" -v t="$mem_t" 'BEGIN{printf "%.1f/%.1fG", u/1024, t/1024}')
    read -r disk_u disk_t disk_p < <(df -BG / 2>/dev/null | awk 'NR==2{gsub("G","",$2); gsub("G","",$3); gsub("%","",$5); print $3, $2, $5}')
    [ -z "$disk_p" ] && disk_p=0
    load=$(cut -d' ' -f1-3 /proc/loadavg 2>/dev/null)
    cores=$(nproc 2>/dev/null)
    up=$(uptime -p 2>/dev/null | sed 's/^up //' | cut -d, -f1-2)
    [ -z "$up" ] && up="n/a"

    if [ "$installed" = 1 ] && [ "$DASH_PHP_BAD" = 1 ]; then
        al_text+=("${C_BAD}▲ PHP ${php_v:-none} is older than ${PHP_MIN_VERSION}. The bot is paused for all users.${CR}"); al_key+=("${C_KEY}[4] fix${CR}")
    fi
    if [ "$hook_bad" = 1 ]; then
        al_text+=("${C_BAD}▲ Webhook: ${hook_state}.${CR}"); al_key+=("")
    fi
    if [ "$installed" = 1 ] && { [ "$apache_s" != active ] || [ "$mysql_s" != active ]; }; then
        al_text+=("${C_BAD}▲ Apache is ${apache_s}, MySQL is ${mysql_s}.${CR}"); al_key+=("")
    fi
    if [ -n "$inst" ] && [ -n "$latest" ] && [ "$inst" != "$latest" ] && [ "$(printf '%s\n%s\n' "$inst" "$latest" | sort -V | tail -1)" = "$latest" ]; then
        DASH_UPDATE=1
        al_text+=("${C_KEY}●${CR} ${C_TITLE}Mirza ${latest} is available.${CR} ${C_DIM}You have ${inst}.${CR}"); al_key+=("${C_KEY}[2] update${CR}")
    fi
    if [ -n "$ssl_days" ] && [ "$ssl_days" -le 14 ]; then
        DASH_SSL=1
        if [ "$ssl_days" -gt 0 ]; then
            al_text+=("${C_KEY}●${CR} ${C_TITLE}SSL certificate expires in ${ssl_days} days.${CR}")
        else
            al_text+=("${C_BAD}▲ SSL certificate has expired.${CR}")
        fi
        al_key+=("${C_KEY}[5] renew${CR}")
    elif [ "$installed" = 1 ] && [ -n "$domain" ] && [ -z "$ssl_days" ]; then
        DASH_SSL=1
        al_text+=("${C_KEY}●${CR} ${C_TITLE}No SSL certificate found for ${domain}.${CR}"); al_key+=("${C_KEY}[5] renew${CR}")
    fi
    if [ "$installed" = 0 ]; then
        al_text+=("${C_KEY}●${CR} ${C_TITLE}Mirza is not installed on this server.${CR}"); al_key+=("${C_KEY}[1] install${CR}")
    fi
    if [ "${#al_text[@]}" -eq 0 ]; then
        al_text+=("${C_OK}● All good. Nothing needs attention.${CR}"); al_key+=("")
    fi

    local st_state st_hook st_err st_ssl svc_a svc_m svc_c
    if [ "$installed" = 1 ]; then st_state="${C_OK}●${CR} installed"; else st_state="${C_DIM}○ not installed${CR}"; fi
    if [ "$installed" = 0 ]; then st_hook="${C_DIM}-${CR}"
    elif [ "$hook_bad" = 1 ]; then st_hook="${C_BAD}▲${CR} ${hook_state}"
    else st_hook="${C_OK}●${CR} set ${C_DIM}· ${pending} pending${CR}"; fi
    if [ "$installed" = 0 ] || [ "$hook_bad" = 1 ]; then st_err="${C_DIM}-${CR}"
    elif [ -n "$err" ]; then st_err="${C_BAD}▲${CR} ${err:0:26}"
    else st_err="${C_OK}●${CR} none"; fi
    if [ -z "$ssl_days" ]; then st_ssl="${C_DIM}-${CR}"
    elif [ "$ssl_days" -gt 14 ]; then st_ssl="${C_OK}●${CR} ${ssl_days} days left"
    elif [ "$ssl_days" -gt 0 ]; then st_ssl="${C_KEY}●${CR} ${ssl_days} days left ${C_DIM}· renew soon${CR}"
    else st_ssl="${C_BAD}▲${CR} expired"; fi
    if [ "$apache_s" = active ]; then svc_a="${C_OK}●${CR} active"; else svc_a="${C_BAD}▲${CR} ${apache_s}"; fi
    if [ "$mysql_s" = active ]; then svc_m="${C_OK}●${CR} active"; else svc_m="${C_BAD}▲${CR} ${mysql_s}"; fi
    if [ "$cron_s" = active ]; then svc_c="${C_OK}●${CR} active"; else svc_c="${C_BAD}▲${CR} ${cron_s}"; fi

    clear
    echo
    if [ "$UI_W" -ge 80 ]; then
        _lr " ${C_OK}▌${CR} ${C_TITLE}MIRZA PRO${CR}  ${C_DIM}VPN subscription bot${CR}" "${inst:+${C_DIM}installed${CR} ${C_OK}${inst}${CR}  }${C_DIM}latest${CR} ${C_KEY}${latest:-unknown}${CR} "
        _lr "   ${C_DIM}${domain:+https://$domain}${CR}" "${C_DIM}${os} · ${ip}${CR} "
        _drule
        for i in "${!al_text[@]}"; do
            [ "$i" -ge 3 ] && break
            _lr " ${al_text[$i]}" "${al_key[$i]} "
        done
        _rule
        _cols 41 " ${C_BORDER}BOT${CR}" 39 "${C_BORDER}SERVER${CR}"
        _cols 41 " ${C_DIM}State   ${CR} ${st_state}" 39 "${C_DIM}PHP     ${CR} ${php_txt}"
        _cols 41 " ${C_DIM}Webhook ${CR} ${st_hook}" 39 "${C_DIM}Apache  ${CR} ${svc_a}"
        _cols 41 " ${C_DIM}Errors  ${CR} ${st_err}" 39 "${C_DIM}MySQL   ${CR} ${svc_m}"
        _cols 41 " ${C_DIM}SSL     ${CR} ${st_ssl}" 39 "${C_DIM}Cron    ${CR} ${svc_c}"
        echo
        _cols 41 " ${C_DIM}RAM   ${CR} $(_bar "$mem_p" 14) ${mem_p}% ${C_DIM}${mem_txt}${CR}" 39 "${C_DIM}Disk  ${CR} $(_bar "$disk_p" 14) ${disk_p}% ${C_DIM}${disk_u}/${disk_t}G${CR}"
        _cols 41 " ${C_DIM}Load  ${CR} ${load} ${C_DIM}· ${cores} cores${CR}" 39 "${C_DIM}Up    ${CR} ${up}"
    else
        printf " ${C_OK}▌${CR} ${C_TITLE}MIRZA PRO${CR}  ${C_OK}%s${CR}\n" "${inst:-not installed}"
        _drule
        for i in "${!al_text[@]}"; do
            [ "$i" -ge 3 ] && break
            printf ' %b\n' "${al_text[$i]}"
            [ -n "${al_key[$i]}" ] && printf '   %b\n' "${al_key[$i]}"
        done
        _rule
        _kv "Bot" "$st_state"
        _kv "Webhook" "$st_hook"
        _kv "SSL" "$st_ssl"
        _kv "PHP" "$php_txt"
        printf " ${C_DIM}%-12s${CR} %b  ${C_DIM}MySQL${CR} %b\n" "Apache" "${svc_a%% *}" "${svc_m%% *}"
        printf " ${C_DIM}RAM %s%%  Disk %s%%  Load %s${CR}\n" "$mem_p" "$disk_p" "${load%% *}"
    fi
    _rule
}

# Renew (or issue) the SSL certificate for the bot's domain.
function renew_ssl() {
    clear
    banner
    _sec "Renew SSL certificate"

    # 1) Detect the bot domain: prefer config.php, then saved install state
    local cfg="/var/www/html/mirzaprobotconfig/config.php"
    local domain=""
    if [ -f "$cfg" ]; then
        domain=$(grep -E "\\\$domainhosts" "$cfg" 2>/dev/null | head -1 | cut -d"'" -f2)
    fi
    [ -z "$domain" ] && domain="$(state_get DOMAIN)"
    if [ -z "$domain" ]; then
        printf " ${C_PROMPT}❯${CR} Enter the bot domain: "
        read -r domain
    fi
    if [ -z "$domain" ]; then
        echo -e " ${C_BAD}●${CR} ${C_BAD}No domain found. Aborting.${CR}"
        sleep 1; show_menu; return 1
    fi
    _kv "Domain" "${C_KEY}${domain}${CR}"

    if ! command -v certbot >/dev/null 2>&1; then
        echo -e " ${C_BAD}●${CR} ${C_BAD}certbot is not installed. Install Mirza first.${CR}"
        sleep 1; show_menu; return 1
    fi

    # Show current expiry, if a certificate already exists
    local certfile="/etc/letsencrypt/live/${domain}/cert.pem"
    if [ -f "$certfile" ]; then
        local exp
        exp=$(openssl x509 -enddate -noout -in "$certfile" 2>/dev/null | cut -d= -f2)
        [ -n "$exp" ] && _kv "Expires" "${C_DIM}${exp}${CR}"
    else
        echo -e " ${C_WARN}!${CR} ${C_WARN}No existing certificate found - a new one will be issued.${CR}"
    fi
    echo ""

    # 2) Optional force (Let's Encrypt normally renews only within ~30 days of expiry)
    printf " ${C_PROMPT}❯${CR} Force renewal now even if not near expiry? ${C_DIM}[y/N]${CR}: "
    read -r _force
    local force_flag=""
    [[ "$_force" =~ ^[Yy]$ ]] && force_flag="--force-renewal"
    echo ""

    # Use the apache authenticator so it works while Apache is running (no downtime).
    # certonly updates the existing cert lineage in place; Apache already points at it.
    run_step "Renewing certificate for ${domain}" \
        "certbot certonly --apache --non-interactive --agree-tos --register-unsafely-without-email --keep-until-expiring --cert-name '${domain}' -d '${domain}' ${force_flag}" \
        || { show_step_error; echo -e "\n ${C_BAD}●${CR} ${C_BAD}Renewal failed. See the details above.${CR}"; echo ""; printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "; read -r _; show_menu; return 1; }

    run_step "Reloading Apache" "systemctl reload apache2 2>/dev/null || systemctl restart apache2"

    _sec "Done"
    _kv "Domain" "${C_KEY}${domain}${CR}"
    if [ -f "$certfile" ]; then
        local newexp
        newexp=$(openssl x509 -enddate -noout -in "$certfile" 2>/dev/null | cut -d= -f2)
        [ -n "$newexp" ] && _kv "Valid until" "${C_OK}${newexp}${CR}"
    fi
    echo ""
    printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}

function backup_bot() {
    clear
    banner
    _sec "Backup Database"
    set_db_vars

    CONFIG_PATH="/var/www/html/mirzaprobotconfig/config.php"
    if [ ! -f "$CONFIG_PATH" ]; then
        printf " ${C_BAD}●${CR} ${C_BAD}Mirza is not installed. config.php not found.${CR}\n"
        echo ""
        printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi

    local dbhost dbname dbuser dbpass bot_token admin_id
    dbhost=$(grep '^\$dbhost' "$CONFIG_PATH" | cut -d"'" -f2)
    dbname=$(grep '^\$dbname' "$CONFIG_PATH" | cut -d"'" -f2)
    dbuser=$(grep '^\$usernamedb' "$CONFIG_PATH" | cut -d"'" -f2)
    dbpass=$(grep '^\$passworddb' "$CONFIG_PATH" | cut -d"'" -f2)
    bot_token=$(grep '^\$APIKEY' "$CONFIG_PATH" | cut -d"'" -f2)
    admin_id=$(grep '^\$adminnumber' "$CONFIG_PATH" | cut -d"'" -f2)
    [ -z "$dbhost" ] && dbhost="localhost"

    if [ -z "$dbname" ] || [ -z "$dbuser" ] || [ -z "$dbpass" ]; then
        printf " ${C_BAD}●${CR} ${C_BAD}Could not read database credentials from config.php${CR}\n"
        echo ""
        printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi

    _kv "Database" "${C_DIM}${dbname}${CR}"
    _kv "DB User" "${C_DIM}${dbuser}${CR}"
    _kv "DB Host" "${C_DIM}${dbhost}${CR}"
    echo ""

    local backup_date
    backup_date=$(date +"%Y-%m-%d_%H-%M-%S")
    local backup_file="/root/mirza_backup_${backup_date}.sql"

    # MariaDB's dump has no --ssl-mode; --skip-ssl is its equivalent.
    local _dump_ssl="--ssl-mode=DISABLED"
    [ "$DB_SERVICE" = "mariadb" ] && _dump_ssl="--skip-ssl"
    run_step "Exporting database (${dbname})" \
        "mysqldump -h '$dbhost' -u '$dbuser' -p'$dbpass' --no-tablespaces $_dump_ssl '$dbname' > '$backup_file'" \
        || { show_step_error; echo -e "\n ${C_BAD}●${CR} ${C_BAD}Backup failed. See details above.${CR}"; echo ""; printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "; read -r _; show_menu; return 1; }

    local file_size
    file_size=$(du -h "$backup_file" 2>/dev/null | awk '{print $1}')
    _kv "File" "${C_OK}${backup_file}${CR}"
    _kv "Size" "${C_DIM}${file_size}${CR}"

    if [ -n "$bot_token" ] && [ -n "$admin_id" ]; then
        echo ""
        local send_result
        send_result=$(curl -s -o /dev/null -w "%{http_code}" \
            -F "chat_id=${admin_id}" \
            -F "document=@${backup_file}" \
            -F "caption=📦 Mirza DB Backup (${backup_date})" \
            "https://api.telegram.org/bot${bot_token}/sendDocument" 2>/dev/null)
        if [ "$send_result" = "200" ]; then
            _kv "Telegram" "$(_dot ok) ${C_OK}Backup sent to admin chat (${admin_id})${CR}"
        else
            _kv "Telegram" "$(_dot bad) ${C_BAD}Failed to send (HTTP ${send_result})${CR}"
            printf " ${C_DIM}Make sure the bot token and admin chat ID are correct.${CR}\n"
        fi
    else
        echo ""
        printf " ${C_WARN}!${CR} ${C_WARN}Bot token or admin ID not found in config - skipping Telegram send.${CR}\n"
    fi

    echo ""
    printf " ${C_OK}✔${CR} ${C_OK}Backup saved to:${CR} ${C_KEY}${backup_file}${CR}\n"
    echo ""
    printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}

function import_bot() {
    clear
    banner
    _sec "Import Database"

    CONFIG_PATH="/var/www/html/mirzaprobotconfig/config.php"
    if [ ! -f "$CONFIG_PATH" ]; then
        printf " ${C_BAD}●${CR} ${C_BAD}Mirza is not installed. config.php not found.${CR}\n"
        echo ""
        printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi

    local dbhost dbname dbuser dbpass
    dbhost=$(grep '^\$dbhost' "$CONFIG_PATH" | cut -d"'" -f2)
    dbname=$(grep '^\$dbname' "$CONFIG_PATH" | cut -d"'" -f2)
    dbuser=$(grep '^\$usernamedb' "$CONFIG_PATH" | cut -d"'" -f2)
    dbpass=$(grep '^\$passworddb' "$CONFIG_PATH" | cut -d"'" -f2)
    [ -z "$dbhost" ] && dbhost="localhost"

    if [ -z "$dbname" ] || [ -z "$dbuser" ] || [ -z "$dbpass" ]; then
        printf " ${C_BAD}●${CR} ${C_BAD}Could not read database credentials from config.php${CR}\n"
        echo ""
        printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi

    _kv "Database" "${C_DIM}${dbname}${CR}"
    _kv "DB User" "${C_DIM}${dbuser}${CR}"
    _kv "DB Host" "${C_DIM}${dbhost}${CR}"
    echo ""

    echo -e " ${C_DIM}Available backup files in /root/:${CR}"
    local files=()
    local i=1
    while IFS= read -r f; do
        files+=("$f")
        local sz
        sz=$(du -h "$f" 2>/dev/null | awk '{print $1}')
        printf " ${C_KEY}[%d]${CR}  ${C_TXT}%s${CR}  ${C_DIM}(%s)${CR}\n" "$i" "$(basename "$f")" "$sz"
        i=$((i + 1))
    done < <(find /root -maxdepth 1 -name 'mirza_backup_*.sql' -type f 2>/dev/null | sort -r)

    if [ "${#files[@]}" -eq 0 ]; then
        printf " ${C_WARN}!${CR} ${C_WARN}No backup files found in /root/${CR}\n"
    fi

    echo ""
    printf " ${C_KEY}[0]${CR}  ${C_TXT}Enter a custom file path${CR}\n"
    echo ""
    printf " ${C_PROMPT}❯${CR} Select a file ${C_DIM}[0-%d]${CR} or enter path: " "${#files[@]}"
    read -r choice

    local sql_file=""
    if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#files[@]}" ]; then
        sql_file="${files[$((choice - 1))]}"
    elif [ "$choice" = "0" ] || [ -z "$choice" ]; then
        printf " ${C_PROMPT}❯${CR} Enter the full path to the .sql file: "
        read -r sql_file
    else
        sql_file="$choice"
    fi

    if [ -z "$sql_file" ] || [ ! -f "$sql_file" ]; then
        printf "\n    ${C_BAD}●${CR} ${C_BAD}File not found: %s${CR}\n" "$sql_file"
        echo ""
        printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi

    local file_size
    file_size=$(du -h "$sql_file" 2>/dev/null | awk '{print $1}')
    _kv "File" "${C_DIM}${sql_file}${CR}"
    _kv "Size" "${C_DIM}${file_size}${CR}"
    echo ""

    printf " ${C_WARN}!${CR} ${C_WARN}This will OVERWRITE the current database (${dbname}).${CR}\n"
    printf " ${C_PROMPT}❯${CR} Are you sure? ${C_DIM}[y/N]${CR}: "
    read -r confirm
    if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
        printf "\n    ${C_DIM}Import cancelled.${CR}\n"
        echo ""
        printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 0
    fi
    echo ""

    run_step "Importing database (${dbname})" \
        "mysql -h '$dbhost' -u '$dbuser' -p'$dbpass' '$dbname' < '$sql_file'" \
        || { show_step_error; echo -e "\n ${C_BAD}●${CR} ${C_BAD}Import failed. See details above.${CR}"; echo ""; printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "; read -r _; show_menu; return 1; }

    local DOMAIN_NAME=""
    if [ -f "$CONFIG_PATH" ]; then
        DOMAIN_NAME=$(grep '^\$domainhosts' "$CONFIG_PATH" | cut -d"'" -f2 | cut -d'/' -f1)
    fi
    if [ -n "$DOMAIN_NAME" ]; then
        run_step "Updating database tables" "curl -s 'https://${DOMAIN_NAME}/table.php' > /dev/null" || true
    fi

    echo ""
    printf " ${C_OK}✔${CR} ${C_OK}Database imported successfully from:${CR} ${C_KEY}${sql_file}${CR}\n"
    echo ""
    printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}

function show_menu() {
    while true; do
    show_logo
    local m2="" m4="" m5=""
    [ "$DASH_UPDATE" = 1 ] && m2=" ${C_KEY}●${CR}"
    [ "$DASH_PHP_BAD" = 1 ] && m4=" ${C_BAD}▲${CR}"
    [ "$DASH_SSL" = 1 ] && m5=" ${C_KEY}●${CR}"
    if [ "$UI_W" -ge 80 ]; then
        _cols 22 " ${C_BORDER}BOT${CR}" 26 "${C_BORDER}MAINTENANCE${CR}" 32 "${C_BORDER}TOOLS${CR}"
        _cols 22 " ${C_KEY}[1]${CR} Install" 26 "${C_KEY}[4]${CR} Update PHP${m4}" 32 "${C_KEY}[7]${CR} Migrate Free → Pro ${C_DIM}(Beta)${CR}"
        _cols 22 " ${C_KEY}[2]${CR} Update${m2}" 26 "${C_KEY}[5]${CR} Renew SSL${m5}" 32 "${C_KEY}[8]${CR} Help & parameters"
        _cols 22 " ${C_KEY}[3]${CR} Remove" 26 "${C_KEY}[6]${CR} Backup database" 32 "${C_KEY}[9]${CR} Import database ${C_DIM}(Beta)${CR}"
        _cols 22 "" 26 "" 32 "${C_KEY}[0]${CR} Exit"
    else
        _cols 20 " ${C_KEY}[1]${CR} Install" 20 "${C_KEY}[6]${CR} Backup"
        _cols 20 " ${C_KEY}[2]${CR} Update${m2}" 20 "${C_KEY}[7]${CR} Migrate"
        _cols 20 " ${C_KEY}[3]${CR} Remove" 20 "${C_KEY}[8]${CR} Help"
        _cols 20 " ${C_KEY}[4]${CR} PHP${m4}" 20 "${C_KEY}[9]${CR} Import"
        _cols 20 " ${C_KEY}[5]${CR} SSL${m5}" 20 "${C_KEY}[0]${CR} Exit"
    fi
    _rule
    printf " ${C_PROMPT}❯${CR} Select ${C_DIM}[0-9]${CR}: "
    read -r option || exit 0
    case $option in
        1) install_bot ;;
        2) update_bot ;;
        3) remove_bot ;;
        4) update_php ;;
        5) renew_ssl ;;
        6) backup_bot ;;
        7) migrate_to_pro ;;
        8) show_help_screen ;;
        9) import_bot ;;
        0) echo -e "\n${C_OK}Exiting...${CR}"; exit 0 ;;
        *) echo -e "\n${C_BAD}Invalid option. Please try again.${CR}"; sleep 1; continue ;;
    esac
    done
}

function update_php() {
    clear
    banner "Update PHP" "${C_DIM}mirza update-php${CR}"
    upgrade_php_if_needed || printf " ${C_BAD}●${CR} ${C_BAD}PHP was not updated.${CR}\n"
    echo ""
    printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}

# Clean, styled guide of all commands and parameters
function show_help_screen() {
    clear
    banner

    _sec "Commands"
    _kv "install" "${C_DIM}Install Mirza${CR}"
    _kv "update" "${C_DIM}Update Mirza (choose channel / version)${CR}"
    _kv "remove" "${C_DIM}Remove Mirza and its services${CR}"
    _kv "migrate" "${C_DIM}Migrate Free -> Pro${CR}"
    _kv "renew" "${C_DIM}Renew the bot domain SSL certificate${CR}"
    _kv "backup" "${C_DIM}Backup database & send to Telegram${CR}"
    _kv "import" "${C_DIM}Import database from SQL file (Beta)${CR}"
    _kv "update-php" "${C_DIM}Upgrade PHP to ${PHP_MIN_VERSION}+ and reconfigure Apache${CR}"
    _kv "menu" "${C_DIM}Open this interactive panel (default)${CR}"

    _sec "Install parameters"
    _kv "--token" "${C_DIM}Telegram bot token${CR}"
    _kv "--admin" "${C_DIM}Admin chat id${CR}"
    _kv "--domain" "${C_DIM}Domain name (e.g. bot.example.com)${CR}"
    _kv "--db-user" "${C_DIM}Database username${CR}"
    _kv "--db-pass" "${C_DIM}Database password${CR}"

    _sec "Source parameters"
    _kv "--version" "${C_DIM}Specific release tag (e.g. 0.1.7)${CR}"
    _kv "--channel" "${C_DIM}beta | release | auto${CR}"
    _kv "-h, --help" "${C_DIM}Show CLI help and exit${CR}"

    _sec "Examples"
    printf " ${C_KEY}mirza install --channel auto${CR}\n"
    printf " ${C_KEY}mirza install --token 123:ABC \\\\${CR}\n"
    printf " ${C_DIM}            --admin 111 --domain bot.example.com --version 0.1.7${CR}\n"
    printf " ${C_KEY}mirza update --version 0.1.6${CR}\n"
    printf " ${C_KEY}mirza update --channel release${CR}\n"
    printf " ${C_KEY}mirza remove${CR}\n"
    printf " ${C_KEY}mirza backup${CR}\n"
    printf " ${C_KEY}mirza import${CR}\n"

    echo ""
    _rule
    echo ""
    printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}
# Debian: rewrite apt sources (deb822 default since bookworm) against the
# deb.debian.org CDN. Security lives in its own suite URI.
_fix_debian_mirrors() {
    local DEB822=/etc/apt/sources.list.d/debian.sources
    local LEGACY=/etc/apt/sources.list
    local target="" fmt=""
    if [ -f "$DEB822" ]; then target="$DEB822"; fmt="deb822"
    else target="$LEGACY"; fmt="legacy"; fi
    [ -f "$target" ] && cp "$target" "$target.mirzabackup"

    local MIRRORS=("deb.debian.org" "mirrors.kernel.org")
    local mirror
    for mirror in "${MIRRORS[@]}"; do
        echo -e "\e[33mTrying mirror: $mirror\033[0m"
        if [ "$fmt" = "deb822" ]; then
            cat > "$target" << EOF
Types: deb
URIs: http://$mirror/debian
Suites: $OS_CODENAME $OS_CODENAME-updates $OS_CODENAME-backports
Components: main contrib non-free non-free-firmware
Signed-By: /usr/share/keyrings/debian-archive-keyring.gpg

Types: deb
URIs: http://deb.debian.org/debian-security
Suites: $OS_CODENAME-security
Components: main contrib non-free non-free-firmware
Signed-By: /usr/share/keyrings/debian-archive-keyring.gpg
EOF
        else
            cat > "$target" << EOF
deb http://$mirror/debian $OS_CODENAME main contrib non-free non-free-firmware
deb http://$mirror/debian $OS_CODENAME-updates main contrib non-free non-free-firmware
deb http://deb.debian.org/debian-security $OS_CODENAME-security main contrib non-free non-free-firmware
EOF
        fi
        # apt-get exits 0 even when a source fails to fetch; --error-on=any
        # makes this mirror check meaningful.
        if apt-get update --allow-releaseinfo-change --error-on=any 2>/dev/null; then
            echo -e "\e[32mSuccessfully updated using mirror: $mirror\033[0m"
            rm -f "$target.mirzabackup"
            return 0
        fi
    done
    if [ -f "$target.mirzabackup" ]; then
        mv "$target.mirzabackup" "$target"
    else
        rm -f "$target"
    fi
    echo -e "\e[91mAll mirrors failed. Restored original apt sources\033[0m"
    return 1
}

function fix_update_issues() {
    echo -e "\e[33mTrying to fix update issues by changing mirrors...\033[0m"
    # Broken apt mirrors are often a DNS problem - fix DNS first
    ensure_dns
    if ! detect_os || [ -z "$OS_CODENAME" ]; then
        echo -e "\e[91mCould not detect OS version.\033[0m"
        return 1
    fi

    if [ "$OS_ID" = "debian" ]; then
        _fix_debian_mirrors
        return $?
    fi

    # Ubuntu 24.04+ (and 26.04) ship the deb822 file and often have no
    # /etc/apt/sources.list at all - rewrite whichever one this release uses.
    local DEB822=/etc/apt/sources.list.d/ubuntu.sources
    local LEGACY=/etc/apt/sources.list
    local target="" fmt=""
    if [ -f "$DEB822" ]; then target="$DEB822"; fmt="deb822"
    else target="$LEGACY"; fmt="legacy"; fi
    [ -f "$target" ] && cp "$target" "$target.mirzabackup"

    local parked=""
    if [ "$fmt" = "deb822" ] && [ -s "$LEGACY" ]; then
        cp "$LEGACY" "$LEGACY.mirzabackup" && : > "$LEGACY" && parked="$LEGACY"
    fi

    # arm64/armhf live on ports.ubuntu.com, not the archive mirrors.
    local arch path MIRRORS
    arch=$(dpkg --print-architecture 2>/dev/null || uname -m)
    case "$arch" in
        arm64|armhf|ppc64el|s390x|riscv64)
            MIRRORS=("ports.ubuntu.com")
            path="ubuntu-ports"
            ;;
        *)
            MIRRORS=(
                "archive.ubuntu.com"
                "us.archive.ubuntu.com"
                "fr.archive.ubuntu.com"
                "de.archive.ubuntu.com"
                "mirrors.digitalocean.com"
                "mirrors.linode.com"
            )
            path="ubuntu"
            ;;
    esac

    local mirror
    for mirror in "${MIRRORS[@]}"; do
        echo -e "\e[33mTrying mirror: $mirror\033[0m"
        if [ "$fmt" = "deb822" ]; then
            cat > "$target" << EOF
Types: deb
URIs: http://$mirror/$path/
Suites: $OS_CODENAME $OS_CODENAME-updates $OS_CODENAME-backports $OS_CODENAME-security
Components: main restricted universe multiverse
Signed-By: /usr/share/keyrings/ubuntu-archive-keyring.gpg
EOF
        else
            cat > "$target" << EOF
deb http://$mirror/$path/ $OS_CODENAME main restricted universe multiverse
deb http://$mirror/$path/ $OS_CODENAME-updates main restricted universe multiverse
deb http://$mirror/$path/ $OS_CODENAME-security main restricted universe multiverse
EOF
        fi
        if apt-get update --allow-releaseinfo-change 2>/dev/null; then
            echo -e "\e[32mSuccessfully updated using mirror: $mirror\033[0m"
            rm -f "$target.mirzabackup"
            [ -n "$parked" ] && rm -f "$parked.mirzabackup"
            return 0
        fi
    done
    if [ -f "$target.mirzabackup" ]; then
        mv "$target.mirzabackup" "$target"
    else
        rm -f "$target"
    fi
    [ -n "$parked" ] && [ -f "$parked.mirzabackup" ] && mv "$parked.mirzabackup" "$parked"
    echo -e "\e[91mAll mirrors failed. Restored original apt sources\033[0m"
    return 1
}

# ─────────────────────────────────────────────────────────────
#  Validation and pre-flight checks
#  (DNS helpers dns_works/ensure_dns are defined near the top)
# ─────────────────────────────────────────────────────────────

# Can we actually reach the internet?
net_works() {
    curl -fsSL --max-time 8 -o /dev/null "https://github.com" 2>/dev/null && return 0
    curl -fsSL --max-time 8 -o /dev/null "https://api.telegram.org" 2>/dev/null && return 0
    return 1
}

# Ensure DNS + connectivity, fixing DNS automatically if needed.
ensure_connectivity() {
    ensure_dns
    net_works && return 0
    echo -e " ${C_WARN}!${CR} ${C_WARN}No connectivity - resetting DNS and retrying...${CR}"
    ensure_dns
    net_works && return 0
    return 1
}

# ── Input validators ─────────────────────────────────────────
validate_domain() { [[ "$1" =~ ^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$ ]]; }

# 0 = points here, 1 = points elsewhere, 2 = could not resolve
domain_points_here() {
    local dom="$1" myip resolved
    myip=$(get_server_ip)
    resolved=$(getent ahostsv4 "$dom" 2>/dev/null | awk '{print $1; exit}')
    [ -z "$resolved" ] && resolved=$(getent hosts "$dom" 2>/dev/null | awk '{print $1; exit}')
    [ -z "$resolved" ] && return 2
    [ "$resolved" = "$myip" ] && return 0
    return 1
}

# 0 = valid+live, 1 = bad format, 2 = format ok but token rejected/unreachable
validate_token() {
    TG_BOT_USERNAME=""
    [[ "$1" =~ ^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$ ]] || return 1
    local r; r=$(curl -fsSL --max-time 8 "https://api.telegram.org/bot$1/getMe" 2>/dev/null)
    echo "$r" | grep -q '"ok":true' || return 2
    TG_BOT_USERNAME=$(printf '%s' "$r" | sed -n 's/.*"username"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p')
    return 0
}

fetch_bot_username() {
    local r; r=$(curl -fsSL --max-time 8 "https://api.telegram.org/bot$1/getMe" 2>/dev/null)
    echo "$r" | grep -q '"ok":true' || return 1
    local u; u=$(printf '%s' "$r" | sed -n 's/.*"username"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p')
    [ -n "$u" ] || return 1
    printf '%s' "$u"
}

# Safe identifiers/passwords (no quotes/specials that break SQL or config.php)
valid_db_ident() { [[ "$1" =~ ^[A-Za-z0-9_]{1,32}$ ]]; }
valid_db_pass()  { [[ "$1" =~ ^[A-Za-z0-9_]{6,64}$ ]]; }

purge_installer_dir() {
    local target="$1"
    [ -z "$target" ] && return 0
    [ -e "$target/install" ] || return 0
    rm -rf "$target/install" 2>/dev/null
    [ -e "$target/install" ] && sudo rm -rf "$target/install" 2>/dev/null
    if [ -e "$target/install" ]; then
        printf " ${C_BAD}●${CR} ${C_BAD}Could not remove the web installer at %s/install.${CR}\n" "$target"
        printf " ${C_BAD}●${CR} ${C_BAD}Delete it manually - the bot refuses to answer users while it exists.${CR}\n"
        return 1
    fi
    return 0
}

move_extracted_files() {
    local src="$1" dest="$2"
    [ -d "$src" ] && [ -d "$dest" ] || return 1
    find "$src" -mindepth 1 -maxdepth 1 -exec mv -f -t "$dest/" {} +
}

# vpnbot instance dirs (not Default/update). update_bot wipes BOT_DIR.
VPNBOT_BACKUP="/tmp/mirza_vpnbot_backup"

vpnbot_instance_count() {
    local dir="$1" n=0 d
    [ -d "$dir" ] || { echo 0; return 0; }
    for d in "$dir"/*; do
        [ -d "$d" ] || continue
        case "$(basename "$d")" in Default|update) continue ;; esac
        n=$((n + 1))
    done
    echo "$n"
}
export -f vpnbot_instance_count

backup_vpnbots() {
    local bot_dir="$1"
    local src="$bot_dir/vpnbot"
    local d name count=0
    mkdir -p "$VPNBOT_BACKUP" || return 1
    [ -d "$src" ] || { echo "Backed up 0 vpnbot(s)"; return 0; }
    for d in "$src"/*; do
        [ -d "$d" ] || continue
        name=$(basename "$d")
        case "$name" in Default|update) continue ;; esac
        rm -rf "$VPNBOT_BACKUP/$name"
        cp -a "$d" "$VPNBOT_BACKUP/$name" || return 1
        count=$((count + 1))
    done
    echo "Backed up $count vpnbot(s)"
    return 0
}
export -f backup_vpnbots
export VPNBOT_BACKUP

restore_vpnbots() {
    local bot_dir="$1"
    local dest="$bot_dir/vpnbot"
    local update_dir="$bot_dir/vpnbot/update"
    local d name count=0
    [ -d "$VPNBOT_BACKUP" ] || { echo "No vpnbot backup to restore"; return 0; }
    mkdir -p "$dest" || return 1
    shopt -s nullglob
    for d in "$VPNBOT_BACKUP"/*; do
        [ -d "$d" ] || continue
        name=$(basename "$d")
        case "$name" in Default|update) continue ;; esac
        rm -rf "$dest/$name"
        cp -a "$d" "$dest/$name" || { shopt -u nullglob; return 1; }
        if [ -d "$update_dir" ]; then
            find "$update_dir" -mindepth 1 -maxdepth 1 \
                ! -name config.php ! -name product.json ! -name product_name.json ! -name data \
                -exec cp -a {} "$dest/$name/" \;
        fi
        count=$((count + 1))
    done
    shopt -u nullglob
    echo "Restored $count vpnbot(s)"
    return 0
}
export -f restore_vpnbots

set_vpnbot_webhooks() {
    local config="$1"
    [ -f "$config" ] || return 0
    local dbhost dbname dbuser dbpass domain rows id user token secret hook_url fail=0
    dbhost=$(grep '^\$dbhost' "$config" | cut -d"'" -f2)
    dbname=$(grep '^\$dbname' "$config" | cut -d"'" -f2)
    dbuser=$(grep '^\$usernamedb' "$config" | cut -d"'" -f2)
    dbpass=$(grep '^\$passworddb' "$config" | cut -d"'" -f2)
    domain=$(grep '^\$domainhosts' "$config" | cut -d"'" -f2 | cut -d'/' -f1)
    [ -z "$dbhost" ] && dbhost="localhost"
    [ -n "$dbname" ] && [ -n "$dbuser" ] && [ -n "$domain" ] || return 0
    command -v mysql >/dev/null 2>&1 || return 0
    rows=$(mysql -h "$dbhost" -u "$dbuser" -p"$dbpass" -N -B \
        -e "SELECT id_user, username, bot_token, IFNULL(webhook_secret, '') FROM botsaz;" "$dbname" 2>/dev/null) \
        || rows=$(mysql -h "$dbhost" -u "$dbuser" -p"$dbpass" -N -B \
            -e "SELECT id_user, username, bot_token, '' FROM botsaz;" "$dbname" 2>/dev/null) \
        || return 0
    [ -n "$rows" ] || return 0
    while IFS=$'\t' read -r id user token secret; do
        [ -n "$id" ] && [ -n "$user" ] && [ -n "$token" ] || continue
        if [ -z "$secret" ] || [ "$secret" = "NULL" ]; then
            secret=$(openssl rand -hex 24)
            mysql -h "$dbhost" -u "$dbuser" -p"$dbpass" \
                -e "UPDATE botsaz SET webhook_secret = '$secret' WHERE bot_token = '$token';" "$dbname" >/dev/null 2>&1 \
                || secret=""
        fi
        hook_url="https://${domain}/vpnbot/${id}${user}/index.php"
        [ -n "$secret" ] && hook_url="${hook_url}?secret=${secret}"
        curl -s --max-time 15 -o /dev/null \
            -F "url=${hook_url}" \
            "https://api.telegram.org/bot${token}/setWebhook" || fail=$((fail + 1))
    done <<< "$rows"
    [ "$fail" -eq 0 ]
}
export -f set_vpnbot_webhooks

# Whole-server pre-flight before installing
preflight() {
    local ok=1
    _sec "Pre-flight checks"

    if command -v apt-get >/dev/null 2>&1; then
        _kv "Package mgr" "$(_dot ok) ${C_OK}apt detected${CR}"
    else
        _kv "Package mgr" "$(_dot bad) ${C_BAD}apt not found (Ubuntu/Debian required)${CR}"; ok=0
    fi

    # Supported: Ubuntu 22.04 / 24.04 / 26.04 and Debian 12 / 13.
    detect_os
    set_db_vars
    local maj; maj=$(os_major)
    if [ "$OS_ID" = "ubuntu" ]; then
        case "$OS_VERSION_ID" in
            22.04|24.04|26.04) _kv "OS" "$(_dot ok) ${C_OK}${OS_PRETTY}${CR}" ;;
            *)
                if [ "$maj" -ge 26 ]; then
                    _kv "OS" "$(_dot ok) ${C_OK}${OS_PRETTY}${CR} ${C_DIM}(newer than tested)${CR}"
                elif [ "$maj" -ge 20 ]; then
                    _kv "OS" "$(_dot warn) ${C_WARN}${OS_PRETTY} (untested; 22.04/24.04/26.04 recommended)${CR}"
                elif [ "$maj" -eq 0 ]; then
                    # No usable VERSION_ID (dev snapshot, trimmed image): warn, don't block.
                    _kv "OS" "$(_dot warn) ${C_WARN}${OS_PRETTY} (version unknown; 22.04/24.04/26.04 recommended)${CR}"
                else
                    _kv "OS" "$(_dot bad) ${C_BAD}${OS_PRETTY} (too old; use 22.04, 24.04 or 26.04)${CR}"; ok=0
                fi
                ;;
        esac
    elif [ "$OS_ID" = "debian" ]; then
        case "$OS_VERSION_ID" in
            12|13) _kv "OS" "$(_dot ok) ${C_OK}${OS_PRETTY}${CR}" ;;
            *)
                if [ "$maj" -ge 14 ]; then
                    _kv "OS" "$(_dot ok) ${C_OK}${OS_PRETTY}${CR} ${C_DIM}(newer than tested)${CR}"
                elif [ "$maj" -eq 0 ]; then
                    # No usable VERSION_ID (dev snapshot, trimmed image): warn, don't block.
                    _kv "OS" "$(_dot warn) ${C_WARN}${OS_PRETTY} (version unknown; Debian 12/13 recommended)${CR}"
                else
                    _kv "OS" "$(_dot bad) ${C_BAD}${OS_PRETTY} (too old; use Debian 12 or 13)${CR}"; ok=0
                fi
                ;;
        esac
    else
        _kv "OS" "$(_dot warn) ${C_WARN}${OS_PRETTY:-unknown} (untested)${CR}"
    fi

    local arch; arch=$(uname -m)
    case "$arch" in
        x86_64|amd64|aarch64|arm64) _kv "Arch" "$(_dot ok) ${C_OK}${arch}${CR}" ;;
        *) _kv "Arch" "$(_dot warn) ${C_WARN}${arch} (untested)${CR}" ;;
    esac

    local free_mb; free_mb=$(df -Pm / 2>/dev/null | awk 'NR==2{print $4}')
    if [ "${free_mb:-0}" -ge 2048 ]; then
        _kv "Disk free" "$(_dot ok) ${C_OK}${free_mb} MB${CR}"
    else
        _kv "Disk free" "$(_dot bad) ${C_BAD}${free_mb:-0} MB (need >= 2048 MB)${CR}"; ok=0
    fi

    local mem; mem=$(free -m 2>/dev/null | awk '/^Mem:/{print $2}')
    if [ "${mem:-0}" -ge 900 ]; then
        _kv "RAM" "$(_dot ok) ${C_OK}${mem} MB${CR}"
    else
        _kv "RAM" "$(_dot warn) ${C_WARN}${mem:-0} MB (low; MySQL may struggle)${CR}"
    fi

    if ensure_connectivity; then
        _kv "Network" "$(_dot ok) ${C_OK}online${CR}"
    else
        _kv "Network" "$(_dot bad) ${C_BAD}offline (cannot reach GitHub/Telegram)${CR}"; ok=0
    fi

    local b80 b443
    b80=$(ss -ltnH 'sport = :80' 2>/dev/null | head -1)
    b443=$(ss -ltnH 'sport = :443' 2>/dev/null | head -1)
    if [ -n "$b80" ] || [ -n "$b443" ]; then
        _kv "Ports 80/443" "$(_dot warn) ${C_WARN}in use (will be freed for Apache/SSL)${CR}"
    else
        _kv "Ports 80/443" "$(_dot ok) ${C_OK}free${CR}"
    fi

    if [ "$ok" -ne 1 ]; then
        echo ""
        echo -e " ${C_BAD}●${CR} ${C_BAD}Pre-flight checks failed. Aborting to avoid a broken install.${CR}"
        return 1
    fi
    return 0
}

function install_bot() {
    BOT_DIR="/var/www/html/mirzaprobotconfig"
    INSTALL_STARTED=$(date +%s)
    PHP_VER="$(state_get PHP_VER)"
    [ -z "$PHP_VER" ] && PHP_VER="$PHP_TARGET_VER"

    # ── Guard: only block when a PREVIOUS install fully COMPLETED ──
    if [ -f "$CONFIG_FILE_DEFAULT" ] && ! has_resumable_state; then
        clear
        banner
        _sec "Install blocked"
        printf " ${C_BAD}●${CR} ${C_BAD}Mirza is already installed on this server.${CR}\n"
        printf " ${C_DIM}Path:${CR} %s\n" "$BOT_DIR_DEFAULT"
        echo ""
        printf " ${C_DIM}To upgrade, use option ${CR}${C_KEY}2 (Update)${CR}${C_DIM}.${CR}\n"
        printf " ${C_DIM}To reinstall, first remove it with option ${CR}${C_KEY}3 (Remove)${CR}${C_DIM}.${CR}\n"
        echo ""
        printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi
    # ── Fresh-server requirement (only on a brand-new install) ──
    if ! has_resumable_state && [ ! -f "$CONFIG_FILE_DEFAULT" ]; then
        if ! precheck_fresh_server; then
            echo ""
            printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
            read -r _
            show_menu
            return 1
        fi
    fi

    # ── Resume detector: an unfinished install is on disk ──
    if has_resumable_state; then
        clear
        banner
        _sec "Resume install"
        local _last
        _last="$(grep '^PHASE:' "$STATE_FILE" 2>/dev/null | tail -1 | cut -d: -f2)"
        [ -z "$_last" ] && _last="dependencies"
        printf " ${C_WARN}●${CR} ${C_WARN}An unfinished installation was found.${CR}\n"
        printf " ${C_DIM}Last completed step:${CR} ${C_KEY}%s${CR}\n" "$_last"
        echo ""
        printf " ${C_KEY}[1]${CR} ${C_TXT}Resume from where it stopped${CR}\n"
        printf " ${C_KEY}[2]${CR} ${C_TXT}Start fresh from the beginning${CR}\n"
        printf " ${C_KEY}[0]${CR} ${C_TXT}Back to menu${CR}\n"
        echo ""
        printf " ${C_PROMPT}❯${CR} Your choice: "
        read -r _resume_choice
        case "$_resume_choice" in
            2)
                state_clear
                [ -d "$BOT_DIR" ] && sudo rm -rf "$BOT_DIR"
                echo -e " ${C_DIM}Starting from scratch...${CR}"; sleep 1 ;;
            0) show_menu; return 0 ;;
            *) echo -e " ${C_OK}●${CR} ${C_OK}Resuming installation from the last step...${CR}"; sleep 1 ;;
        esac
    fi
    state_init
    state_set STARTED 1   # mark install as in-progress -> future re-runs resume (skip fresh-check)
    plan_eta   # count pending steps + estimate total time left

    # ── Pre-flight checks (network/DNS/disk/ram/ports) ──
    clear
    banner
    if ! preflight; then
        echo ""
        printf " ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi

    if phase_done DEPS && [ "$PHP_VER" != "$PHP_TARGET_VER" ]; then
        upgrade_php_if_needed || { LAST_STEP_RC=0; install_pause "PHP ${PHP_MIN_VERSION}+ required"; }
        PHP_VER="$PHP_TARGET_VER"
        state_set PHP_VER "$PHP_VER"
    fi

    # ╭──────────────────────── PHASE: DEPS ────────────────────────╮
    if ! phase_done DEPS; then
        # Choose which version to install (only needed before files are fetched)
        echo ""
        choose_source
        local _rc=$?
        if [ "$_rc" -eq 2 ]; then show_menu; return 0; fi
        if [ "$_rc" -ne 0 ]; then sleep 2; show_menu; return 1; fi
        state_set SRC_ZIP_URL "$SRC_ZIP_URL"
        state_set SRC_LABEL "$SRC_LABEL"
        echo ""
        echo -e " ${C_DIM}Install target:${CR} ${C_KEY}${SRC_LABEL}${CR}"
        sleep 1

        install_banner
        phase_header DEPS "Packages"

        run_step "Preparing package manager (clearing stale apt locks)" "apt_recover" \
            || { show_step_error; install_pause "Preparing package manager"; }

        if ! run_step "Adding PHP repository" "setup_php_repo"; then
            if ! run_step "Retrying PHP repository with locale override" "LC_ALL=C.UTF-8 setup_php_repo"; then
                show_step_error
                install_pause "Adding PHP repository"
            fi
        fi

        if ! run_step "Updating & upgrading system packages" "apt-get update --allow-releaseinfo-change -o DPkg::Lock::Timeout=180 && DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -o DPkg::Lock::Timeout=180"; then
            echo -e "\e[93mUpdate/upgrade failed. Attempting to fix using alternative mirrors...\033[0m"
            if fix_update_issues; then
                if ! run_step "Re-running system update after mirror fix" "apt-get update --allow-releaseinfo-change -o DPkg::Lock::Timeout=180 && DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -o DPkg::Lock::Timeout=180"; then
                    show_step_error
                    install_pause "System update/upgrade"
                fi
            else
                install_pause "System update/upgrade (mirror fix failed)"
            fi
        fi

        # software-properties-common (only pulled for add-apt-repository) was
        # dropped in Debian 13 and the Debian branch never uses it.
        local _base_tools="software-properties-common git unzip curl wget jq sudo"
        [ "$OS_ID" = "debian" ] && _base_tools="git unzip curl wget jq sudo"
        run_step "Installing base tools (git, curl, wget, unzip, jq)" \
            "apt-get install -y $_base_tools" \
            || { show_step_error; install_pause "Installing base tools"; }

        select_php_target
        PHP_VER="$PHP_TARGET_VER"
        state_set PHP_VER "$PHP_VER"
        echo -e " ${C_DIM}Selected PHP version:${CR} ${C_KEY}${PHP_VER} (>= ${PHP_MIN_VERSION})${CR}"

        if ! run_step "Checking PHP ${PHP_MIN_VERSION}+ availability" "php_candidate_ok"; then
            show_step_error
            echo -e " ${C_BAD}●${CR} ${C_BAD}PHP ${PHP_MIN_VERSION} or newer is not available from apt on this server; lower versions are not installed.${CR}"
            install_pause "PHP ${PHP_MIN_VERSION}+ not available"
        fi

        run_step "Installing PHP ${PHP_VER} (fpm + mysql)" \
            "DEBIAN_FRONTEND=noninteractive apt install -y php${PHP_VER} php${PHP_VER}-cli php${PHP_VER}-fpm php${PHP_VER}-mysql" \
            || { show_step_error; install_pause "Installing PHP ${PHP_VER}"; }

        if ! run_step "Verifying installed PHP is ${PHP_MIN_VERSION}+" "php_version_ok"; then
            show_step_error
            echo -e " ${C_BAD}●${CR} ${C_BAD}Installed PHP is older than ${PHP_MIN_VERSION}.${CR}"
            install_pause "PHP version below ${PHP_MIN_VERSION}"
        fi

        WEBSTACK_CMD="DEBIAN_FRONTEND=noninteractive apt install -y $DB_PKG apache2 libapache2-mod-php${PHP_VER} php${PHP_VER}-mbstring php${PHP_VER}-zip php${PHP_VER}-gd php${PHP_VER}-curl php${PHP_VER}-intl php${PHP_VER}-xml php${PHP_VER}-bcmath"
        if ! run_step "Installing web stack (Apache, MySQL, PHP modules)" "$WEBSTACK_CMD"; then
            run_step "Repairing broken MySQL installation" "repair_mysql" \
                || { show_step_error; install_pause "Repairing MySQL"; }
            run_step "Re-installing web stack" "$WEBSTACK_CMD" \
                || { show_step_error; install_pause "Installing web stack"; }
        fi

        local _other_php="" _pv
        for _pv in 8.5 8.4 8.3 8.2 8.1 8.0 7.4; do
            [ "$_pv" = "$PHP_VER" ] || _other_php="$_other_php php$_pv"
        done
        run_step "Setting PHP ${PHP_VER} as the active version" \
            "a2dismod${_other_php} mpm_event mpm_worker 2>/dev/null; a2enmod php${PHP_VER} mpm_prefork 2>/dev/null; update-alternatives --set php /usr/bin/php${PHP_VER} 2>/dev/null; systemctl restart apache2" \
            || { show_step_error; install_pause "Setting PHP ${PHP_VER} as default"; }

        echo 'phpmyadmin phpmyadmin/dbconfig-install boolean true' | sudo debconf-set-selections
        local pma_pass
        pma_pass=$(openssl rand -base64 18 | tr -dc 'a-zA-Z0-9' | cut -c1-16)
        echo "phpmyadmin phpmyadmin/app-password-confirm password ${pma_pass}" | sudo debconf-set-selections
        echo "phpmyadmin phpmyadmin/mysql/admin-pass password ${pma_pass}" | sudo debconf-set-selections
        echo "phpmyadmin phpmyadmin/mysql/app-pass password ${pma_pass}" | sudo debconf-set-selections
        echo 'phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2' | sudo debconf-set-selections
        run_step "Installing phpMyAdmin" \
            "DEBIAN_FRONTEND=noninteractive apt-get install -y phpmyadmin" \
            || { show_step_error; install_pause "Installing phpMyAdmin"; }

        if [ -f /etc/apache2/conf-available/phpmyadmin.conf ]; then
            sudo rm -f /etc/apache2/conf-available/phpmyadmin.conf
        fi
        sudo ln -s /etc/phpmyadmin/apache.conf /etc/apache2/conf-available/phpmyadmin.conf || {
            echo -e "\e[91mError: Failed to create symbolic link for phpMyAdmin configuration.\033[0m"
            install_pause "phpMyAdmin symlink"
        }

        # libssh2-1 is libssh2-1t64 in Debian 13 - php-ssh2 pulls the right
        # runtime lib itself, so only pin it explicitly on Ubuntu.
        local _libssh2="libssh2-1-dev libssh2-1"
        [ "$OS_ID" = "debian" ] && _libssh2=""
        run_step "Installing extra modules (php-soap, php-ssh2)" \
            "DEBIAN_FRONTEND=noninteractive apt-get install -y php${PHP_VER}-soap php${PHP_VER}-ssh2 $_libssh2" \
            || { show_step_error; install_pause "Installing extra PHP modules"; }

        run_step "Enabling & starting services ($([ "$DB_SERVICE" = "mariadb" ] && echo MariaDB || echo MySQL), Apache)" \
            "systemctl enable $DB_SERVICE.service && systemctl start $DB_SERVICE.service && systemctl enable apache2 && systemctl start apache2" \
            || { show_step_error; install_pause "Enabling core services"; }

        # Port rules, not the 'Apache' UFW profile: Debian's apache2 ships no
        # app profile (Ubuntu's does), so `ufw allow 'Apache'` fails there.
        run_step "Configuring firewall (UFW + Apache)" \
            "apt-get install -y ufw && ufw allow 80/tcp && ufw allow 443/tcp" \
            || { show_step_error; install_pause "Configuring UFW"; }
        run_step "Restarting Apache" "systemctl restart apache2" \
            || { show_step_error; install_pause "Restarting Apache"; }

        mark_phase DEPS
    else
        echo -e " ${C_OK}●${CR} ${C_DIM}Dependencies already installed - skipping.${CR}"
    fi

    run_step "Ensuring cron is installed and running" "ensure_cron" \
        || { show_step_error; install_pause "Installing cron"; }
    # ╰─────────────────────────────────────────────────────────────╯

    # ╭──────────────────────── PHASE: FILES ───────────────────────╮
    if ! phase_done FILES; then
        phase_header FILES "Files"
        ZIP_URL="$(state_get SRC_ZIP_URL)"; [ -z "$ZIP_URL" ] && ZIP_URL="$SRC_ZIP_URL"
        SRC_LABEL_RESUME="$(state_get SRC_LABEL)"; [ -z "$SRC_LABEL_RESUME" ] && SRC_LABEL_RESUME="$SRC_LABEL"
        if [ -d "$BOT_DIR" ]; then
            sudo rm -rf "$BOT_DIR" || {
                echo -e "\e[91mError: Failed to remove existing directory $BOT_DIR.\033[0m"
                install_pause "Cleaning bot directory"
            }
        fi
        sudo mkdir -p "$BOT_DIR"
        if [ ! -d "$BOT_DIR" ]; then
            echo -e "\e[91mError: Failed to create directory $BOT_DIR.\033[0m"
            install_pause "Creating bot directory"
        fi

        TEMP_DIR="/tmp/mirzaprobot"
        rm -rf "$TEMP_DIR"; mkdir -p "$TEMP_DIR"
        run_step "Downloading Mirza (${SRC_LABEL_RESUME})" "wget -O '$TEMP_DIR/bot.zip' '$ZIP_URL'" \
            || { show_step_error; install_pause "Downloading bot files"; }
        run_step "Extracting source files" "unzip -o '$TEMP_DIR/bot.zip' -d '$TEMP_DIR'" \
            || { show_step_error; install_pause "Extracting bot files"; }

        EXTRACTED_DIR=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -1)
        if [ -z "$EXTRACTED_DIR" ] || [ ! -d "$EXTRACTED_DIR" ]; then
            echo -e "\e[91mError: Extracted source folder not found (bad or empty download).\033[0m"
            install_pause "Locating extracted files"
        fi
        purge_installer_dir "$EXTRACTED_DIR"
        move_extracted_files "$EXTRACTED_DIR" "$BOT_DIR" || {
            echo -e "\e[91mError: Failed to move extracted files.\033[0m"
            install_pause "Moving bot files"
        }
        purge_installer_dir "$BOT_DIR"
        rm -rf "$TEMP_DIR"
        sudo chown -R www-data:www-data "$BOT_DIR"
        sudo chmod -R 755 "$BOT_DIR"
        wait
        run_step "Installing PHP dependencies (composer)" "install_php_deps '$BOT_DIR'" \
            || { show_step_error; install_pause "Installing PHP dependencies"; }
        mark_phase FILES
    else
        echo -e " ${C_OK}●${CR} ${C_DIM}Bot files already downloaded - skipping.${CR}"
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ╭──────────────────────── PHASE: DBROOT ──────────────────────╮
    if ! phase_done DBROOT; then
        if [ ! -f "/root/confmirza/dbrootmirza.txt" ] || ! grep -q '\$pass' /root/confmirza/dbrootmirza.txt 2>/dev/null; then
            run_step "Configuring MySQL root access" "setup_mysql_root" \
                || { show_step_error; install_pause "MySQL root setup"; }
        fi
        mark_phase DBROOT
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ── Domain capture (needed for SSL, VHost, config & webhook) ──
    install_banner
    phase_header SSL "SSL certificate"
    domainname="$(state_get DOMAIN)"
    if [ -n "$domainname" ]; then
        echo -e " ${C_DIM}Domain (resumed):${CR} ${C_KEY}${domainname}${CR}"
    else
        if [ -n "$ARG_DOMAIN" ]; then
            domainname="$ARG_DOMAIN"
            echo -e " ${C_DIM}Domain (from --domain):${CR} ${C_KEY}${domainname}${CR}"
        else
            read -p "Enter the domain: " domainname
        fi
        while ! validate_domain "$domainname"; do
            echo -e "\e[91mInvalid domain. Enter a full domain like bot.example.com (no http://, no slash).\033[0m"
            read -p "Enter the domain: " domainname
        done
        # Verify the domain actually points to this server (certbot needs this)
        domain_points_here "$domainname"
        case $? in
            0) echo -e " ${C_OK}●${CR} ${C_OK}Domain resolves to this server.${CR}" ;;
            1) echo -e " ${C_WARN}!${CR} ${C_WARN}Domain does NOT point to this server's IP ($(get_server_ip)).${CR}"
               echo -e " ${C_DIM}Let's Encrypt will fail until the DNS A record points here.${CR}"
               printf " ${C_PROMPT}❯${CR} Continue anyway? ${C_DIM}[y/N]${CR}: "
               read -r _gd
               if [[ ! "$_gd" =~ ^[Yy]$ ]]; then echo -e " ${C_BAD}Aborted. Fix the DNS A record and retry.${CR}"; sleep 1; show_menu; return 1; fi ;;
            2) echo -e " ${C_WARN}!${CR} ${C_WARN}Could not resolve the domain yet (DNS may still be propagating).${CR}"
               printf " ${C_PROMPT}❯${CR} Continue anyway? ${C_DIM}[y/N]${CR}: "
               read -r _gd
               if [[ ! "$_gd" =~ ^[Yy]$ ]]; then echo -e " ${C_BAD}Aborted.${CR}"; sleep 1; show_menu; return 1; fi ;;
        esac
        state_set DOMAIN "$domainname"
    fi
    DOMAIN_NAME="$domainname"
    PATHS=$(cat /root/confmirza/dbrootmirza.txt | grep '$path' | cut -d"'" -f2)

    # ╭──────────────────────── PHASE: SSL ─────────────────────────╮
    if ! phase_done SSL; then
        if [ -f "/etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem" ]; then
            echo -e " ${C_OK}●${CR} ${C_DIM}SSL certificate for ${DOMAIN_NAME} already exists - skipping issuance.${CR}"
        else
            run_step "Opening firewall ports 80 & 443" "ufw allow 80/tcp && ufw allow 443/tcp" \
                || { show_step_error; install_pause "Opening firewall ports"; }
            run_step "Stopping Apache for certificate issuance" "systemctl stop apache2 && systemctl disable apache2" \
                || { show_step_error; install_pause "Stopping Apache"; }
            # Debian's certbot only Suggests the apache plugin; menu renew uses --apache.
            local _cb_pkg=letsencrypt; [ "$OS_ID" = "debian" ] && _cb_pkg="certbot python3-certbot-apache"
            run_step "Installing Let's Encrypt (certbot)" "apt install $_cb_pkg -y && systemctl enable certbot.timer" \
                || { show_step_error; install_pause "Installing certbot"; }

            run_step "Requesting SSL certificate (Let's Encrypt)" \
                "certbot certonly --standalone --non-interactive --agree-tos --register-unsafely-without-email --preferred-challenges http -d $DOMAIN_NAME" \
                || { show_step_error; install_pause "Requesting SSL certificate"; }
        fi
        run_step "Enabling & starting Apache" "systemctl enable apache2 && systemctl start apache2" \
            || { show_step_error; install_pause "Starting Apache"; }
        mark_phase SSL
    else
        echo -e " ${C_OK}●${CR} ${C_DIM}SSL certificate already configured - skipping.${CR}"
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ╭──────────────────────── PHASE: VHOST ───────────────────────╮
    if ! phase_done VHOST; then
        VHOST_FILE="/etc/apache2/sites-available/${DOMAIN_NAME}.conf"
        sudo tee "$VHOST_FILE" > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    DocumentRoot $BOT_DIR
    <Directory $BOT_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    Include /etc/apache2/conf-available/phpmyadmin.conf
    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-access.log combined
</VirtualHost>
EOF
        VHOST_SSL_FILE="/etc/apache2/sites-available/${DOMAIN_NAME}-ssl.conf"
        sudo tee "$VHOST_SSL_FILE" > /dev/null <<EOF
<VirtualHost *:443>
    ServerName $DOMAIN_NAME
    DocumentRoot $BOT_DIR
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem
    <Directory $BOT_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    Include /etc/apache2/conf-available/phpmyadmin.conf
    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-access.log combined
</VirtualHost>
EOF
        run_step "Configuring Apache virtual hosts" \
            "a2ensite '${DOMAIN_NAME}.conf' && a2ensite '${DOMAIN_NAME}-ssl.conf' ; a2dissite 000-default.conf 2>/dev/null ; a2dissite 000-default-le-ssl.conf 2>/dev/null ; a2dissite default-ssl.conf 2>/dev/null ; rm -f /etc/apache2/sites-enabled/000-default.conf /etc/apache2/sites-enabled/000-default-le-ssl.conf /etc/apache2/sites-enabled/default-ssl.conf ; rm -f /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/000-default-le-ssl.conf /etc/apache2/sites-available/default-ssl.conf ; a2enmod ssl ; a2enmod rewrite ; systemctl restart apache2" \
            || { show_step_error; install_pause "Configuring Apache virtual hosts"; }
        mark_phase VHOST
    else
        echo -e " ${C_OK}●${CR} ${C_DIM}Apache virtual hosts already configured - skipping.${CR}"
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ── Bot configuration inputs (token / chat id / botname) ──
    install_banner
    phase_header DB "Bot configuration"
    YOUR_BOT_TOKEN="$(state_get BOT_TOKEN)"
    if [ -n "$YOUR_BOT_TOKEN" ]; then
        echo -e "\e[33m[+] \e[36mBot Token (resumed):\e[0m ${YOUR_BOT_TOKEN:0:10}..."
    else
        if [ -n "$ARG_TOKEN" ]; then
            YOUR_BOT_TOKEN="$ARG_TOKEN"
            echo -e "\e[33m[+] \e[36mBot Token (from --token):\e[0m ${YOUR_BOT_TOKEN:0:10}..."
        else
            printf "\e[33m[+] \e[36mBot Token: \033[0m"
            read YOUR_BOT_TOKEN
        fi
        while [[ ! "$YOUR_BOT_TOKEN" =~ ^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$ ]]; do
            echo -e "\e[91mInvalid bot token format. Please try again.\033[0m"
            printf "\e[33m[+] \e[36mBot Token: \033[0m"
            read YOUR_BOT_TOKEN
        done
        # Live-verify the token with Telegram (getMe)
        while true; do
            validate_token "$YOUR_BOT_TOKEN"
            case $? in
                0) echo -e " ${C_OK}●${CR} ${C_OK}Token verified with Telegram.${CR}"; break ;;
                2) echo -e " ${C_BAD}●${CR} ${C_BAD}Telegram rejected this token (or API unreachable).${CR}"
                   printf " ${C_PROMPT}❯${CR} Re-enter token, or press Enter to keep it anyway: "
                   read -r _t
                   if [ -z "$_t" ]; then break; fi
                   YOUR_BOT_TOKEN="$_t"
                   while [[ ! "$YOUR_BOT_TOKEN" =~ ^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$ ]]; do
                       echo -e "\e[91mInvalid format.\033[0m"; printf " ${C_PROMPT}❯${CR} Bot Token: "; read -r YOUR_BOT_TOKEN
                   done ;;
                *) break ;;
            esac
        done
        state_set BOT_TOKEN "$YOUR_BOT_TOKEN"
    fi

    YOUR_CHAT_ID="$(state_get CHAT_ID)"
    if [ -n "$YOUR_CHAT_ID" ]; then
        echo -e "\e[33m[+] \e[36mChat id (resumed):\e[0m ${YOUR_CHAT_ID}"
    else
        if [ -n "$ARG_ADMIN" ]; then
            YOUR_CHAT_ID="$ARG_ADMIN"
            echo -e "\e[33m[+] \e[36mChat id (from --admin):\e[0m ${YOUR_CHAT_ID}"
        else
            printf "\e[33m[+] \e[36mChat id: \033[0m"
            read YOUR_CHAT_ID
        fi
        while [[ ! "$YOUR_CHAT_ID" =~ ^-?[0-9]+$ ]]; do
            echo -e "\e[91mInvalid chat ID format. Please try again.\033[0m"
            printf "\e[33m[+] \e[36mChat id: \033[0m"
            read YOUR_CHAT_ID
        done
        state_set CHAT_ID "$YOUR_CHAT_ID"
    fi

    YOUR_DOMAIN="$DOMAIN_NAME"
    YOUR_BOTNAME="$(state_get BOTNAME)"
    if [ -n "$YOUR_BOTNAME" ]; then
        echo -e "\e[33m[+] \e[36musernamebot (resumed):\e[0m ${YOUR_BOTNAME}"
    else
        YOUR_BOTNAME="$TG_BOT_USERNAME"
        [ -z "$YOUR_BOTNAME" ] && YOUR_BOTNAME="$(fetch_bot_username "$YOUR_BOT_TOKEN")"
        if [ -n "$YOUR_BOTNAME" ]; then
            echo -e "\e[33m[+] \e[36musernamebot (from token):\e[0m @${YOUR_BOTNAME}"
        else
            echo -e " ${C_BAD}●${CR} ${C_BAD}Could not read the bot username from Telegram.${CR}"
            while true; do
                printf "\e[33m[+] \e[36musernamebot: \033[0m"
                read YOUR_BOTNAME
                if [ "$YOUR_BOTNAME" != "" ]; then
                    break
                else
                    echo -e "\e[91mError: Bot username cannot be empty. Please enter a valid username.\033[0m"
                fi
            done
        fi
        YOUR_BOTNAME="${YOUR_BOTNAME#@}"
        YOUR_BOTNAME="${YOUR_BOTNAME//[[:space:]]/}"
        state_set BOTNAME "$YOUR_BOTNAME"
    fi

    ROOT_PASSWORD=$(cat /root/confmirza/dbrootmirza.txt | grep '$pass' | cut -d"'" -f2)
    ROOT_USER="root"
    echo "SELECT 1" | mysql -u$ROOT_USER -p$ROOT_PASSWORD 2>/dev/null || {
        echo -e "\e[91mError: MySQL connection failed.\033[0m"
        install_pause "MySQL connection"
    }

    if [ "${DB_SERVICE:-mysql}" = "mariadb" ]; then
        # MariaDB rejects the MySQL `IDENTIFIED WITH plugin BY` form; its
        # default plugin for new users is already mysql_native_password.
        MYSQL_IDENT_SQL="IDENTIFIED BY"
    else
        # MySQL 8.4+ disables mysql_native_password by default; fall back to the
        # server default plugin when it is not ACTIVE so CREATE USER does not fail.
        MYSQL_AUTH_PLUGIN="mysql_native_password"
        if ! mysql -u"$ROOT_USER" -p"$ROOT_PASSWORD" -N -B -e \
            "SELECT PLUGIN_STATUS FROM INFORMATION_SCHEMA.PLUGINS WHERE PLUGIN_NAME='mysql_native_password';" 2>/dev/null \
            | grep -qi ACTIVE; then
            MYSQL_AUTH_PLUGIN="caching_sha2_password"
        fi
        MYSQL_IDENT_SQL="IDENTIFIED WITH $MYSQL_AUTH_PLUGIN BY"
    fi

    randomdbpass=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)
    randomdbdb=$(openssl rand -base64 10 | tr -dc 'a-zA-Z' | cut -c1-8)
    dbname="mirzaprobot"

    # ╭──────────────────────── PHASE: DB ──────────────────────────╮
    if ! phase_done DB; then
        dbuser="$(state_get DBUSER)"
        dbpass="$(state_get DBPASS)"
        if [ -z "$dbuser" ] || [ -z "$dbpass" ]; then
            clear
            if [ -n "$ARG_DBUSER" ]; then
                dbuser="$ARG_DBUSER"
                echo -e "\e[32mDatabase username (from --db-user):\e[0m ${dbuser}"
            else
                echo -e "\n\e[32mPlease enter the database username!\033[0m"
                printf "[+] Default user name is \e[91m${randomdbdb}\e[0m ( let it blank to use this user name ): "
                read dbuser
            fi
            if [ "$dbuser" = "" ]; then
                dbuser=$randomdbdb
            fi
            if ! valid_db_ident "$dbuser"; then
                echo -e " ${C_WARN}!${CR} ${C_WARN}Invalid DB username (use only A-Z a-z 0-9 _). Using generated name.${CR}"
                dbuser=$randomdbdb
            fi
            if [ -n "$ARG_DBPASS" ]; then
                dbpass="$ARG_DBPASS"
                echo -e "\e[32mDatabase password (from --db-pass): [hidden]\033[0m"
            else
                echo -e "\n\e[32mPlease enter the database password!\033[0m"
                printf "[+] Default password is \e[91m${randomdbpass}\e[0m ( let it blank to use this password ): "
                read dbpass
            fi
            if [ "$dbpass" = "" ]; then
                dbpass=$randomdbpass
            fi
            if ! valid_db_pass "$dbpass"; then
                echo -e " ${C_WARN}!${CR} ${C_WARN}Password has unsafe characters or is too short (need 6+, A-Z a-z 0-9 _). Using generated password.${CR}"
                dbpass=$randomdbpass
            fi
            state_set DBUSER "$dbuser"
            state_set DBPASS "$dbpass"
        else
            echo -e " ${C_OK}●${CR} ${C_DIM}Database credentials resumed.${CR}"
        fi
        # Idempotent: safe to re-run (IF NOT EXISTS), so a resumed install never breaks here
        run_step "Creating database & user" \
            "mysql -u root -p$ROOT_PASSWORD -e \"CREATE DATABASE IF NOT EXISTS $dbname;\" && mysql -u root -p$ROOT_PASSWORD -e \"CREATE USER IF NOT EXISTS '$dbuser'@'%' $MYSQL_IDENT_SQL '$dbpass'; GRANT ALL PRIVILEGES ON $dbname.* TO '$dbuser'@'%'; FLUSH PRIVILEGES;\" && mysql -u root -p$ROOT_PASSWORD -e \"CREATE USER IF NOT EXISTS '$dbuser'@'localhost' $MYSQL_IDENT_SQL '$dbpass'; GRANT ALL PRIVILEGES ON $dbname.* TO '$dbuser'@'localhost'; FLUSH PRIVILEGES;\"" \
            || { show_step_error; install_pause "Creating database/user"; }
        mark_phase DB
    else
        dbuser="$(state_get DBUSER)"
        dbpass="$(state_get DBPASS)"
        echo -e " ${C_OK}●${CR} ${C_DIM}Database already created - skipping.${CR}"
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ╭──────────────────────── PHASE: CONFIG ──────────────────────╮
    if ! phase_done CONFIG; then
        wait
        sleep 1
        file_path="/var/www/html/mirzaprobotconfig/config.php"
        if [ -f "$file_path" ]; then
            rm "$file_path" || {
                echo -e "\e[91mError: Failed to delete old config.php.\033[0m"
                install_pause "Removing old config.php"
            }
        fi
        sleep 1
        cat <<EOF > /var/www/html/mirzaprobotconfig/config.php
<?php
// This variable added for high load panels which their response time is long and bot can't communicate with online panel!
// null for default settings
\$request_exec_timeout = null;
\$dbhost = 'localhost';
\$dbname = '$dbname';
\$usernamedb = '$dbuser';
\$passworddb = '$dbpass';
\$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, ];
\$dsn = "mysql:host=\$dbhost;dbname=\$dbname;charset=utf8mb4";
try { \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options); \$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"); } catch (\PDOException \$e) { error_log("Database connection failed: " . \$e->getMessage()); die("error: database connection failed"); }
\$APIKEY = '${YOUR_BOT_TOKEN}';
\$adminnumber = '${YOUR_CHAT_ID}';
\$domainhosts = '${YOUR_DOMAIN}';
\$usernamebot = '${YOUR_BOTNAME}';
?>
EOF
        sudo chown www-data:www-data /var/www/html/mirzaprobotconfig/config.php 2>/dev/null
        sudo chmod 640 /var/www/html/mirzaprobotconfig/config.php 2>/dev/null
        mark_phase CONFIG
    else
        echo -e " ${C_OK}●${CR} ${C_DIM}config.php already written - skipping.${CR}"
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ╭──────────────────────── PHASE: WEBHOOK ─────────────────────╮
    if ! phase_done WEBHOOK; then
        sleep 1
        run_step "Setting Telegram webhook" \
            "curl -s -F \"url=https://${YOUR_DOMAIN}/index.php\" \"https://api.telegram.org/bot${YOUR_BOT_TOKEN}/setWebhook\"" \
            || { show_step_error; install_pause "Setting Telegram webhook"; }

        MESSAGE="✅ The Mirza bot is installed! for start the bot send /start command."
        curl -s -X POST "https://api.telegram.org/bot${YOUR_BOT_TOKEN}/sendMessage" -d chat_id="${YOUR_CHAT_ID}" -d text="$MESSAGE" > /dev/null 2>&1
        sleep 3
        run_step "Starting Apache" "systemctl start apache2" \
            || { show_step_error; install_pause "Starting Apache"; }
        sleep 5
        run_step "Initializing database tables" "cd '$BOT_DIR' && php${PHP_VER} table.php" \
            || { show_step_error; install_pause "Initializing database tables"; }
        mark_phase WEBHOOK
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ── Done ──
    mark_phase COMPLETE
    clear
    banner "${C_OK}Installed${CR}" "${C_DIM}took $(_fmt_secs $(( $(date +%s) - INSTALL_STARTED )))${CR}"
    _phase_line COMPLETE
    _rule
    printf " ${C_OK}✔${CR} ${C_TITLE}Mirza %s is running. Send /start to %s to open the admin panel.${CR}\n" \
        "$(get_installed_version)" "$([ -n "$YOUR_BOTNAME" ] && echo "@${YOUR_BOTNAME#@}" || echo "your bot")"
    _sec "Open"
    [ -n "$YOUR_BOTNAME" ] && _kv "Bot" "https://t.me/${YOUR_BOTNAME#@}"
    _kv "Admin" "${YOUR_CHAT_ID}"
    _kv "Website" "https://${YOUR_DOMAIN}"
    _kv "phpMyAdmin" "https://${YOUR_DOMAIN}/phpmyadmin"
    _sec "Saved on this server"
    _kv "Database" "${dbname} ${C_DIM}· user${CR} ${dbuser} ${C_DIM}· password in config.php${CR}"
    _kv "DB root" "/root/confmirza/dbrootmirza.txt"
    _kv "Config" "${BOT_DIR}/config.php"
    _sec "Next"
    printf " ${C_KEY}%-14s${CR} %s\n" "mirza" "open the menu any time"
    printf " ${C_KEY}%-14s${CR} %s\n" "mirza backup" "send a database backup to Telegram"
    echo ""
    _rule
    echo ""

    chmod +x /root/install.sh
    ln -sf /root/install.sh /usr/local/bin/mirza
    self_update_script
}
function update_bot() {
    clear
    banner
    BOT_DIR="/var/www/html/mirzaprobotconfig"
    if [ ! -d "$BOT_DIR" ]; then
        _sec "Update"
        printf " ${C_BAD}●${CR} ${C_BAD}Mirza is not installed. Install it first.${CR}\n"
        sleep 2
        show_menu
        return 1
    fi

    # ── Show current version + choose source (has Back option) ──
    local current
    current=$(get_installed_version); [ -z "$current" ] && current="unknown"
    _sec "Update"
    printf " ${C_DIM}Currently installed:${CR} ${C_OK}%s${CR}\n" "$current"
    if ! ensure_connectivity; then
        printf " ${C_BAD}●${CR} ${C_BAD}No internet connection (even after DNS reset). Try again later.${CR}\n"
        sleep 2; show_menu; return 1
    fi
    choose_source
    local _rc=$?
    if [ "$_rc" -eq 2 ]; then show_menu; return 0; fi
    if [ "$_rc" -ne 0 ]; then sleep 2; show_menu; return 1; fi
    local ZIP_URL="$SRC_ZIP_URL" TARGET_LABEL="$SRC_LABEL"

    echo ""
    echo -e " ${C_DIM}Update target:${CR} ${C_KEY}${TARGET_LABEL}${CR}"
    _sec "Updating Mirza"
    run_step "Updating system packages" "apt update --allow-releaseinfo-change && apt upgrade -y" \
        || { show_step_error; echo -e "\e[91mError updating the server. Exiting...\033[0m"; exit 1; }
    run_step "Ensuring cron is installed and running" "ensure_cron" \
        || { show_step_error; echo -e "\e[91mError: Failed to install or start cron.\033[0m"; exit 1; }
    echo -e "\e[92mServer packages updated successfully...\033[0m\n"
    if ! upgrade_php_if_needed; then
        echo -e "\e[91mError: this version of Mirza requires PHP ${PHP_MIN_VERSION}+. The update was aborted and your bot files were left untouched.\033[0m"
        sleep 2; show_menu; return 1
    fi
    TEMP_DIR="/tmp/mirzaprobot_update"
    rm -rf "$TEMP_DIR"; mkdir -p "$TEMP_DIR"
    run_step "Downloading ${TARGET_LABEL}" "wget -q -O '$TEMP_DIR/bot.zip' '$ZIP_URL'" \
        || { show_step_error; echo -e "\e[91mError: Failed to download update package.\033[0m"; exit 1; }
    run_step "Extracting update package" "unzip -o -q '$TEMP_DIR/bot.zip' -d '$TEMP_DIR'" \
        || { show_step_error; echo -e "\e[91mError: Failed to extract update package.\033[0m"; exit 1; }
    EXTRACTED_DIR=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -1)
    if [ -z "$EXTRACTED_DIR" ] || [ ! -d "$EXTRACTED_DIR" ]; then
        echo -e "\e[91mError: Extracted update folder not found. Aborting before touching the current install.\033[0m"
        rm -rf "$TEMP_DIR"; sleep 2; show_menu; return 1
    fi
    # Build vendor/ inside the extracted copy first. The live install is still
    # untouched at this point, so a composer or network failure aborts the update
    # instead of leaving the bot without its dependencies.
    run_step "Installing PHP dependencies (composer)" "install_php_deps '$EXTRACTED_DIR'" \
        || { show_step_error
             echo -e "\e[91mError: Failed to install PHP dependencies. The update was aborted and your current installation was left untouched.\033[0m"
             rm -rf "$TEMP_DIR"; sleep 2; show_menu; return 1; }
    CONFIG_PATH="$BOT_DIR/config.php"
    TEMP_CONFIG="/root/mirzapro_config_backup.php"
    if [ -f "$CONFIG_PATH" ]; then
        cp "$CONFIG_PATH" "$TEMP_CONFIG" || {
            echo -e "\e[91mConfig file backup failed!\033[0m"
            exit 1
        }
    else
        echo -e "\e[93mWarning: config.php not found. Proceeding without backup.\033[0m"
    fi
    LANG_OVERRIDE_BACKUP="/root/mirzapro_lang_override_backup"
    rm -rf "$LANG_OVERRIDE_BACKUP"
    [ -d "$BOT_DIR/lang/override" ] && cp -a "$BOT_DIR/lang/override" "$LANG_OVERRIDE_BACKUP"
    QR_BACKGROUND_BACKUP="/root/mirzapro_custom_qr.jpg"
    rm -f "$QR_BACKGROUND_BACKUP"
    [ -f "$BOT_DIR/custom.jpg" ] && cp "$BOT_DIR/custom.jpg" "$QR_BACKGROUND_BACKUP"
    run_step "Backing up vpnbots" "backup_vpnbots '$BOT_DIR'" \
        || { show_step_error
             echo -e "\e[91mError: Failed to backup vpnbots.\033[0m"
             rm -rf "$TEMP_DIR"; sleep 2; show_menu; return 1; }
    _vpnbot_live=$(vpnbot_instance_count "$BOT_DIR/vpnbot")
    _vpnbot_bak=$(vpnbot_instance_count "$VPNBOT_BACKUP")
    if [ "$_vpnbot_live" -gt 0 ] && [ "$_vpnbot_bak" -lt "$_vpnbot_live" ]; then
        echo -e "\e[91mError: vpnbot backup incomplete ($_vpnbot_bak/$_vpnbot_live). Update aborted.\033[0m"
        rm -rf "$TEMP_DIR"; sleep 2; show_menu; return 1
    fi
    sudo rm -rf "$BOT_DIR" || {
        echo -e "\e[91mFailed to remove old bot files!\033[0m"
        echo -e "\e[93mvpnbot backup: ${VPNBOT_BACKUP}\033[0m"
        exit 1
    }
    sudo mkdir -p "$BOT_DIR"
    purge_installer_dir "$EXTRACTED_DIR"
    purge_installer_dir "$BOT_DIR"
    move_extracted_files "$EXTRACTED_DIR" "$BOT_DIR" || {
        echo -e "\e[91mFile transfer failed!\033[0m"
        echo -e "\e[93mvpnbot backup: ${VPNBOT_BACKUP}\033[0m"
        exit 1
    }
    purge_installer_dir "$BOT_DIR"
    if [ -f "$TEMP_CONFIG" ]; then
        sudo mv "$TEMP_CONFIG" "$CONFIG_PATH" || {
            echo -e "\e[91mConfig file restore failed!\033[0m"
            echo -e "\e[93mvpnbot backup: ${VPNBOT_BACKUP}\033[0m"
            exit 1
        }
    fi
    if [ -f "$CONFIG_PATH" ] && grep -q 'PDO::MYSQL_ATTR_INIT_COMMAND' "$CONFIG_PATH"; then
        sed -E '/^[[:space:]]*PDO::MYSQL_ATTR_INIT_COMMAND => "[^"]*",[[:space:]]*$/d; s/ PDO::MYSQL_ATTR_INIT_COMMAND => "[^"]*",//; s/(\$pdo = new PDO\(\$dsn, \$usernamedb, \$passworddb, \$options\);)/\1 $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");/' \
            "$CONFIG_PATH" > "$CONFIG_PATH.new" && php -l "$CONFIG_PATH.new" >/dev/null 2>&1 \
            && sudo mv "$CONFIG_PATH.new" "$CONFIG_PATH" || sudo rm -f "$CONFIG_PATH.new"
    fi
    if [ -d "$LANG_OVERRIDE_BACKUP" ]; then
        sudo rm -rf "$BOT_DIR/lang/override"
        sudo mv "$LANG_OVERRIDE_BACKUP" "$BOT_DIR/lang/override"
    fi
    if [ -f "$QR_BACKGROUND_BACKUP" ]; then
        sudo cp "$QR_BACKGROUND_BACKUP" "$BOT_DIR/images.jpg"
        sudo mv "$QR_BACKGROUND_BACKUP" "$BOT_DIR/custom.jpg"
    fi
    run_step "Restoring vpnbots" "restore_vpnbots '$BOT_DIR'" \
        || { show_step_error
             echo -e "\e[91mError: Failed to restore vpnbots. Backup: ${VPNBOT_BACKUP}\033[0m"; }
    _vpnbot_restored=$(vpnbot_instance_count "$BOT_DIR/vpnbot")
    if [ "$_vpnbot_bak" -gt 0 ] && [ "$_vpnbot_restored" -lt "$_vpnbot_bak" ]; then
        echo -e "\e[91mError: vpnbot restore incomplete ($_vpnbot_restored/$_vpnbot_bak). Backup kept at ${VPNBOT_BACKUP}\033[0m"
    else
        rm -rf "$VPNBOT_BACKUP"
    fi
    if [ -f "$BOT_DIR/install.sh" ]; then
        sed -i 's/\r$//' "$BOT_DIR/install.sh"
        if bash -n "$BOT_DIR/install.sh" 2>/dev/null; then
            sudo cp "$BOT_DIR/install.sh" /root/install.sh
            sudo sed -i 's/\r$//' /root/install.sh
            echo -e "\n\e[92mCopied latest install.sh to /root/install.sh.\033[0m"
        else
            echo -e "\n\e[91mWarning: downloaded install.sh failed syntax check; keeping the existing /root/install.sh.\033[0m"
        fi
    else
        echo -e "\n\e[91mWarning: install.sh not found in update files.\033[0m"
    fi
    sudo chown -R www-data:www-data "$BOT_DIR"
    sudo chmod -R 755 "$BOT_DIR"
    DOMAIN_NAME=""
    if [ -f "$CONFIG_PATH" ]; then
        DOMAIN_NAME=$(grep "^\$domainhosts" "$CONFIG_PATH" | cut -d"'" -f2 | cut -d'/' -f1)
    fi
    if [ -n "$DOMAIN_NAME" ]; then
        echo -e "\e[33mUpdating Apache VirtualHost configuration for domain: $DOMAIN_NAME\033[0m"
        VHOST_FILE="/etc/apache2/sites-available/${DOMAIN_NAME}.conf"
        sudo tee "$VHOST_FILE" > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    DocumentRoot $BOT_DIR
    <Directory $BOT_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    Include /etc/apache2/conf-available/phpmyadmin.conf
    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-access.log combined
</VirtualHost>
EOF
        VHOST_SSL_FILE="/etc/apache2/sites-available/${DOMAIN_NAME}-ssl.conf"
        sudo tee "$VHOST_SSL_FILE" > /dev/null <<EOF
<VirtualHost *:443>
    ServerName $DOMAIN_NAME
    DocumentRoot $BOT_DIR
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem
    <Directory $BOT_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    Include /etc/apache2/conf-available/phpmyadmin.conf
    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-access.log combined
</VirtualHost>
EOF
        if ! sudo apache2ctl -S 2>/dev/null | grep -q "$DOMAIN_NAME"; then
            sudo a2ensite "${DOMAIN_NAME}.conf" 2>/dev/null || true
            sudo a2ensite "${DOMAIN_NAME}-ssl.conf" 2>/dev/null || true
            echo -e "\e[33mCleaning up conflicting default Apache sites...\033[0m"
            sudo a2dissite 000-default.conf 2>/dev/null || true
            sudo a2dissite 000-default-le-ssl.conf 2>/dev/null || true
            sudo a2dissite default-ssl.conf 2>/dev/null || true
            sudo rm -f /etc/apache2/sites-enabled/000-default* 2>/dev/null || true
            sudo rm -f /etc/apache2/sites-enabled/default-ssl* 2>/dev/null || true
            sudo rm -f /etc/apache2/sites-available/000-default.conf 2>/dev/null || true
            sudo rm -f /etc/apache2/sites-available/000-default-le-ssl.conf 2>/dev/null || true
            sleep 3
            sudo a2enmod ssl 2>/dev/null || true
        fi
        sudo a2enmod rewrite 2>/dev/null || true
        sudo a2enmod ssl 2>/dev/null || true
        if sudo apache2ctl configtest >/dev/null 2>&1; then
            sudo systemctl restart apache2 || {
                echo -e "\e[91mWarning: Failed to restart Apache2 after updating VirtualHost.\033[0m"
            }
            echo -e "\e[92mVirtualHost configuration updated and Apache restarted.\033[0m"
        else
            echo -e "\e[93mWarning: Apache configuration test failed. Skipping restart.\033[0m"
            sudo apache2ctl configtest
        fi
    fi
    if [ -f "$CONFIG_PATH" ]; then
        URL_PATH=$(grep "^\$domainhosts" "$CONFIG_PATH" | cut -d"'" -f2)
        if [ -n "$URL_PATH" ]; then
            run_step "Updating database tables" "curl -s 'https://$URL_PATH/table.php' > /dev/null" \
                || echo -e "\e[91mSetup script execution failed! Check logs.\033[0m"
        fi
        run_step "Setting vpnbot webhooks" "set_vpnbot_webhooks '$CONFIG_PATH'" \
            || echo -e "\e[93mWarning: vpnbot webhook update failed.\033[0m"
    fi
    rm -rf "$TEMP_DIR"
    echo -e "\n\e[92mMirza Bot updated to latest version successfully!\033[0m"
    if [ -f "/root/install.sh" ]; then
        sudo chmod +x /root/install.sh
        sudo ln -sf /root/install.sh /usr/local/bin/mirza
        echo -e "\e[92mEnsured /root/install.sh is executable and 'mirza' command is linked.\033[0m"
    else
        echo -e "\e[91mError: /root/install.sh not found after update attempt.\033[0m"
    fi
}
function remove_bot() {
    echo -e "\e[33mStarting Mirza Bot removal process...\033[0m"
    set_db_vars
    LOG_FILE="/var/log/remove_bot.log"
    echo "Log file: $LOG_FILE" > "$LOG_FILE"
    BOT_DIR="/var/www/html/mirzaprobotconfig"
    if [ ! -d "$BOT_DIR" ]; then
        echo -e "\e[31m[ERROR]\033[0m Mirza Bot is not installed (/var/www/html/mirzaprobotconfig not found)." | tee -a "$LOG_FILE"
        echo -e "\e[33mNothing to remove. Exiting...\033[0m" | tee -a "$LOG_FILE"
        sleep 2
        exit 1
    fi
    read -p "Are you sure you want to remove Mirza Bot and its dependencies? (y/n): " choice
    if [[ ! "$choice" =~ ^[Yy]$ ]]; then
        echo "Aborting..." | tee -a "$LOG_FILE"
        exit 0
    fi
    echo "Removing Mirza Bot..." | tee -a "$LOG_FILE"
    if command -v crontab >/dev/null 2>&1 || [ -x /usr/bin/crontab ]; then
        local _cb
        _cb="$(command -v crontab || echo /usr/bin/crontab)"
        if id www-data >/dev/null 2>&1; then
            "$_cb" -u www-data -l 2>/dev/null | grep -v '/cronbot/' | "$_cb" -u www-data - 2>/dev/null || true
        fi
        "$_cb" -l 2>/dev/null | grep -v '/cronbot/' | "$_cb" - 2>/dev/null || true
        echo -e "\e[92mRemoved Mirza cron jobs.\033[0m" | tee -a "$LOG_FILE"
    fi
    CONFIG_PATH="/var/www/html/mirzaprobotconfig/config.php"
    if [ -f "$CONFIG_PATH" ]; then
        sudo shred -u -n 5 "$CONFIG_PATH" && echo -e "\e[92mConfig file securely removed: $CONFIG_PATH\033[0m" | tee -a "$LOG_FILE" || {
            echo -e "\e[91mFailed to securely remove config file: $CONFIG_PATH\033[0m" | tee -a "$LOG_FILE"
        }
    fi
    if [ -d "$BOT_DIR" ]; then
        sudo rm -rf "$BOT_DIR" && echo -e "\e[92mBot directory removed: $BOT_DIR\033[0m" | tee -a "$LOG_FILE" || {
            echo -e "\e[91mFailed to remove bot directory: $BOT_DIR. Exiting...\033[0m" | tee -a "$LOG_FILE"
            exit 1
        }
    fi
    echo -e "\e[33mRemoving MySQL/MariaDB and database...\033[0m" | tee -a "$LOG_FILE"
    sudo systemctl stop "$DB_SERVICE" 2>/dev/null
    sudo systemctl disable "$DB_SERVICE" 2>/dev/null
    sudo systemctl daemon-reload
    sudo apt --fix-broken install -y
    # phpMyAdmin pulls dbconfig-mysql, which depends on the default mysql
    # client; on Debian that dep (mariadb-client) cannot be purged while
    # dbconfig-mysql stays, and apt aborts the whole transaction. Remove the
    # dependents with the database so the resolver never dead-ends.
    sudo apt-get purge -y mysql-server mysql-client mysql-common mysql-server-core-* mysql-client-core-* mariadb-server mariadb-client mariadb-common phpmyadmin dbconfig-mysql dbconfig-common dbconfig-no-thanks
    sudo rm -rf /etc/mysql /var/lib/mysql /var/log/mysql /var/log/mysql.* /usr/lib/mysql /usr/include/mysql /usr/share/mysql
    [ -f /lib/systemd/system/mysql.service ] && sudo rm /lib/systemd/system/mysql.service
    [ -f /etc/init.d/mysql ] && sudo rm /etc/init.d/mysql
    sudo dpkg --remove --force-remove-reinstreq mysql-server mysql-server-8.0 mysql-server-8.4 2>/dev/null
    sudo find /etc/systemd /lib/systemd /usr/lib/systemd -name "*mysql*" -exec rm -f {} \;
    sudo apt-get purge -y 'mysql-server*' 'mysql-client*'
    sudo apt-get purge -y mysql-common php-mysql php8.2-mysql php8.3-mysql php8.4-mysql php8.5-mysql php-mariadb-mysql-kbs
    sudo apt-get autoremove --purge -y
    sudo apt-get clean
    sudo apt-get update --allow-releaseinfo-change
    echo -e "\e[92mMySQL has been completely removed.\033[0m" | tee -a "$LOG_FILE"
    echo -e "\e[33mRemoving PHPMyAdmin...\033[0m" | tee -a "$LOG_FILE"
    if dpkg -s phpmyadmin &>/dev/null; then
        sudo apt-get purge -y phpmyadmin && echo -e "\e[92mPHPMyAdmin removed.\033[0m" | tee -a "$LOG_FILE"
        sudo apt-get autoremove -y && sudo apt-get autoclean -y
    else
        echo -e "\e[93mPHPMyAdmin is not installed.\033[0m" | tee -a "$LOG_FILE"
    fi
    echo -e "\e[33mRemoving Apache...\033[0m" | tee -a "$LOG_FILE"
    sudo systemctl stop apache2 || {
        echo -e "\e[91mFailed to stop Apache. Continuing anyway...\033[0m" | tee -a "$LOG_FILE"
    }
    sudo systemctl disable apache2 || {
        echo -e "\e[91mFailed to disable Apache. Continuing anyway...\033[0m" | tee -a "$LOG_FILE"
    }
    sudo apt-get purge -y apache2 apache2-utils apache2-bin apache2-data libapache2-mod-php* || {
        echo -e "\e[91mFailed to purge Apache packages.\033[0m" | tee -a "$LOG_FILE"
    }
    sudo apt-get autoremove --purge -y
    sudo apt-get autoclean -y
    sudo rm -rf /etc/apache2 /var/www/html
    echo -e "\e[33mRemoving Apache and PHP configurations...\033[0m" | tee -a "$LOG_FILE"
    sudo a2disconf phpmyadmin.conf &>/dev/null
    sudo rm -f /etc/apache2/conf-available/phpmyadmin.conf
    echo -e "\e[33mRemoving additional packages...\033[0m" | tee -a "$LOG_FILE"
    # Patterns, not versioned names: apt aborts the whole removal when one name
    # is unknown (trixie renamed libssh2-1 to libssh2-1t64).
    sudo apt-get remove -y 'php*-soap' 'php*-ssh2' \
        && echo -e "\e[92mRemoved additional PHP packages.\033[0m" | tee -a "$LOG_FILE" || echo -e "\e[93mSome additional PHP packages may not be installed.\033[0m" | tee -a "$LOG_FILE"
    echo -e "\e[33mResetting firewall rules (except SSL)...\033[0m" | tee -a "$LOG_FILE"
    sudo ufw delete allow 'Apache' 2>/dev/null
    sudo ufw delete allow 80/tcp 2>/dev/null
    sudo ufw delete allow 443/tcp 2>/dev/null
    sudo ufw reload 2>/dev/null
    # Clear Mirza install state so a fresh install is allowed afterwards
    sudo rm -rf /root/confmirza
    echo -e "\e[92mMirza Bot, MySQL, and their dependencies have been completely removed.\033[0m" | tee -a "$LOG_FILE"
}

function migrate_to_pro() {
    clear
    echo -e "\033[1;33mStarting Migration from Free to Pro Version...\033[0m"
    set_db_vars
    if ! ensure_connectivity; then
        echo -e " ${C_BAD}●${CR} ${C_BAD}No internet connection (even after DNS reset). Aborting.${CR}"
        sleep 2; show_menu; return 1
    fi
    OLD_BOT_DIR="/var/www/html/mirzabotconfig"
    if [ ! -d "$OLD_BOT_DIR" ]; then
        echo -e "\033[31m[ERROR] Free version source code not found in $OLD_BOT_DIR.\033[0m"
        echo -e "\033[33mMake sure the free version is installed.\033[0m"
        exit 1
    fi
    if ! systemctl is-active --quiet mysql; then
        echo -e "\033[31m[ERROR] MySQL service is not active or not installed.\033[0m"
        echo -e "\033[33mPlease ensure MySQL is running locally.\033[0m"
        exit 1
    else
        echo -e "\033[32mMySQL is running.\033[0m"
    fi
    echo ""
    read -p "Are you sure you want to migrate to the Pro version? (y/n): " confirm_mig
    if [[ "$confirm_mig" != "y" && "$confirm_mig" != "Y" ]]; then
        echo -e "\033[31mMigration aborted.\033[0m"
        exit 0
    fi
    echo ""
    read -p "Have you created a backup of your database? (y/n): " confirm_backup
    if [[ "$confirm_backup" != "y" && "$confirm_backup" != "Y" ]]; then
        echo -e "\033[31mPlease create a backup first!\033[0m"
        exit 1
    fi
    BACKUP_FILE="/root/mirzabot_backup.sql"
    if [ ! -f "$BACKUP_FILE" ]; then
        echo -e "\033[31m[ERROR] Backup file not found at $BACKUP_FILE\033[0m"
        echo -e "\033[33mPlease run the 'mirza' command (Free Version Script) and use option 4 to create a backup.\033[0m"
        exit 1
    else
        echo -e "\033[32mBackup file found.\033[0m"
    fi
    echo ""
    echo -e "\033[43;30m[WARNING] Additional Bots Notice\033[0m"
    echo -e "\033[33mThis migration process will reconfigure Apache for the Pro version.\033[0m"
    echo -e "\033[33mOnly the main bot (mirzabotconfig) will be migrated.\033[0m"
    echo -e "\033[33mExisting Additional Bots in /var/www/html/ might stop working.\033[0m"
    echo -e "\033[36mFound directories:\033[0m"
    ls -d /var/www/html/*/ 2>/dev/null | grep -v "mirzabotconfig"
    echo ""
    read -p "Do you understand and want to proceed? (y/n): " confirm_add
    if [[ "$confirm_add" != "y" && "$confirm_add" != "Y" ]]; then
        echo -e "\033[31mMigration aborted.\033[0m"
        exit 0
    fi
    echo -e "\n\033[36mChecking Database Credentials...\033[0m"
    ROOT_CRED_FILE="/root/confmirza/dbrootmirza.txt"
    ROOT_PASS=""
    ROOT_USER="root"
    if [ -f "$ROOT_CRED_FILE" ]; then
        ROOT_PASS=$(grep '$pass' "$ROOT_CRED_FILE" | cut -d"'" -f2)
    fi
    if [ -z "$ROOT_PASS" ]; then
        echo -e "\033[33mRoot password not found in config file.\033[0m"
        read -s -p "Please enter MySQL root password: " ROOT_PASS
        echo ""
    fi
    if ! mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "SELECT 1;" &>/dev/null; then
        echo -e "\033[31m[ERROR] Incorrect MySQL root password. Migration stopped.\033[0m"
        exit 1
    fi
    # MySQL 8.4+ disables mysql_native_password by default; fall back to the server
    # default plugin when it is not ACTIVE so CREATE USER does not fail.
    if [ "${DB_SERVICE:-mysql}" = "mariadb" ]; then
        MYSQL_IDENT_SQL="IDENTIFIED BY"
    else
        MYSQL_AUTH_PLUGIN="mysql_native_password"
        if ! mysql -u "$ROOT_USER" -p"$ROOT_PASS" -N -B -e \
            "SELECT PLUGIN_STATUS FROM INFORMATION_SCHEMA.PLUGINS WHERE PLUGIN_NAME='mysql_native_password';" 2>/dev/null \
            | grep -qi ACTIVE; then
            MYSQL_AUTH_PLUGIN="caching_sha2_password"
        fi
        MYSQL_IDENT_SQL="IDENTIFIED WITH $MYSQL_AUTH_PLUGIN BY"
    fi
    echo -e "\033[32mDatabase connection successful.\033[0m"
    OLD_DB="mirzabot"
    NEW_DB="mirzaprobot"
    if ! mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "USE $OLD_DB;" &>/dev/null; then
        echo -e "\033[31m[ERROR] Database '$OLD_DB' not found!\033[0m"
        exit 1
    fi
    echo -e "\033[33mCleaning up old tables (setting, admin, channels)...\033[0m"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" "$OLD_DB" -e "DROP TABLE IF EXISTS setting, admin, channels;"
    echo -e "\033[33mUpdating panel status...\033[0m"
    if mysql -u "$ROOT_USER" -p"$ROOT_PASS" "$OLD_DB" -e "DESCRIBE marzban_panel;" &>/dev/null; then
         mysql -u "$ROOT_USER" -p"$ROOT_PASS" "$OLD_DB" -e "UPDATE marzban_panel SET status = 'active';"
    fi
    echo -e "\033[33mMigrating Database from $OLD_DB to $NEW_DB...\033[0m"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "CREATE DATABASE IF NOT EXISTS $NEW_DB;"
    TABLES=$(mysql -u "$ROOT_USER" -p"$ROOT_PASS" -N -e "SHOW TABLES FROM $OLD_DB")
    for t in $TABLES; do
        mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "RENAME TABLE $OLD_DB.$t TO $NEW_DB.$t"
    done
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "DROP DATABASE IF EXISTS $OLD_DB;"
    echo -e "\033[32mDatabase migrated successfully.\033[0m"
    OLD_CONFIG="/var/www/html/mirzabotconfig/config.php"
    OLD_DB_USER=$(grep '$usernamedb' "$OLD_CONFIG" | cut -d"'" -f2)
    if [ -n "$OLD_DB_USER" ]; then
        echo -e "\033[33mRemoving old database user ($OLD_DB_USER)...\033[0m"
        mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "DROP USER IF EXISTS '$OLD_DB_USER'@'localhost';"
        mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "DROP USER IF EXISTS '$OLD_DB_USER'@'%';"
    fi
    NEW_DB_USER=$(openssl rand -base64 10 | tr -dc 'a-zA-Z' | cut -c1-8)
    NEW_DB_PASS=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | cut -c1-10)
    echo -e "\033[33mCreating new database user...\033[0m"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "CREATE USER '$NEW_DB_USER'@'localhost' $MYSQL_IDENT_SQL '$NEW_DB_PASS';"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "GRANT ALL PRIVILEGES ON $NEW_DB.* TO '$NEW_DB_USER'@'localhost';"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "CREATE USER '$NEW_DB_USER'@'%' $MYSQL_IDENT_SQL '$NEW_DB_PASS';"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "GRANT ALL PRIVILEGES ON $NEW_DB.* TO '$NEW_DB_USER'@'%';"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "FLUSH PRIVILEGES;"
    echo -e "\033[33mReading old configuration...\033[0m"
    OLD_API_KEY=$(grep '$APIKEY' "$OLD_CONFIG" | cut -d"'" -f2)
    OLD_ADMIN_ID=$(grep '$adminnumber' "$OLD_CONFIG" | cut -d"'" -f2)
    OLD_BOT_NAME=$(grep '$usernamebot' "$OLD_CONFIG" | cut -d"'" -f2)
    OLD_DOMAIN_FULL=$(grep '$domainhosts' "$OLD_CONFIG" | cut -d"'" -f2)
    DOMAIN_NAME=$(echo "$OLD_DOMAIN_FULL" | cut -d'/' -f1)
    echo -e "\033[32mDomain detected: $DOMAIN_NAME\033[0m"
    NEW_BOT_DIR="/var/www/html/mirzaprobotconfig"
    rm -rf "$OLD_BOT_DIR"
    mkdir -p "$NEW_BOT_DIR"
    ZIP_URL="https://github.com/mahdiMGF2/mirzabot/archive/refs/heads/main.zip"
    TEMP_DIR="/tmp/mirzabot_mig"
    mkdir -p "$TEMP_DIR"
    run_step "Downloading Mirza source" "wget -q -O '$TEMP_DIR/bot.zip' '$ZIP_URL'" \
        || { show_step_error; echo -e "\033[31mError: Failed to download Mirza source.\033[0m"; exit 1; }
    run_step "Extracting source files" "unzip -o -q '$TEMP_DIR/bot.zip' -d '$TEMP_DIR'" \
        || { show_step_error; echo -e "\033[31mError: Failed to extract source files.\033[0m"; exit 1; }
    EXTRACTED_DIR=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -1)
    if [ -z "$EXTRACTED_DIR" ] || [ ! -d "$EXTRACTED_DIR" ]; then
        echo -e "\033[31mError: Extracted source folder not found. Aborting migration.\033[0m"
        rm -rf "$TEMP_DIR"; exit 1
    fi
    purge_installer_dir "$EXTRACTED_DIR"
    move_extracted_files "$EXTRACTED_DIR" "$NEW_BOT_DIR"
    purge_installer_dir "$NEW_BOT_DIR"
    rm -rf "$TEMP_DIR"
    cat <<EOF > "$NEW_BOT_DIR/config.php"
<?php
// This variable added for high load panels which their response time is long and bot can't communicate with online panel!
// null for default settings
\$request_exec_timeout = null;
\$dbhost = 'localhost';
\$dbname = '$NEW_DB';
\$usernamedb = '$NEW_DB_USER';
\$passworddb = '$NEW_DB_PASS';
\$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, ];
\$dsn = "mysql:host=\$dbhost;dbname=\$dbname;charset=utf8mb4";
try { \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options); \$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"); } catch (\PDOException \$e) { error_log("Database connection failed: " . \$e->getMessage()); die("error: database connection failed"); }
\$APIKEY = '${OLD_API_KEY}';
\$adminnumber = '${OLD_ADMIN_ID}';
\$domainhosts = '${DOMAIN_NAME}';
\$usernamebot = '${OLD_BOT_NAME}';
?>
EOF
    chown -R www-data:www-data "$NEW_BOT_DIR"
    chmod -R 755 "$NEW_BOT_DIR"
    run_step "Installing PHP dependencies (composer)" "install_php_deps '$NEW_BOT_DIR'" \
        || { show_step_error; echo -e "\033[31mError: Failed to install PHP dependencies. Run 'composer install' in $NEW_BOT_DIR before using the bot.\033[0m"; exit 1; }
    echo -e "\033[33mReconfiguring Apache...\033[0m"
    a2dissite 000-default.conf 2>/dev/null || true
    a2dissite 000-default-le-ssl.conf 2>/dev/null || true
    rm -f /etc/apache2/sites-enabled/000-default* 2>/dev/null
    rm -f /etc/apache2/sites-available/000-default* 2>/dev/null
    VHOST_FILE="/etc/apache2/sites-available/${DOMAIN_NAME}.conf"
    cat <<EOF > "$VHOST_FILE"
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    DocumentRoot $NEW_BOT_DIR
    <Directory $NEW_BOT_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    Include /etc/apache2/conf-available/phpmyadmin.conf
    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-access.log combined
</VirtualHost>
EOF
    VHOST_SSL_FILE="/etc/apache2/sites-available/${DOMAIN_NAME}-ssl.conf"
    cat <<EOF > "$VHOST_SSL_FILE"
<VirtualHost *:443>
    ServerName $DOMAIN_NAME
    DocumentRoot $NEW_BOT_DIR
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem
    <Directory $NEW_BOT_DIR>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    Include /etc/apache2/conf-available/phpmyadmin.conf
    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN_NAME}-access.log combined
</VirtualHost>
EOF
    a2ensite "${DOMAIN_NAME}.conf"
    a2ensite "${DOMAIN_NAME}-ssl.conf"
    a2enmod ssl
    a2enmod rewrite
    systemctl restart apache2
    echo -e "\033[33mUpdating Webhook and Tables...\033[0m"
    curl -F "url=https://${DOMAIN_NAME}/index.php" \
         "https://api.telegram.org/bot${OLD_API_KEY}/setWebhook"
    sleep 2
    curl -k "https://${DOMAIN_NAME}/table.php" > /dev/null 2>&1
    ensure_cron || echo -e "\033[33mWarning: cron is not installed or not running.\033[0m"
    sed -i 's/\r$//' /root/install.sh
    chmod +x /root/install.sh
    rm -f /usr/local/bin/mirza
    ln -sf /root/install.sh /usr/local/bin/mirza
    clear
    echo -e "\033[32m====================================================\033[0m"
    echo -e "\033[32m       MIGRATION SUCCESSFUL (Free -> Pro)           \033[0m"
    echo -e "\033[32m====================================================\033[0m"
    echo -e "\033[36mNew Database:\033[0m $NEW_DB"
    echo -e "\033[36mNew User:\033[0m     $NEW_DB_USER"
    echo -e "\033[36mNew Pass:\033[0m     $NEW_DB_PASS"
    echo -e "\033[36mBot Domain:\033[0m   https://$DOMAIN_NAME"
    echo -e "\033[33mUse command 'mirza' to manage the bot from now on.\033[0m"
    echo ""
}

# ── Command-line argument parsing ────────────────────────────
# Globals filled from flags (consumed by install/update where relevant)
ARG_TOKEN=""    ARG_ADMIN=""   ARG_DOMAIN=""
ARG_DBUSER=""   ARG_DBPASS=""  ARG_VERSION=""  ARG_CHANNEL=""

print_usage() {
    cat <<USAGE

  Mirza - management script

  Usage:
    mirza [command] [options]

  Commands:
    install            Install Mirza
    update             Update Mirza
    remove             Remove Mirza
    migrate            Migrate Free -> Pro
    renew              Renew the bot domain SSL certificate
    backup             Backup database & send to Telegram
    import             Import database from SQL file (Beta)
    update-php         Upgrade PHP to the required version (${PHP_MIN_VERSION}+)
    menu               Show interactive menu (default)

  Options:
    --token  <token>   Telegram bot token
    --admin  <id>      Admin chat id
    --domain <domain>  Domain name (e.g. bot.example.com)
    --db-user <user>   Database username
    --db-pass <pass>   Database password
    --version <tag>    Install/update a specific release tag (e.g. 0.1.7)
    --channel <name>   Source channel: beta | release | auto
    -h, --help         Show this help and exit

  Examples:
    mirza install --channel auto
    mirza install --token 123:ABC --admin 111 --domain bot.example.com --version 0.1.7
    mirza update --channel release
    mirza update --version 0.1.6

USAGE
}

process_arguments() {
    local cmd="menu"
    # First non-flag token is the command
    case "$1" in
        install|update|remove|migrate|renew|backup|import|update-php|menu) cmd="$1"; shift ;;
        -h|--help) print_usage; exit 0 ;;
        "") cmd="menu" ;;
        --*) cmd="menu" ;;            # only flags given -> menu, but still parse flags
        *) cmd="menu" ;;
    esac

    # Parse remaining flags
    while [ $# -gt 0 ]; do
        case "$1" in
            --token)   ARG_TOKEN="$2";   shift 2 ;;
            --admin)   ARG_ADMIN="$2";   shift 2 ;;
            --domain)  ARG_DOMAIN="$2";  shift 2 ;;
            --db-user) ARG_DBUSER="$2";  shift 2 ;;
            --db-pass) ARG_DBPASS="$2";  shift 2 ;;
            --version) ARG_VERSION="$2"; shift 2 ;;
            --channel) ARG_CHANNEL="$2"; shift 2 ;;
            -h|--help) print_usage; exit 0 ;;
            *) echo -e "\e[91mUnknown option: $1\033[0m"; print_usage; exit 1 ;;
        esac
    done

    case "$cmd" in
        install) install_bot ;;
        update)  update_bot ;;
        remove)  remove_bot ;;
        migrate) migrate_to_pro ;;
        renew)   renew_ssl ;;
        backup)  backup_bot ;;
        import)  import_bot ;;
        update-php) update_php ;;
        menu|*)  show_menu ;;
    esac
}
process_arguments "$@"