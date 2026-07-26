# mwART.solutions — PHP + Apache
# Bewusst schlank gehalten: statische Seiten + ein paar PHP-Endpunkte.
FROM php:8.3-apache

# Zeitzone für korrekte Zeitstempel in Statistik und Anfragen
ENV TZ=Europe/Berlin
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Module für saubere URLs, Sicherheits-Header, Komprimierung und Caching
RUN a2enmod rewrite headers deflate expires

# .htaccess-Dateien wirksam machen (Standard im Image ist AllowOverride None)
RUN printf '<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/mwart.conf \
 && a2enconf mwart

# Produktions-Konfiguration für PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'expose_php = Off\n\
display_errors = Off\n\
log_errors = On\n\
error_log = /dev/stderr\n\
upload_max_filesize = 8M\n\
post_max_size = 8M\n' > "$PHP_INI_DIR/conf.d/99-mwart.ini"

# Anwendungsdateien
COPY --chown=www-data:www-data . /var/www/html/

# Datenordner für Statistik und Kontaktanfragen (wird als Volume gemountet)
RUN mkdir -p /var/www/html/data \
 && printf 'Require all denied\n' > /var/www/html/data/.htaccess \
 && chown -R www-data:www-data /var/www/html/data

# Apache Standardseite deaktivieren
RUN rm -f /var/www/html/index.html.* \
 && echo "ServerName mwart.solutions" >> /etc/apache2/apache2.conf

# DirectoryIndex explizit setzen
RUN echo "DirectoryIndex index.html index.php" >> /etc/apache2/mods-enabled/dir.conf

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -f http://127.0.0.1/index.html || exit 1
