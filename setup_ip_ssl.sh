#!/usr/bin/env bash
# ==============================================================================
# Bashir Agro: Native IP SSL Setup Script for 129.212.239.216
# Replaces sslip.io with a native IP SSL certificate (Let's Encrypt / ZeroSSL)
# ==============================================================================

set -euo pipefail

IP_ADDRESS="129.212.239.216"
EMAIL="admin@bashiragro.com"
CERT_DIR="/etc/ssl/ip-certs"
WEBROOT="/var/www/html"

echo "=================================================="
echo " Starting Native IP SSL Setup for $IP_ADDRESS"
echo "=================================================="

# 1. Verify root privileges
if [ "$EUID" -ne 0 ]; then
  echo "[-] Please run this script as root (e.g. sudo bash setup_ip_ssl.sh)"
  exit 1
fi

# 2. Install prerequisite packages
echo "[+] Installing prerequisites (socat, curl)..."
apt-get update -qq
apt-get install -y -qq socat curl

# 3. Ensure challenge directory exists
echo "[+] Preparing ACME webroot challenge directory..."
mkdir -p "$WEBROOT/.well-known/acme-challenge"
chown -R www-data:www-data "$WEBROOT"
chmod -R 755 "$WEBROOT"

# 4. Install acme.sh if not already present
if [ ! -f "$HOME/.acme.sh/acme.sh" ]; then
  echo "[+] Installing acme.sh..."
  curl https://get.acme.sh | sh -s email="$EMAIL"
fi

ACME="$HOME/.acme.sh/acme.sh"

# 5. Issue the IP certificate using Let's Encrypt shortlived profile (RFC 8738)
echo "[+] Issuing IP certificate from Let's Encrypt for $IP_ADDRESS..."
"$ACME" --issue \
  -d "$IP_ADDRESS" \
  --webroot "$WEBROOT" \
  --server letsencrypt \
  --cert-profile shortlived || {
    echo "[!] Let's Encrypt shortlived failed; attempting fallback to ZeroSSL..."
    "$ACME" --register-account -m "$EMAIL" --server zerossl || true
    "$ACME" --issue -d "$IP_ADDRESS" --webroot "$WEBROOT" --server zerossl
}

# 6. Install the certificates into /etc/ssl/ip-certs
echo "[+] Installing certificates to $CERT_DIR..."
mkdir -p "$CERT_DIR"
"$ACME" --install-cert -d "$IP_ADDRESS" \
  --key-file       "$CERT_DIR/$IP_ADDRESS.key" \
  --fullchain-file "$CERT_DIR/$IP_ADDRESS.crt" \
  --reloadcmd     "systemctl reload nginx"

echo "[+] Certificate files installed:"
ls -lh "$CERT_DIR"

# 7. Check Nginx configuration
echo "[+] Testing Nginx configuration..."
nginx -t

echo "[+] Reloading Nginx..."
systemctl reload nginx

echo "=================================================="
echo "[SUCCESS] Native IP SSL successfully activated!"
echo "Verify at: https://$IP_ADDRESS/api/v1/owner/dashboard"
echo "=================================================="
