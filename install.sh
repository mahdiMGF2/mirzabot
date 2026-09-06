#!/bin/bash
# Checking Root Access
if [[ $EUID -ne 0 ]]; then
    echo -e "\033[31m[ERROR]\033[0m Please run this script as \033[1mroot\033[0m."
    exit 1
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
    local pct=$1 width=${2:-14} filled i out=""
    [ "$pct" -gt 100 ] && pct=100; [ "$pct" -lt 0 ] && pct=0
    filled=$(( pct * width / 100 ))
    for ((i = 0; i < width; i++)); do
        if [ "$i" -lt "$filled" ]; then out+="█"; else out+="░"; fi
    done
    printf '%s' "$out"
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

print_header() {
    echo ""
    echo -e "\033[1;34m╭────────────────────────────────────────────────╮\033[0m"
    printf  "\033[1;34m│\033[0m \033[1;36m%-46s\033[0m \033[1;34m│\033[0m\n" "$1"
    echo -e "\033[1;34m╰────────────────────────────────────────────────╯\033[0m"
}

run_step() {
    local msg="$1"
    local cmd="$2"
    local eta="${3:-$(_step_eta "$msg")}"
    [ "$eta" -lt 1 ] && eta=1
    STEP_NO=$((STEP_NO + 1))
    local counter="$STEP_NO"
    [ "$STEP_TOTAL" -gt 0 ] && counter="$STEP_NO/$STEP_TOTAL"
    : > "$INSTALL_LOG"
    local start; start=$(date +%s)
    bash -c "$cmd" >> "$INSTALL_LOG" 2>&1 &
    local pid=$!
    local frames=("⠋" "⠙" "⠹" "⠸" "⠼" "⠴" "⠦" "⠧" "⠇" "⠏")
    local n=${#frames[@]}
    local i=0
    tput civis 2>/dev/null
    while kill -0 "$pid" 2>/dev/null; do
        local el=$(( $(date +%s) - start ))
        local pct=$(( el * 100 / eta ))
        [ "$pct" -gt 95 ] && pct=95          # don't show full until it really finishes
        local left=$(( eta - el )) lefttxt
        if [ "$left" -gt 0 ]; then lefttxt="~$(_fmt_secs $left) left"; else lefttxt="finishing…"; fi
        local otxt=""
        if [ "$ETA_REMAINING" -gt 0 ]; then
            local orem=$(( ETA_REMAINING - el )); [ "$orem" -lt 0 ] && orem=0
            otxt=" \033[0;37m· total ~$(_fmt_secs $orem)\033[0m"
        fi
        printf "\r\033[K \033[1;33m%s\033[0m \033[0;37m[%s]\033[0m %s  \033[1;36m▕%s▏\033[0m \033[0;37m%s · %s\033[0m%b" \
            "${frames[$i]}" "$counter" "$msg" "$(_bar "$pct" 14)" "$(_fmt_secs $el)" "$lefttxt" "$otxt"
        i=$(( (i + 1) % n ))
        sleep 0.2
    done
    wait "$pid"
    local rc=$?
    local el=$(( $(date +%s) - start ))
    tput cnorm 2>/dev/null
    if [ "$ETA_REMAINING" -gt 0 ]; then
        ETA_REMAINING=$(( ETA_REMAINING - eta )); [ "$ETA_REMAINING" -lt 0 ] && ETA_REMAINING=0
    fi
    if [ "$rc" -eq 0 ]; then
        printf "\r\033[K \033[1;32m✔\033[0m \033[0;37m[%s]\033[0m %s \033[0;37m(%s)\033[0m\n" "$counter" "$msg" "$(_fmt_secs $el)"
    else
        printf "\r\033[K \033[1;31m✘\033[0m \033[0;37m[%s]\033[0m %s \033[0;37m(%s)\033[0m\n" "$counter" "$msg" "$(_fmt_secs $el)"
    fi
    return "$rc"
}

show_step_error() {
    echo -e "\033[1;31m──────────────── Error details ─────────────────\033[0m"
    tail -n 20 "$INSTALL_LOG" 2>/dev/null
    echo -e "\033[1;31m─────────────────────────────────────────────────\033[0m"
}

# ── Menu UI helpers ──────────────────────────────────────────
C_BORDER=$'\033[1;36m'; C_TITLE=$'\033[1;37m'; C_DIM=$'\033[0;37m'
C_KEY=$'\033[1;33m';    C_TXT=$'\033[0;37m';   C_OK=$'\033[1;32m'
C_BAD=$'\033[1;31m';    C_WARN=$'\033[1;33m';  C_PROMPT=$'\033[1;36m'
CR=$'\033[0m'
UI_W=52   # width of horizontal rules (no right border = never misaligns)

_repeat() { local ch="$1" n="$2" out="" i; for ((i=0;i<n;i++)); do out+="$ch"; done; printf '%s' "$out"; }
# Horizontal rules (left-aligned, no right edge to drift)
_rule()   { printf "  ${C_BORDER}%s${CR}\n" "$(_repeat "─" "$UI_W")"; }
_drule()  { printf "  ${C_BORDER}%s${CR}\n" "$(_repeat "━" "$UI_W")"; }
# Banner: rules + left-aligned title (no full box)
banner()  {
    echo
    _drule
    printf "  ${C_OK}▌${CR} ${C_TITLE}MIRZA${CR}  ${C_DIM}— VPN Subscription Management${CR}\n"
    _drule
}
# Menu item row: [n] label  (left-aligned, no right border)
_mi()     { printf "    ${C_KEY}[%s]${CR}  ${C_TXT}%b${CR}\n" "$1" "$2"; }

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
    echo -e "  ${C_WARN}!${CR} ${C_WARN}DNS resolution failed - configuring public DNS...${CR}"
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
    dns_works && { echo -e "  ${C_OK}●${CR} ${C_OK}DNS is now working.${CR}"; return 0; }
    echo -e "  ${C_BAD}●${CR} ${C_BAD}DNS still failing after applying public resolvers.${CR}"
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

php_ppa_has_series() {
    [ -n "$1" ] || return 1
    local try
    for try in 1 2 3; do
        curl -fsSL --max-time 10 -o /dev/null \
            "https://ppa.launchpadcontent.net/ondrej/php/ubuntu/dists/$1/Release" 2>/dev/null && return 0
        sleep 2
    done
    return 1
}
export -f php_ppa_has_series

php_repo_disable() {
    local f n=0
    for f in /etc/apt/sources.list.d/*ondrej*php*.sources /etc/apt/sources.list.d/*ondrej*php*.list; do
        [ -f "$f" ] || continue
        mv -f "$f" "$f.disabled-by-mirza" && n=$((n + 1))
    done
    [ "$n" -gt 0 ]
}
export -f php_repo_disable

setup_php_repo() {
    detect_os
    export DEBIAN_FRONTEND=noninteractive
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

export PHP_VER_CANDIDATES="8.2 8.3 8.4 8.5"

# 0 when apt has an installable candidate for this package.
_apt_has_candidate() {
    local cand
    cand=$(apt-cache policy "$1" 2>/dev/null | awk '/Candidate:/{print $2; exit}')
    [ -n "$cand" ] && [ "$cand" != "(none)" ]
}
export -f _apt_has_candidate

resolve_php_ver() {
    local v
    for v in $PHP_VER_CANDIDATES; do
        if _apt_has_candidate "php$v" && _apt_has_candidate "libapache2-mod-php$v"; then
            echo "$v"; return 0
        fi
    done
    echo "8.2"
    return 1
}
export -f resolve_php_ver

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
    sudo systemctl restart mysql
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
    sudo systemctl restart mysql
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
        && php -m 2>/dev/null | grep -qi '^dom$'; then
        return 0
    fi

    pkgs="php${ver}-mbstring php${ver}-xml php${ver}-zip php${ver}-gd php${ver}-curl php${ver}-intl php${ver}-bcmath"
    echo "Ensuring PHP ${ver} extensions for Composer: ${pkgs}"
    DEBIAN_FRONTEND=noninteractive apt-get install -y $pkgs || {
        echo "Failed to install PHP ${ver} extensions required by Composer." >&2
        return 1
    }

    if ! php -m 2>/dev/null | grep -qi '^mbstring$' \
        || ! php -m 2>/dev/null | grep -qi '^dom$'; then
        echo "PHP ${ver} is missing mbstring and/or dom after package install." >&2
        echo "Run: php -m | grep -E 'mbstring|dom'  and php --ini" >&2
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
        printf "    ${C_BAD}●${CR} ${C_BAD}This installer needs a fresh server with no other software installed.${CR}\n"
        printf "    ${C_DIM}Detected conflicting components:${CR}\n"
        local f
        for f in "${found[@]}"; do printf "      ${C_WARN}-${CR} ${C_TXT}%s${CR}\n" "$f"; done
        echo ""
        printf "    ${C_TXT}Use a clean Ubuntu 22.04/24.04/26.04 server (no web server, database, or panel)${CR}\n"
        printf "    ${C_TXT}or reinstall the OS, then run the installer again.${CR}\n"
        return 1
    fi
    return 0
}


repair_mysql() {
    export DEBIAN_FRONTEND=noninteractive
    systemctl stop mysql 2>/dev/null
    # 1) Gentle fix first
    dpkg --configure -a >/dev/null 2>&1
    apt-get install -f -y >/dev/null 2>&1
    if dpkg-query -W -f='${Package} ${Status}\n' 'mysql-server-[0-9]*' 2>/dev/null | grep -q 'install ok installed'; then
        return 0
    fi
    # 2) Hard reset: purge MySQL and wipe its (empty) data dir, then reinstall fresh
    apt-get purge -y 'mysql-server*' 'mysql-client*' 'mysql-community*' mysql-common >/dev/null 2>&1
    apt-get autoremove -y >/dev/null 2>&1
    rm -rf /var/lib/mysql /var/log/mysql /etc/mysql
    dpkg --configure -a >/dev/null 2>&1
    apt-get update --allow-releaseinfo-change >/dev/null 2>&1
    return 0
}
export -f repair_mysql


install_pause() {
    local where="$1"
    echo ""
    echo -e "  ${C_WARN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${CR}"
    echo -e "  ${C_WARN}● Installation paused${CR} ${C_DIM}(${where})${CR}"
    echo -e "  ${C_DIM}This is usually caused by the server losing internet or a network error.${CR}"
    echo ""
    echo -e "  ${C_TXT}Completed steps are saved. Just run it again:${CR}"
    echo -e "      ${C_KEY}mirza install${CR}"
    echo -e "  ${C_DIM}It resumes from this step; values you already entered (domain/token/...) will not be asked again.${CR}"
    echo -e "  ${C_WARN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${CR}"
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
_sec() { printf "\n  ${C_KEY}▌${CR} ${C_TITLE}%s${CR}\n" "$1"; _rule; }
_kv()  { printf "    ${C_DIM}%-11s${CR}${C_BORDER}:${CR} %b${CR}\n" "$1" "$2"; }

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
            echo -e "    ${C_BAD}●${CR} ${C_BAD}Version '${ARG_VERSION}' not found.${CR}"
            echo -e "    ${C_DIM}Available:${CR} $(echo "$_avail" | tr '\n' ' ')"
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
            *) echo -e "    ${C_BAD}Unknown channel: ${ARG_CHANNEL}${CR}"; return 1 ;;
        esac
    fi

    # ── Interactive ──────────────────────────────────────────
    _sec "Select version"
    _mi "1" "Automatic  ${C_DIM}(latest stable release)${CR}"
    _mi "2" "Choose a specific release version"
    _mi "3" "Beta       ${C_DIM}(latest main branch - may be unstable)${CR}"
    _mi "0" "Back to menu"
    echo ""
    printf "  ${C_PROMPT}❯${CR} Select ${C_DIM}[0-3]${CR}: "
    local S; read -r S
    case "$S" in
        0) return 2 ;;
        1)
            local l; l=$(get_latest_version)
            if [ -n "$l" ]; then SRC_ZIP_URL="${tagbase}/${l}.zip"; SRC_LABEL="Release ${l}";
            else
                echo -e "    ${C_WARN}Could not detect latest release; falling back to Beta.${CR}"
                SRC_ZIP_URL="$beta"; SRC_LABEL="Beta (main)"
            fi
            return 0 ;;
        2)
            echo ""
            echo -e "  ${C_DIM}Fetching available versions...${CR}"
            local TAGS=(); mapfile -t TAGS < <(list_tags_desc)
            if [ "${#TAGS[@]}" -eq 0 ]; then
                echo -e "    ${C_BAD}●${CR} ${C_BAD}Could not fetch release list (offline or rate-limited).${CR}"
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
            printf "  ${C_PROMPT}❯${CR} Select version ${C_DIM}[default: 1]${CR}: "
            local V; read -r V; [ -z "$V" ] && V=1
            [ "$V" = "0" ] && return 2
            if ! [[ "$V" =~ ^[0-9]+$ ]] || [ "$V" -lt 1 ] || [ "$V" -gt "${#TAGS[@]}" ]; then
                echo -e "    ${C_BAD}Invalid selection.${CR}"; return 1
            fi
            local c="${TAGS[$((V-1))]}"
            SRC_ZIP_URL="${tagbase}/${c}.zip"; SRC_LABEL="Release ${c}"
            return 0 ;;
        3) SRC_ZIP_URL="$beta"; SRC_LABEL="Beta (main)"; return 0 ;;
        *) echo -e "    ${C_BAD}Invalid selection.${CR}"; return 1 ;;
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
version_section() {
    local inst latest
    inst=$(get_installed_version)
    latest=$(get_latest_version)
    _sec "Version"
    if [ -n "$inst" ]; then
        _kv "Installed" "$(_dot ok) ${C_OK}${inst}${CR}"
    else
        _kv "Installed" "$(_dot bad) ${C_BAD}not installed${CR}"
    fi
    if [ -n "$latest" ]; then
        if [ -n "$inst" ] && [ "$inst" = "$latest" ]; then
            _kv "Latest" "$(_dot ok) ${C_OK}${latest}${CR} ${C_DIM}(up to date)${CR}"
        elif [ -n "$inst" ]; then
            _kv "Latest" "$(_dot warn) ${C_WARN}${latest}${CR} ${C_WARN}(update available!)${CR}"
        else
            _kv "Latest" "$(_dot warn) ${C_DIM}${latest}${CR}"
        fi
    else
        _kv "Latest" "$(_dot warn) ${C_DIM}unknown (offline)${CR}"
    fi
    _kv "Channel" "${C_DIM}t.me/mirzapanel${CR}"
    _kv "Group" "${C_DIM}t.me/mirzapanelgroup${CR}"
}

bot_section() {
    SSL_DOMAIN=""
    _sec "Bot Status"
    if [ ! -f "$CONFIG_FILE_DEFAULT" ]; then
        _kv "State" "$(_dot bad) ${C_BAD}not installed${CR}"
        return
    fi
    _kv "State" "$(_dot ok) ${C_OK}installed${CR}"
    SSL_DOMAIN=$(grep '^\$domainhosts' "$CONFIG_FILE_DEFAULT" | cut -d"'" -f2 | cut -d'/' -f1)
    if [ -n "$SSL_DOMAIN" ] && [ -f "/etc/letsencrypt/live/$SSL_DOMAIN/cert.pem" ]; then
        local expiry days
        expiry=$(openssl x509 -enddate -noout -in "/etc/letsencrypt/live/$SSL_DOMAIN/cert.pem" 2>/dev/null | cut -d= -f2)
        days=$(( ( $(date -d "$expiry" +%s 2>/dev/null || echo 0) - $(date +%s) ) / 86400 ))
        if [ "$days" -gt 14 ]; then
            _kv "SSL" "$(_dot ok) ${C_OK}valid${CR} ${C_DIM}(${days} days left)${CR}"
        elif [ "$days" -gt 0 ]; then
            _kv "SSL" "$(_dot warn) ${C_WARN}valid${CR} ${C_DIM}(${days} days left - renew soon)${CR}"
        else
            _kv "SSL" "$(_dot bad) ${C_BAD}expired${CR}"
        fi
    else
        _kv "SSL" "$(_dot warn) ${C_WARN}certificate not found${CR}"
    fi
    if [ -n "$SSL_DOMAIN" ]; then
        _kv "Domain" "${C_DIM}https://${SSL_DOMAIN}${CR}"
        _kv "phpMyAdmin" "${C_DIM}https://${SSL_DOMAIN}/phpmyadmin${CR}"
    fi
}

# Read the Telegram webhook using the bot token from config.php.
# Prints webhook URL / pending count, and surfaces any error message.
webhook_section() {
    _sec "Webhook"
    if [ ! -f "$CONFIG_FILE_DEFAULT" ]; then
        _kv "Status" "$(_dot warn) ${C_DIM}n/a (bot not installed)${CR}"
        return
    fi
    local token info ok url pending err errdate apierr when
    token=$(grep '^\$APIKEY' "$CONFIG_FILE_DEFAULT" | cut -d"'" -f2)
    if [ -z "$token" ]; then
        _kv "Status" "$(_dot bad) ${C_BAD}token not found in config.php${CR}"
        return
    fi
    info=$(curl -fsSL --max-time 8 "https://api.telegram.org/bot${token}/getWebhookInfo" 2>/dev/null)
    if [ -z "$info" ]; then
        _kv "Status" "$(_dot bad) ${C_BAD}cannot reach Telegram API${CR}"
        printf "    ${C_BAD}Error:${CR} request to api.telegram.org failed (network/timeout).\n"
        return
    fi
    if command -v jq >/dev/null 2>&1; then
        ok=$(echo "$info"     | jq -r '.ok')
        url=$(echo "$info"    | jq -r '.result.url // empty')
        pending=$(echo "$info"| jq -r '.result.pending_update_count // 0')
        err=$(echo "$info"    | jq -r '.result.last_error_message // empty')
        errdate=$(echo "$info"| jq -r '.result.last_error_date // empty')
        apierr=$(echo "$info" | jq -r '.description // empty')
    else
        ok=$(echo "$info"     | grep -oE '"ok":[[:space:]]*(true|false)' | grep -oE '(true|false)')
        url=$(echo "$info"    | grep -oE '"url":[[:space:]]*"[^"]*"' | sed -E 's/.*"url":[[:space:]]*"([^"]*)".*/\1/')
        pending=$(echo "$info"| grep -oE '"pending_update_count":[[:space:]]*[0-9]+' | grep -oE '[0-9]+$')
        err=$(echo "$info"    | grep -oE '"last_error_message":[[:space:]]*"[^"]*"' | sed -E 's/.*"last_error_message":[[:space:]]*"([^"]*)".*/\1/')
        errdate=$(echo "$info"| grep -oE '"last_error_date":[[:space:]]*[0-9]+' | grep -oE '[0-9]+$')
        apierr=$(echo "$info" | grep -oE '"description":[[:space:]]*"[^"]*"' | sed -E 's/.*"description":[[:space:]]*"([^"]*)".*/\1/')
        [ -z "$pending" ] && pending=0
    fi
    # Telegram-level API failure (e.g. invalid/revoked token)
    if [ "$ok" != "true" ]; then
        _kv "Status" "$(_dot bad) ${C_BAD}API error${CR}"
        [ -n "$apierr" ] && printf "    ${C_BAD}Error:${CR} %s\n" "$apierr"
        return
    fi
    # Webhook URL
    if [ -n "$url" ]; then
        _kv "URL" "$(_dot ok) ${C_OK}set${CR} ${C_DIM}(${url})${CR}"
    else
        _kv "URL" "$(_dot bad) ${C_BAD}not set${CR}"
    fi
    _kv "Pending" "${C_DIM}${pending} update(s)${CR}"
    # Last delivery error reported by Telegram
    if [ -n "$err" ]; then
        when=""
        [ -n "$errdate" ] && when=$(date -d "@$errdate" '+%Y-%m-%d %H:%M' 2>/dev/null)
        _kv "Last error" "$(_dot bad) ${C_BAD}${err}${CR}"
        [ -n "$when" ] && _kv "Error time" "${C_DIM}${when}${CR}"
    else
        _kv "Last error" "$(_dot ok) ${C_OK}none${CR}"
    fi
}

system_section() {
    local php_v apache_s mysql_s ip os
    php_v=$(php -r 'echo PHP_VERSION;' 2>/dev/null); [ -z "$php_v" ] && php_v="n/a"
    apache_s=$(systemctl is-active apache2 2>/dev/null || echo "inactive")
    mysql_s=$(systemctl is-active mysql 2>/dev/null || echo "inactive")
    ip=$(get_server_ip)
    if [ -f /etc/os-release ]; then os=$(. /etc/os-release; echo "$PRETTY_NAME"); else os="Unknown"; fi
    _svc_row() { if [ "$2" = "active" ]; then _kv "$1" "$(_dot ok) ${C_OK}active${CR}"; else _kv "$1" "$(_dot bad) ${C_BAD}$2${CR}"; fi; }
    _sec "System"
    _kv "OS" "${C_DIM}${os}${CR}"
    _kv "PHP" "${C_DIM}${php_v}${CR}"
    _svc_row "Apache" "$apache_s"
    _svc_row "MySQL" "$mysql_s"
    _kv "Server IP" "${C_DIM}${ip}${CR}"
}

resources_section() {
    local mem_t mem_u mem_p disk load cores up
    mem_t=$(free -m 2>/dev/null | awk '/^Mem:/{print $2}')
    mem_u=$(free -m 2>/dev/null | awk '/^Mem:/{print $3}')
    if [ -n "$mem_t" ] && [ "$mem_t" -gt 0 ] 2>/dev/null; then mem_p=$(( mem_u * 100 / mem_t )); else mem_p=0; fi
    disk=$(df -h / 2>/dev/null | awk 'NR==2{print $3" / "$2"  ("$5")"}')
    load=$(awk '{print $1", "$2", "$3}' /proc/loadavg 2>/dev/null)
    cores=$(nproc 2>/dev/null)
    up=$(uptime -p 2>/dev/null | sed 's/^up //')
    [ -z "$up" ] && up="n/a"
    _sec "Resources"
    _kv "RAM" "${C_DIM}${mem_u}MB / ${mem_t}MB  (${mem_p}%)${CR}"
    _kv "Disk" "${C_DIM}${disk}${CR}"
    _kv "CPU load" "${C_DIM}${load}  (${cores} cores)${CR}"
    _kv "Uptime" "${C_DIM}${up}${CR}"
}

function show_logo() {
    clear
    banner
    version_section
    bot_section
    webhook_section
    system_section
    resources_section
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
        printf "  ${C_PROMPT}❯${CR} Enter the bot domain: "
        read -r domain
    fi
    if [ -z "$domain" ]; then
        echo -e "  ${C_BAD}●${CR} ${C_BAD}No domain found. Aborting.${CR}"
        sleep 1; show_menu; return 1
    fi
    _kv "Domain" "${C_KEY}${domain}${CR}"

    if ! command -v certbot >/dev/null 2>&1; then
        echo -e "  ${C_BAD}●${CR} ${C_BAD}certbot is not installed. Install Mirza first.${CR}"
        sleep 1; show_menu; return 1
    fi

    # Show current expiry, if a certificate already exists
    local certfile="/etc/letsencrypt/live/${domain}/cert.pem"
    if [ -f "$certfile" ]; then
        local exp
        exp=$(openssl x509 -enddate -noout -in "$certfile" 2>/dev/null | cut -d= -f2)
        [ -n "$exp" ] && _kv "Expires" "${C_DIM}${exp}${CR}"
    else
        echo -e "  ${C_WARN}!${CR} ${C_WARN}No existing certificate found - a new one will be issued.${CR}"
    fi
    echo ""

    # 2) Optional force (Let's Encrypt normally renews only within ~30 days of expiry)
    printf "  ${C_PROMPT}❯${CR} Force renewal now even if not near expiry? ${C_DIM}[y/N]${CR}: "
    read -r _force
    local force_flag=""
    [[ "$_force" =~ ^[Yy]$ ]] && force_flag="--force-renewal"
    echo ""

    # Use the apache authenticator so it works while Apache is running (no downtime).
    # certonly updates the existing cert lineage in place; Apache already points at it.
    run_step "Renewing certificate for ${domain}" \
        "certbot certonly --apache --non-interactive --agree-tos --register-unsafely-without-email --keep-until-expiring --cert-name '${domain}' -d '${domain}' ${force_flag}" \
        || { show_step_error; echo -e "\n  ${C_BAD}●${CR} ${C_BAD}Renewal failed. See the details above.${CR}"; echo ""; printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "; read -r _; show_menu; return 1; }

    run_step "Reloading Apache" "systemctl reload apache2 2>/dev/null || systemctl restart apache2"

    _sec "Done"
    _kv "Domain" "${C_KEY}${domain}${CR}"
    if [ -f "$certfile" ]; then
        local newexp
        newexp=$(openssl x509 -enddate -noout -in "$certfile" 2>/dev/null | cut -d= -f2)
        [ -n "$newexp" ] && _kv "Valid until" "${C_OK}${newexp}${CR}"
    fi
    echo ""
    printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}

function backup_bot() {
    clear
    banner
    _sec "Backup Database"

    CONFIG_PATH="/var/www/html/mirzaprobotconfig/config.php"
    if [ ! -f "$CONFIG_PATH" ]; then
        printf "    ${C_BAD}●${CR} ${C_BAD}Mirza is not installed. config.php not found.${CR}\n"
        echo ""
        printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
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
        printf "    ${C_BAD}●${CR} ${C_BAD}Could not read database credentials from config.php${CR}\n"
        echo ""
        printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
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

    run_step "Exporting database (${dbname})" \
        "mysqldump -h '$dbhost' -u '$dbuser' -p'$dbpass' --no-tablespaces --ssl-mode=DISABLED '$dbname' > '$backup_file'" \
        || { show_step_error; echo -e "\n  ${C_BAD}●${CR} ${C_BAD}Backup failed. See details above.${CR}"; echo ""; printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "; read -r _; show_menu; return 1; }

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
            printf "    ${C_DIM}Make sure the bot token and admin chat ID are correct.${CR}\n"
        fi
    else
        echo ""
        printf "    ${C_WARN}!${CR} ${C_WARN}Bot token or admin ID not found in config - skipping Telegram send.${CR}\n"
    fi

    echo ""
    printf "    ${C_OK}✔${CR} ${C_OK}Backup saved to:${CR} ${C_KEY}${backup_file}${CR}\n"
    echo ""
    printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}

function import_bot() {
    clear
    banner
    _sec "Import Database"

    CONFIG_PATH="/var/www/html/mirzaprobotconfig/config.php"
    if [ ! -f "$CONFIG_PATH" ]; then
        printf "    ${C_BAD}●${CR} ${C_BAD}Mirza is not installed. config.php not found.${CR}\n"
        echo ""
        printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
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
        printf "    ${C_BAD}●${CR} ${C_BAD}Could not read database credentials from config.php${CR}\n"
        echo ""
        printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi

    _kv "Database" "${C_DIM}${dbname}${CR}"
    _kv "DB User" "${C_DIM}${dbuser}${CR}"
    _kv "DB Host" "${C_DIM}${dbhost}${CR}"
    echo ""

    echo -e "  ${C_DIM}Available backup files in /root/:${CR}"
    local files=()
    local i=1
    while IFS= read -r f; do
        files+=("$f")
        local sz
        sz=$(du -h "$f" 2>/dev/null | awk '{print $1}')
        printf "    ${C_KEY}[%d]${CR}  ${C_TXT}%s${CR}  ${C_DIM}(%s)${CR}\n" "$i" "$(basename "$f")" "$sz"
        i=$((i + 1))
    done < <(find /root -maxdepth 1 -name 'mirza_backup_*.sql' -type f 2>/dev/null | sort -r)

    if [ "${#files[@]}" -eq 0 ]; then
        printf "    ${C_WARN}!${CR} ${C_WARN}No backup files found in /root/${CR}\n"
    fi

    echo ""
    printf "    ${C_KEY}[0]${CR}  ${C_TXT}Enter a custom file path${CR}\n"
    echo ""
    printf "  ${C_PROMPT}❯${CR} Select a file ${C_DIM}[0-%d]${CR} or enter path: " "${#files[@]}"
    read -r choice

    local sql_file=""
    if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#files[@]}" ]; then
        sql_file="${files[$((choice - 1))]}"
    elif [ "$choice" = "0" ] || [ -z "$choice" ]; then
        printf "  ${C_PROMPT}❯${CR} Enter the full path to the .sql file: "
        read -r sql_file
    else
        sql_file="$choice"
    fi

    if [ -z "$sql_file" ] || [ ! -f "$sql_file" ]; then
        printf "\n    ${C_BAD}●${CR} ${C_BAD}File not found: %s${CR}\n" "$sql_file"
        echo ""
        printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi

    local file_size
    file_size=$(du -h "$sql_file" 2>/dev/null | awk '{print $1}')
    _kv "File" "${C_DIM}${sql_file}${CR}"
    _kv "Size" "${C_DIM}${file_size}${CR}"
    echo ""

    printf "    ${C_WARN}!${CR} ${C_WARN}This will OVERWRITE the current database (${dbname}).${CR}\n"
    printf "  ${C_PROMPT}❯${CR} Are you sure? ${C_DIM}[y/N]${CR}: "
    read -r confirm
    if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
        printf "\n    ${C_DIM}Import cancelled.${CR}\n"
        echo ""
        printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 0
    fi
    echo ""

    run_step "Importing database (${dbname})" \
        "mysql -h '$dbhost' -u '$dbuser' -p'$dbpass' '$dbname' < '$sql_file'" \
        || { show_step_error; echo -e "\n  ${C_BAD}●${CR} ${C_BAD}Import failed. See details above.${CR}"; echo ""; printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "; read -r _; show_menu; return 1; }

    local DOMAIN_NAME=""
    if [ -f "$CONFIG_PATH" ]; then
        DOMAIN_NAME=$(grep '^\$domainhosts' "$CONFIG_PATH" | cut -d"'" -f2 | cut -d'/' -f1)
    fi
    if [ -n "$DOMAIN_NAME" ]; then
        run_step "Updating database tables" "curl -s 'https://${DOMAIN_NAME}/table.php' > /dev/null" || true
    fi

    echo ""
    printf "    ${C_OK}✔${CR} ${C_OK}Database imported successfully from:${CR} ${C_KEY}${sql_file}${CR}\n"
    echo ""
    printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}

function show_menu() {
    show_logo
    _sec "Menu"
    _mi "1" "Install Mirza"
    _mi "2" "Update Mirza"
    _mi "3" "Remove Mirza"
    _mi "4" "Migrate: Free -> Pro (Beta)"
    _mi "5" "Renew SSL certificate"
    _mi "6" "Backup Database"
    _mi "7" "Import Database  ${C_WARN}(Beta)${CR}"
    _mi "8" "Help & Parameters"
    _mi "9" "Exit"
    _rule
    echo ""
    printf  "  ${C_PROMPT}❯${CR} Select an option ${C_DIM}[1-9]${CR}: "
    read -r option
    case $option in
        1) install_bot ;;
        2) update_bot ;;
        3) remove_bot ;;
        4) migrate_to_pro ;;
        5) renew_ssl ;;
        6) backup_bot ;;
        7) import_bot ;;
        8) show_help_screen ;;
        9) echo -e "\n${C_OK}Exiting...${CR}"; exit 0 ;;
        *) echo -e "\n${C_BAD}Invalid option. Please try again.${CR}"; sleep 1; show_menu ;;
    esac
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
    printf "    ${C_KEY}mirza install --channel auto${CR}\n"
    printf "    ${C_KEY}mirza install --token 123:ABC \\\\${CR}\n"
    printf "    ${C_DIM}            --admin 111 --domain bot.example.com --version 0.1.7${CR}\n"
    printf "    ${C_KEY}mirza update --version 0.1.6${CR}\n"
    printf "    ${C_KEY}mirza update --channel release${CR}\n"
    printf "    ${C_KEY}mirza remove${CR}\n"
    printf "    ${C_KEY}mirza backup${CR}\n"
    printf "    ${C_KEY}mirza import${CR}\n"

    echo ""
    _rule
    echo ""
    printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
    read -r _
    show_menu
}
function fix_update_issues() {
    echo -e "\e[33mTrying to fix update issues by changing mirrors...\033[0m"
    # Broken apt mirrors are often a DNS problem - fix DNS first
    ensure_dns
    if ! detect_os || [ -z "$OS_CODENAME" ]; then
        echo -e "\e[91mCould not detect Ubuntu version.\033[0m"
        return 1
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
    echo -e "  ${C_WARN}!${CR} ${C_WARN}No connectivity - resetting DNS and retrying...${CR}"
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
        printf "    ${C_BAD}●${CR} ${C_BAD}Could not remove the web installer at %s/install.${CR}\n" "$target"
        printf "    ${C_BAD}●${CR} ${C_BAD}Delete it manually - the bot refuses to answer users while it exists.${CR}\n"
        return 1
    fi
    return 0
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
    local dbhost dbname dbuser dbpass domain rows id user token fail=0
    dbhost=$(grep '^\$dbhost' "$config" | cut -d"'" -f2)
    dbname=$(grep '^\$dbname' "$config" | cut -d"'" -f2)
    dbuser=$(grep '^\$usernamedb' "$config" | cut -d"'" -f2)
    dbpass=$(grep '^\$passworddb' "$config" | cut -d"'" -f2)
    domain=$(grep '^\$domainhosts' "$config" | cut -d"'" -f2 | cut -d'/' -f1)
    [ -z "$dbhost" ] && dbhost="localhost"
    [ -n "$dbname" ] && [ -n "$dbuser" ] && [ -n "$domain" ] || return 0
    command -v mysql >/dev/null 2>&1 || return 0
    rows=$(mysql -h "$dbhost" -u "$dbuser" -p"$dbpass" -N -B \
        -e "SELECT id_user, username, bot_token FROM botsaz;" "$dbname" 2>/dev/null) || return 0
    [ -n "$rows" ] || return 0
    while IFS=$'\t' read -r id user token; do
        [ -n "$id" ] && [ -n "$user" ] && [ -n "$token" ] || continue
        curl -s --max-time 15 -o /dev/null \
            -F "url=https://${domain}/vpnbot/${id}${user}/index.php" \
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

    # Supported: Ubuntu 22.04 / 24.04 / 26.04 (newer releases pass with a note).
    detect_os
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
        _kv "OS" "$(_dot warn) ${C_WARN}${OS_PRETTY} (untested; Ubuntu 22.04/24.04/26.04 recommended)${CR}"
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
        echo -e "  ${C_BAD}●${CR} ${C_BAD}Pre-flight checks failed. Aborting to avoid a broken install.${CR}"
        return 1
    fi
    return 0
}

