FROM php:8.2-apache

# Install apache2-utils (for htpasswd) and curl
RUN apt-get update && apt-get install -y --no-install-recommends \
    apache2-utils \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Verify or install curl extension
RUN php -m | grep -i curl || docker-php-ext-install curl

# Enable required Apache modules
RUN a2enmod rewrite headers auth_basic authn_file authz_core authz_user

# Custom PHP settings for timeout and memory
RUN { \
        echo "max_execution_time = 120"; \
        echo "max_input_time = 120"; \
        echo "memory_limit = 256M"; \
        echo "post_max_size = 32M"; \
        echo "upload_max_filesize = 32M"; \
        echo "display_errors = Off"; \
        echo "log_errors = On"; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Configure Apache virtual host with AllowOverride and folder protections
RUN { \
        echo '<VirtualHost *:80>'; \
        echo '    ServerAdmin webmaster@localhost'; \
        echo '    DocumentRoot /var/www/html'; \
        echo '    Timeout 120'; \
        echo '    <Directory /var/www/html>'; \
        echo '        Options -Indexes +FollowSymLinks'; \
        echo '        AllowOverride All'; \
        echo '        Require all granted'; \
        echo '    </Directory>'; \
        echo '    <Directory /var/www/html/app>'; \
        echo '        Require all denied'; \
        echo '    </Directory>'; \
        echo '    <Directory /var/www/html/cache>'; \
        echo '        Require all denied'; \
        echo '    </Directory>'; \
        echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
        echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
        echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy and configure entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Ensure cache directory exists and www-data has ownership
RUN mkdir -p /var/www/html/cache \
    && chown -R www-data:www-data /var/www/html/cache \
    && chmod -R 775 /var/www/html/cache

# Build-time syntax checks: verify all PHP files compile cleanly
RUN php -l index.php \
    && php -l aeo.php \
    && php -l performance.php \
    && php -l report.php \
    && php -l seo.php \
    && php -l healthz.php \
    && php -l app/core.php \
    && php -l app/view.php \
    && php -l app/header.php \
    && php -l app/footer.php \
    && php -l app/sections/aeo.php \
    && php -l app/sections/performance.php \
    && php -l app/sections/seo.php

# Build-time verification: test running PHP with demo key
RUN VEBAPI_KEY=demo php -r "define('APP', 1); require 'app/core.php'; echo 'Build check: app/core.php loaded successfully.\n';"

# Default port
EXPOSE 80 10000

ENTRYPOINT ["docker-entrypoint.sh"]
