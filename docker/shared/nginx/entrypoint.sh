#!/bin/sh

# ── SSL CERTIFICATE ───────────────────────────────────────────────────────────
CERT_DIR=/etc/nginx/certs
KEY="$CERT_DIR/vault.local.key"
CRT="$CERT_DIR/vault.local.crt"

# Generate the self-signed certificate if missing
if [ ! -f "$KEY" ] || [ ! -f "$CRT" ]; then
    # Only reach the network when a certificate actually has to be generated
    command -v openssl >/dev/null 2>&1 || apk add --no-cache openssl
    echo "🔐 Génération du certificat self-signed vault.local..."
    mkdir -p "$CERT_DIR"
    # Leaf certificate, NOT a CA: it gets trusted on the host (make docker-nginx-trust-cert)
    # and its key is stored unencrypted, so a CA:TRUE certificate would let anyone holding
    # the key sign certificates for any domain. OpenSSL's default req -x509 config adds
    # CA:TRUE, hence the explicit extensions. 825 days: Apple's maximum TLS validity.
    openssl req -x509 -noenc -days 825 \
        -newkey rsa:2048 \
        -keyout "$KEY" \
        -out "$CRT" \
        -subj "/C=FR/ST=France/L=Paris/O=Reconnect/CN=*.vault.local" \
        -addext "basicConstraints=critical,CA:FALSE" \
        -addext "keyUsage=critical,digitalSignature,keyEncipherment" \
        -addext "extendedKeyUsage=serverAuth" \
        -addext "subjectAltName=DNS:vault.local,DNS:*.vault.local,DNS:main.vault.local,DNS:dev.vault.local,DNS:minio.vault.local,DNS:console.minio.vault.local"
    echo "✅ Certificat généré"
else
    echo "✅ Certificat existant trouvé"
fi

# ── NGINX SERVER ──────────────────────────────────────────────────────────────
exec nginx -g "daemon off;"
