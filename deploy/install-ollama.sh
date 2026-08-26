#!/bin/sh
# GoodTripLove — user-level Ollama + Qwen3 1.7B install.
#
# Run as the DirectAdmin user (no root needed):
#   cd ~/apps/goodtriplove && sh deploy/install-ollama.sh
#
# Why it is written this way, on a server that also runs other sites:
#
#  * Nothing is installed system-wide. Everything lives under ~/apps/ollama,
#    so the machine's shared configuration is untouched.
#  * The server listens on 127.0.0.1 ONLY. An Ollama bound to 0.0.0.0 is an
#    unauthenticated model API open to the internet.
#  * One model resident, one request at a time, capped threads. Two 1.7B
#    inferences in parallel is exactly what would starve the neighbouring
#    sites.
#  * The application degrades gracefully: if this service is not running, the
#    collector falls back to its deterministic text classification and keeps
#    working. Installing it is an improvement, not a dependency.
#
# The download is ~1.2 GB (the published bundle carries GPU runtimes this
# server cannot use). Make sure that is acceptable before running.

set -e

BASE=${BASE:-$HOME/apps/ollama}
VERSION=${VERSION:-v0.11.10}
MODEL=${MODEL:-qwen3:1.7b}
APP=${APP:-$HOME/apps/goodtriplove}

echo "--- disk check ---"
df -h "$HOME" | tail -1

mkdir -p "$BASE/bin" "$BASE/lib" "$BASE/models" "$BASE/logs"

if [ ! -x "$BASE/bin/ollama" ]; then
    echo "--- downloading ollama $VERSION (~1.2 GB) ---"
    curl -L --fail --progress-bar \
        "https://github.com/ollama/ollama/releases/download/$VERSION/ollama-linux-amd64.tgz" \
        -o "$BASE/ollama.tgz"

    echo "--- extracting ---"
    tar -xzf "$BASE/ollama.tgz" -C "$BASE"
    rm -f "$BASE/ollama.tgz"
    chmod +x "$BASE/bin/ollama"
else
    echo "ollama already installed at $BASE/bin/ollama"
fi

cat > "$BASE/env.sh" <<'ENV'
# Loopback only: never expose the model API to the internet.
export OLLAMA_HOST=127.0.0.1:11434
export OLLAMA_ORIGINS=http://127.0.0.1
# One model resident, one request at a time — this box is shared.
export OLLAMA_MAX_LOADED_MODELS=1
export OLLAMA_NUM_PARALLEL=1
export OLLAMA_MAX_QUEUE=8
export OLLAMA_KEEP_ALIVE=5m
export OLLAMA_NUM_THREAD=2
export OLLAMA_FLASH_ATTENTION=0
ENV

cat > "$BASE/start.sh" <<START
#!/bin/sh
# Idempotent: does nothing if the server is already answering.
BASE="$BASE"
. "\$BASE/env.sh"
export OLLAMA_MODELS="\$BASE/models"
export LD_LIBRARY_PATH="\$BASE/lib:\$LD_LIBRARY_PATH"

if curl -s --max-time 3 http://127.0.0.1:11434/api/tags >/dev/null 2>&1; then
    exit 0
fi

# nice + a memory ceiling so a runaway inference can never take the machine
# down with it.
ulimit -v 4194304 2>/dev/null || true
nohup nice -n 10 "\$BASE/bin/ollama" serve >> "\$BASE/logs/ollama.log" 2>&1 &
START
chmod +x "$BASE/start.sh"

echo "--- starting ---"
sh "$BASE/start.sh"

echo "--- waiting for the API ---"
i=0
until curl -s --max-time 3 http://127.0.0.1:11434/api/tags >/dev/null 2>&1; do
    i=$((i + 1))
    [ "$i" -gt 30 ] && { echo "did not come up — see $BASE/logs/ollama.log"; exit 1; }
    sleep 2
done
echo "api is up"

echo "--- pulling $MODEL ---"
. "$BASE/env.sh"
OLLAMA_MODELS="$BASE/models" "$BASE/bin/ollama" pull "$MODEL"

echo "--- keeping it up after a reboot ---"
LINE="@reboot sh $BASE/start.sh >/dev/null 2>&1"
crontab -l 2>/dev/null | grep -Fq "$BASE/start.sh" || {
    crontab -l 2>/dev/null > /tmp/gtl_cron.$$ || true
    echo "$LINE" >> /tmp/gtl_cron.$$
    echo "*/10 * * * * sh $BASE/start.sh >/dev/null 2>&1" >> /tmp/gtl_cron.$$
    crontab /tmp/gtl_cron.$$
    rm -f /tmp/gtl_cron.$$
}

echo "--- enabling it in the application ---"
if [ -f "$APP/.env" ]; then
    sed -i "s|^OLLAMA_ENABLED=.*|OLLAMA_ENABLED=true|" "$APP/.env"
    /usr/local/php83/bin/php "$APP/artisan" config:cache >/dev/null
fi

echo "--- done ---"
curl -s http://127.0.0.1:11434/api/tags
echo
echo "Check it in the admin: /admin/operations/status"