function install_bot() {
    BOT_DIR="/var/www/html/mirzaprobotconfig"
    PHP_VER="$(state_get PHP_VER)"
    [ -z "$PHP_VER" ] && PHP_VER="8.2"

    # ── Guard: only block when a PREVIOUS install fully COMPLETED ──
    if [ -f "$CONFIG_FILE_DEFAULT" ] && ! has_resumable_state; then
        clear
        banner
        _sec "Install blocked"
        printf "    ${C_BAD}●${CR} ${C_BAD}Mirza is already installed on this server.${CR}\n"
        printf "    ${C_DIM}Path:${CR} %s\n" "$BOT_DIR_DEFAULT"
        echo ""
        printf "    ${C_DIM}To upgrade, use option ${CR}${C_KEY}2 (Update)${CR}${C_DIM}.${CR}\n"
        printf "    ${C_DIM}To reinstall, first remove it with option ${CR}${C_KEY}3 (Remove)${CR}${C_DIM}.${CR}\n"
        echo ""
        printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
    fi
    # ── Fresh-server requirement (only on a brand-new install) ──
    if ! has_resumable_state && [ ! -f "$CONFIG_FILE_DEFAULT" ]; then
        if ! precheck_fresh_server; then
            echo ""
            printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
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
        printf "    ${C_WARN}●${CR} ${C_WARN}An unfinished installation was found.${CR}\n"
        printf "    ${C_DIM}Last completed step:${CR} ${C_KEY}%s${CR}\n" "$_last"
        echo ""
        printf "    ${C_KEY}[1]${CR} ${C_TXT}Resume from where it stopped${CR}\n"
        printf "    ${C_KEY}[2]${CR} ${C_TXT}Start fresh from the beginning${CR}\n"
        printf "    ${C_KEY}[0]${CR} ${C_TXT}Back to menu${CR}\n"
        echo ""
        printf "  ${C_PROMPT}❯${CR} Your choice: "
        read -r _resume_choice
        case "$_resume_choice" in
            2)
                state_clear
                [ -d "$BOT_DIR" ] && sudo rm -rf "$BOT_DIR"
                echo -e "  ${C_DIM}Starting from scratch...${CR}"; sleep 1 ;;
            0) show_menu; return 0 ;;
            *) echo -e "  ${C_OK}●${CR} ${C_OK}Resuming installation from the last step...${CR}"; sleep 1 ;;
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
        printf "  ${C_PROMPT}❯${CR} Press Enter to return to the menu... "
        read -r _
        show_menu
        return 1
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
        echo -e "  ${C_DIM}Install target:${CR} ${C_KEY}${SRC_LABEL}${CR}"
        sleep 1

        print_header "Installing Dependencies"

        run_step "Preparing package manager (clearing stale apt locks)" "apt_recover" \
            || { show_step_error; install_pause "Preparing package manager"; }

        if ! run_step "Adding PHP repository (ondrej/php)" "setup_php_repo"; then
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

        run_step "Installing base tools (git, curl, wget, unzip, jq)" \
            "apt-get install -y software-properties-common git unzip curl wget jq" \
            || { show_step_error; install_pause "Installing base tools"; }

        PHP_VER="$(resolve_php_ver)"; [ -z "$PHP_VER" ] && PHP_VER="8.2"
        state_set PHP_VER "$PHP_VER"
        echo -e "  ${C_DIM}Selected PHP version:${CR} ${C_KEY}${PHP_VER}${CR}"

        run_step "Installing PHP ${PHP_VER} (fpm + mysql)" \
            "DEBIAN_FRONTEND=noninteractive apt install -y php${PHP_VER} php${PHP_VER}-cli php${PHP_VER}-fpm php${PHP_VER}-mysql" \
            || { show_step_error; install_pause "Installing PHP ${PHP_VER}"; }

        WEBSTACK_CMD="DEBIAN_FRONTEND=noninteractive apt install -y mysql-server apache2 libapache2-mod-php${PHP_VER} php${PHP_VER}-mbstring php${PHP_VER}-zip php${PHP_VER}-gd php${PHP_VER}-curl php${PHP_VER}-intl php${PHP_VER}-xml php${PHP_VER}-bcmath"
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

        run_step "Installing extra modules (php-soap, php-ssh2, libssh2)" \
            "DEBIAN_FRONTEND=noninteractive apt-get install -y php${PHP_VER}-soap php${PHP_VER}-ssh2 libssh2-1-dev libssh2-1" \
            || { show_step_error; install_pause "Installing extra PHP modules"; }

        run_step "Enabling & starting services (MySQL, Apache)" \
            "systemctl enable mysql.service && systemctl start mysql.service && systemctl enable apache2 && systemctl start apache2" \
            || { show_step_error; install_pause "Enabling core services"; }

        run_step "Configuring firewall (UFW + Apache)" \
            "apt-get install -y ufw && ufw allow 'Apache'" \
            || { show_step_error; install_pause "Configuring UFW"; }
        run_step "Restarting Apache" "systemctl restart apache2" \
            || { show_step_error; install_pause "Restarting Apache"; }

        mark_phase DEPS
    else
        echo -e "  ${C_OK}●${CR} ${C_DIM}Dependencies already installed - skipping.${CR}"
    fi

    run_step "Ensuring cron is installed and running" "ensure_cron" \
        || { show_step_error; install_pause "Installing cron"; }
    # ╰─────────────────────────────────────────────────────────────╯

    # ╭──────────────────────── PHASE: FILES ───────────────────────╮
    if ! phase_done FILES; then
        print_header "Downloading Bot Files"
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
        mv "$EXTRACTED_DIR"/* "$BOT_DIR" || {
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
        echo -e "  ${C_OK}●${CR} ${C_DIM}Bot files already downloaded - skipping.${CR}"
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
    clear
    print_header "SSL Certificate Setup"
    domainname="$(state_get DOMAIN)"
    if [ -n "$domainname" ]; then
        echo -e "  ${C_DIM}Domain (resumed):${CR} ${C_KEY}${domainname}${CR}"
    else
        if [ -n "$ARG_DOMAIN" ]; then
            domainname="$ARG_DOMAIN"
            echo -e "  ${C_DIM}Domain (from --domain):${CR} ${C_KEY}${domainname}${CR}"
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
            0) echo -e "  ${C_OK}●${CR} ${C_OK}Domain resolves to this server.${CR}" ;;
            1) echo -e "  ${C_WARN}!${CR} ${C_WARN}Domain does NOT point to this server's IP ($(get_server_ip)).${CR}"
               echo -e "  ${C_DIM}Let's Encrypt will fail until the DNS A record points here.${CR}"
               printf "  ${C_PROMPT}❯${CR} Continue anyway? ${C_DIM}[y/N]${CR}: "
               read -r _gd
               if [[ ! "$_gd" =~ ^[Yy]$ ]]; then echo -e "  ${C_BAD}Aborted. Fix the DNS A record and retry.${CR}"; sleep 1; show_menu; return 1; fi ;;
            2) echo -e "  ${C_WARN}!${CR} ${C_WARN}Could not resolve the domain yet (DNS may still be propagating).${CR}"
               printf "  ${C_PROMPT}❯${CR} Continue anyway? ${C_DIM}[y/N]${CR}: "
               read -r _gd
               if [[ ! "$_gd" =~ ^[Yy]$ ]]; then echo -e "  ${C_BAD}Aborted.${CR}"; sleep 1; show_menu; return 1; fi ;;
        esac
        state_set DOMAIN "$domainname"
    fi
    DOMAIN_NAME="$domainname"
    PATHS=$(cat /root/confmirza/dbrootmirza.txt | grep '$path' | cut -d"'" -f2)

    # ╭──────────────────────── PHASE: SSL ─────────────────────────╮
    if ! phase_done SSL; then
        if [ -f "/etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem" ]; then
            echo -e "  ${C_OK}●${CR} ${C_DIM}SSL certificate for ${DOMAIN_NAME} already exists - skipping issuance.${CR}"
        else
            run_step "Opening firewall ports 80 & 443" "ufw allow 80 && ufw allow 443" \
                || { show_step_error; install_pause "Opening firewall ports"; }
            run_step "Stopping Apache for certificate issuance" "systemctl stop apache2 && systemctl disable apache2" \
                || { show_step_error; install_pause "Stopping Apache"; }
            run_step "Installing Let's Encrypt (certbot)" "apt install letsencrypt -y && systemctl enable certbot.timer" \
                || { show_step_error; install_pause "Installing certbot"; }

            run_step "Requesting SSL certificate (Let's Encrypt)" \
                "certbot certonly --standalone --non-interactive --agree-tos --register-unsafely-without-email --preferred-challenges http -d $DOMAIN_NAME" \
                || { show_step_error; install_pause "Requesting SSL certificate"; }
        fi
        run_step "Enabling & starting Apache" "systemctl enable apache2 && systemctl start apache2" \
            || { show_step_error; install_pause "Starting Apache"; }
        mark_phase SSL
    else
        echo -e "  ${C_OK}●${CR} ${C_DIM}SSL certificate already configured - skipping.${CR}"
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
        echo -e "  ${C_OK}●${CR} ${C_DIM}Apache virtual hosts already configured - skipping.${CR}"
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ── Bot configuration inputs (token / chat id / botname) ──
    clear
    print_header "Bot Configuration"
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
                0) echo -e "  ${C_OK}●${CR} ${C_OK}Token verified with Telegram.${CR}"; break ;;
                2) echo -e "  ${C_BAD}●${CR} ${C_BAD}Telegram rejected this token (or API unreachable).${CR}"
                   printf "  ${C_PROMPT}❯${CR} Re-enter token, or press Enter to keep it anyway: "
                   read -r _t
                   if [ -z "$_t" ]; then break; fi
                   YOUR_BOT_TOKEN="$_t"
                   while [[ ! "$YOUR_BOT_TOKEN" =~ ^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$ ]]; do
                       echo -e "\e[91mInvalid format.\033[0m"; printf "  ${C_PROMPT}❯${CR} Bot Token: "; read -r YOUR_BOT_TOKEN
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
            echo -e "  ${C_BAD}●${CR} ${C_BAD}Could not read the bot username from Telegram.${CR}"
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

    MYSQL_AUTH_PLUGIN="mysql_native_password"
    if ! mysql -u"$ROOT_USER" -p"$ROOT_PASSWORD" -N -B -e \
        "SELECT PLUGIN_STATUS FROM INFORMATION_SCHEMA.PLUGINS WHERE PLUGIN_NAME='mysql_native_password';" 2>/dev/null \
        | grep -qi ACTIVE; then
        MYSQL_AUTH_PLUGIN="caching_sha2_password"
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
                echo -e "  ${C_WARN}!${CR} ${C_WARN}Invalid DB username (use only A-Z a-z 0-9 _). Using generated name.${CR}"
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
                echo -e "  ${C_WARN}!${CR} ${C_WARN}Password has unsafe characters or is too short (need 6+, A-Z a-z 0-9 _). Using generated password.${CR}"
                dbpass=$randomdbpass
            fi
            state_set DBUSER "$dbuser"
            state_set DBPASS "$dbpass"
        else
            echo -e "  ${C_OK}●${CR} ${C_DIM}Database credentials resumed.${CR}"
        fi
        # Idempotent: safe to re-run (IF NOT EXISTS), so a resumed install never breaks here
        run_step "Creating database & user" \
            "mysql -u root -p$ROOT_PASSWORD -e \"CREATE DATABASE IF NOT EXISTS $dbname;\" && mysql -u root -p$ROOT_PASSWORD -e \"CREATE USER IF NOT EXISTS '$dbuser'@'%' IDENTIFIED WITH $MYSQL_AUTH_PLUGIN BY '$dbpass'; GRANT ALL PRIVILEGES ON $dbname.* TO '$dbuser'@'%'; FLUSH PRIVILEGES;\" && mysql -u root -p$ROOT_PASSWORD -e \"CREATE USER IF NOT EXISTS '$dbuser'@'localhost' IDENTIFIED WITH $MYSQL_AUTH_PLUGIN BY '$dbpass'; GRANT ALL PRIVILEGES ON $dbname.* TO '$dbuser'@'localhost'; FLUSH PRIVILEGES;\"" \
            || { show_step_error; install_pause "Creating database/user"; }
        mark_phase DB
    else
        dbuser="$(state_get DBUSER)"
        dbpass="$(state_get DBPASS)"
        echo -e "  ${C_OK}●${CR} ${C_DIM}Database already created - skipping.${CR}"
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
        secrettoken="$(state_get SECRET)"
        if [ -z "$secrettoken" ]; then
            secrettoken=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)
            state_set SECRET "$secrettoken"
        fi
        cat <<EOF > /var/www/html/mirzaprobotconfig/config.php
<?php
// This variable added for high load panels which their response time is long and bot can't communicate with online panel!
// null for default settings
\$request_exec_timeout = null;
\$dbhost = 'localhost';
\$dbname = '$dbname';
\$usernamedb = '$dbuser';
\$passworddb = '$dbpass';
\$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci", ];
\$dsn = "mysql:host=\$dbhost;dbname=\$dbname;charset=utf8mb4";
try { \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options); } catch (\PDOException \$e) { error_log("Database connection failed: " . \$e->getMessage()); die("error: database connection failed"); }
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
        secrettoken="$(state_get SECRET)"
        echo -e "  ${C_OK}●${CR} ${C_DIM}config.php already written - skipping.${CR}"
    fi
    # ╰─────────────────────────────────────────────────────────────╯

    # ╭──────────────────────── PHASE: WEBHOOK ─────────────────────╮
    if ! phase_done WEBHOOK; then
        sleep 1
        run_step "Setting Telegram webhook" \
            "curl -s -F \"url=https://${YOUR_DOMAIN}/index.php\" -F \"secret_token=${secrettoken}\" \"https://api.telegram.org/bot${YOUR_BOT_TOKEN}/setWebhook\"" \
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
    banner
    _sec "Installation complete"
    printf "    ${C_OK}●${CR} ${C_OK}Mirza is installed and the webhook is set.${CR}\n"
    printf "    ${C_DIM}Open Telegram and send ${CR}${C_KEY}/start${CR}${C_DIM} to your bot.${CR}\n"

    _sec "Access"
    _kv "Bot URL" "${C_DIM}https://${YOUR_DOMAIN}${CR}"
    _kv "phpMyAdmin" "${C_DIM}https://${YOUR_DOMAIN}/phpmyadmin${CR}"

    _sec "Database"
    _kv "Name" "${C_KEY}${dbname}${CR}"
    _kv "Username" "${C_KEY}${dbuser}${CR}"
    _kv "Password" "${C_KEY}${dbpass}${CR}"
    printf "    ${C_WARN}!${CR} ${C_DIM}Save these credentials somewhere safe.${CR}\n"

    _sec "Manage"
    _kv "Command" "${C_DIM}run ${CR}${C_KEY}mirza${CR}${C_DIM} anytime to open this panel${CR}"
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
        printf "    ${C_BAD}●${CR} ${C_BAD}Mirza is not installed. Install it first.${CR}\n"
        sleep 2
        show_menu
        return 1
    fi

    # ── Show current version + choose source (has Back option) ──
    local current
    current=$(get_installed_version); [ -z "$current" ] && current="unknown"
    _sec "Update"
    printf "    ${C_DIM}Currently installed:${CR} ${C_OK}%s${CR}\n" "$current"
    if ! ensure_connectivity; then
        printf "    ${C_BAD}●${CR} ${C_BAD}No internet connection (even after DNS reset). Try again later.${CR}\n"
        sleep 2; show_menu; return 1
    fi
    choose_source
    local _rc=$?
    if [ "$_rc" -eq 2 ]; then show_menu; return 0; fi
    if [ "$_rc" -ne 0 ]; then sleep 2; show_menu; return 1; fi
    local ZIP_URL="$SRC_ZIP_URL" TARGET_LABEL="$SRC_LABEL"

    echo ""
    echo -e "  ${C_DIM}Update target:${CR} ${C_KEY}${TARGET_LABEL}${CR}"
    print_header "Updating Mirza Bot"
    run_step "Updating system packages" "apt update --allow-releaseinfo-change && apt upgrade -y" \
        || { show_step_error; echo -e "\e[91mError updating the server. Exiting...\033[0m"; exit 1; }
    run_step "Ensuring cron is installed and running" "ensure_cron" \
        || { show_step_error; echo -e "\e[91mError: Failed to install or start cron.\033[0m"; exit 1; }
    echo -e "\e[92mServer packages updated successfully...\033[0m\n"
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
    sudo mv "$EXTRACTED_DIR"/* "$BOT_DIR/" || {
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
    echo -e "\e[33mRemoving MySQL and database...\033[0m" | tee -a "$LOG_FILE"
    sudo systemctl stop mysql
    sudo systemctl disable mysql
    sudo systemctl daemon-reload
    sudo apt --fix-broken install -y
    sudo apt-get purge -y mysql-server mysql-client mysql-common mysql-server-core-* mysql-client-core-*
    sudo rm -rf /etc/mysql /var/lib/mysql /var/log/mysql /var/log/mysql.* /usr/lib/mysql /usr/include/mysql /usr/share/mysql
    sudo rm /lib/systemd/system/mysql.service
    sudo rm /etc/init.d/mysql
    sudo dpkg --remove --force-remove-reinstreq mysql-server mysql-server-8.0 mysql-server-8.4
    sudo find /etc/systemd /lib/systemd /usr/lib/systemd -name "*mysql*" -exec rm -f {} \;
    sudo apt-get purge -y 'mysql-server*' 'mysql-client*'
    sudo apt-get purge -y mysql-common php-mysql php8.2-mysql php8.3-mysql php8.4-mysql php-mariadb-mysql-kbs
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
    sudo apt-get remove -y php-soap php-ssh2 libssh2-1-dev libssh2-1 \
        && echo -e "\e[92mRemoved additional PHP packages.\033[0m" | tee -a "$LOG_FILE" || echo -e "\e[93mSome additional PHP packages may not be installed.\033[0m" | tee -a "$LOG_FILE"
    echo -e "\e[33mResetting firewall rules (except SSL)...\033[0m" | tee -a "$LOG_FILE"
    sudo ufw delete allow 'Apache' 2>/dev/null
    sudo ufw reload 2>/dev/null
    # Clear Mirza install state so a fresh install is allowed afterwards
    sudo rm -rf /root/confmirza
    echo -e "\e[92mMirza Bot, MySQL, and their dependencies have been completely removed.\033[0m" | tee -a "$LOG_FILE"
}

function migrate_to_pro() {
    clear
    echo -e "\033[1;33mStarting Migration from Free to Pro Version...\033[0m"
    if ! ensure_connectivity; then
        echo -e "  ${C_BAD}●${CR} ${C_BAD}No internet connection (even after DNS reset). Aborting.${CR}"
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
    MYSQL_AUTH_PLUGIN="mysql_native_password"
    if ! mysql -u "$ROOT_USER" -p"$ROOT_PASS" -N -B -e \
        "SELECT PLUGIN_STATUS FROM INFORMATION_SCHEMA.PLUGINS WHERE PLUGIN_NAME='mysql_native_password';" 2>/dev/null \
        | grep -qi ACTIVE; then
        MYSQL_AUTH_PLUGIN="caching_sha2_password"
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
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "CREATE USER '$NEW_DB_USER'@'localhost' IDENTIFIED WITH $MYSQL_AUTH_PLUGIN BY '$NEW_DB_PASS';"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "GRANT ALL PRIVILEGES ON $NEW_DB.* TO '$NEW_DB_USER'@'localhost';"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "CREATE USER '$NEW_DB_USER'@'%' IDENTIFIED WITH $MYSQL_AUTH_PLUGIN BY '$NEW_DB_PASS';"
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
    mv "$EXTRACTED_DIR"/* "$NEW_BOT_DIR"
    purge_installer_dir "$NEW_BOT_DIR"
    rm -rf "$TEMP_DIR"
    NEW_SECRET_TOKEN=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)
    cat <<EOF > "$NEW_BOT_DIR/config.php"
<?php
// This variable added for high load panels which their response time is long and bot can't communicate with online panel!
// null for default settings
\$request_exec_timeout = null;
\$dbhost = 'localhost';
\$dbname = '$NEW_DB';
\$usernamedb = '$NEW_DB_USER';
\$passworddb = '$NEW_DB_PASS';
\$options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci", ];
\$dsn = "mysql:host=\$dbhost;dbname=\$dbname;charset=utf8mb4";
try { \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options); } catch (\PDOException \$e) { error_log("Database connection failed: " . \$e->getMessage()); die("error: database connection failed"); }
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
         -F "secret_token=${NEW_SECRET_TOKEN}" \
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
        install|update|remove|migrate|renew|backup|import|menu) cmd="$1"; shift ;;
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
        menu|*)  show_menu ;;
    esac
}
process_arguments "$@"