FROM php:8.2-apache

ENV TZ=Asia/Hong_Kong
RUN echo "date.timezone = Asia/Hong_Kong" > /usr/local/etc/php/conf.d/timezone.ini

# Copy all your project files into the container's web root
COPY . /var/www/html/

# Ensure proper permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for web traffic
EXPOSE 80
