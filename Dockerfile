# Usa l'immagine ufficiale di PHP con Apache
FROM php:apache

# Installa l'estensione mysqli
RUN docker-php-ext-install mysqli

# Abilita il modulo mod_headers di Apache per gestire le intestazioni
RUN a2enmod headers

# Aggiungi le configurazioni CORS per Apache
RUN echo '<IfModule mod_headers.c>\n\
    Header set Access-Control-Allow-Origin "*"\n\
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"\n\
    Header set Access-Control-Allow-Headers "Origin, Content-Type, Authorization"\n\
    </IfModule>' > /etc/apache2/conf-available/cors.conf \
    && a2enconf cors

# Copia i file del progetto nella cartella di lavoro di Apache
COPY ./src /var/www/html/

# Imposta i permessi corretti per la cartella di lavoro
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Espone la porta 80
EXPOSE 80