FROM php:8.2-apache

# Habilitar mod_rewrite y sesiones
RUN a2enmod rewrite

# Configurar PHP para sesiones
RUN echo "session.save_path = '/tmp'" >> /usr/local/etc/php/php.ini

# Copiar archivos
COPY index.php /var/www/html/

# Dar permisos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Exponer puerto 80
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
```

## 📦 Archivo 3: `.dockerignore`
```
.git
.gitignore
README.md
.env