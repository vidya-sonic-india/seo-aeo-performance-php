#!/bin/bash
set -e

# Default to port 80 if PORT environment variable is not passed
PORT="${PORT:-80}"

echo "Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen [0-9]*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Ensure Apache passes environment variables to PHP
cat <<'EOF' > /etc/apache2/conf-available/environment.conf
PassEnv VEBAPI_KEY
PassEnv AUTH_USER
PassEnv AUTH_PASSWORD
EOF
a2enconf environment > /dev/null 2>&1

# Export into apache2 envvars as an additional safeguard
if [ -n "$VEBAPI_KEY" ]; then
    echo "export VEBAPI_KEY=\"$VEBAPI_KEY\"" >> /etc/apache2/envvars
fi

# Ensure cache directory exists and has correct permissions
mkdir -p /var/www/html/cache
chown -R www-data:www-data /var/www/html/cache
chmod -R 775 /var/www/html/cache

# Optional HTTP Basic Authentication
if [ -n "$AUTH_USER" ] && [ -n "$AUTH_PASSWORD" ]; then
    echo "Configuring HTTP Basic Authentication for user '${AUTH_USER}'..."
    htpasswd -bc /etc/apache2/.htpasswd "$AUTH_USER" "$AUTH_PASSWORD"
    cat <<'EOF' > /etc/apache2/conf-available/basic-auth.conf
<Directory /var/www/html>
    AuthType Basic
    AuthName "Restricted Access - Internal Team Only"
    AuthUserFile /etc/apache2/.htpasswd
    Require valid-user

    # Allow health check to pass without authentication
    <Files "healthz.php">
        Require all granted
    </Files>
</Directory>
EOF
    a2enconf basic-auth > /dev/null 2>&1
else
    echo "No AUTH_USER / AUTH_PASSWORD set. Basic Auth is disabled."
    a2disconf basic-auth > /dev/null 2>&1 || true
fi

# Start Apache in foreground
exec apache2-foreground
