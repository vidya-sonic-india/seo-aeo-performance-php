#!/bin/bash
set -e

# Default to port 80 if PORT environment variable is not passed
PORT="${PORT:-80}"

echo "Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen [0-9]*/Listen ${PORT}/" /etc/apache2/ports.conf

# Ensure Apache passes environment variables to PHP
{
    echo "PassEnv VEBAPI_KEY"
    if [ -n "$AUTH_USER" ]; then echo "PassEnv AUTH_USER"; fi
    if [ -n "$AUTH_PASSWORD" ]; then echo "PassEnv AUTH_PASSWORD"; fi
} > /etc/apache2/conf-available/environment.conf
a2enconf environment > /dev/null 2>&1

# Export into apache2 envvars as an additional safeguard
if [ -n "$VEBAPI_KEY" ]; then
    echo "export VEBAPI_KEY=\"$VEBAPI_KEY\"" >> /etc/apache2/envvars
fi
if [ -n "$AUTH_USER" ]; then
    echo "export AUTH_USER=\"$AUTH_USER\"" >> /etc/apache2/envvars
fi
if [ -n "$AUTH_PASSWORD" ]; then
    echo "export AUTH_PASSWORD=\"$AUTH_PASSWORD\"" >> /etc/apache2/envvars
fi

# Ensure cache directory exists and has correct permissions
mkdir -p /var/www/html/cache
chown -R www-data:www-data /var/www/html/cache
chmod -R 775 /var/www/html/cache

# Configure VirtualHost with or without Basic Auth directly inside the vhost
if [ -n "$AUTH_USER" ] && [ -n "$AUTH_PASSWORD" ]; then
    echo "Configuring HTTP Basic Authentication for user '${AUTH_USER}'..."
    htpasswd -bc /etc/apache2/.htpasswd "$AUTH_USER" "$AUTH_PASSWORD"
    AUTH_CONFIG="        AuthType Basic
        AuthName \"Restricted Access - Internal Team Only\"
        AuthUserFile /etc/apache2/.htpasswd
        Require valid-user

        <Files \"healthz.php\">
            Require all granted
        </Files>"
else
    echo "No AUTH_USER / AUTH_PASSWORD set. Basic Auth is disabled."
    AUTH_CONFIG="        Require all granted"
fi

cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    Timeout 120

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
${AUTH_CONFIG}
    </Directory>

    <Directory /var/www/html/app>
        Require all denied
    </Directory>

    <Directory /var/www/html/cache>
        Require all denied
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Start Apache in foreground
exec apache2-foreground
