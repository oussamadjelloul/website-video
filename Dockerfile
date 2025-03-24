FROM php:8.3-apache
# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    ffmpeg
# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo pdo_mysql mysqli gd

# IMPORTANT: Copy php.ini-production to php.ini first
#COPY ./php/custom.ini /usr/local/etc/php/conf.d/custom.ini

# METHOD 1: Modify php.ini directly
#RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 1024M/g' /usr/local/etc/php/php.ini
#RUN sed -i 's/post_max_size = 8M/post_max_size = 1100M/g' /usr/local/etc/php/php.ini
#RUN sed -i 's/memory_limit = 128M/memory_limit = 2048M/g' /usr/local/etc/php/php.ini
#RUN sed -i 's/max_execution_time = 30/max_execution_time = 300/g' /usr/local/etc/php/php.ini
#RUN sed -i 's/max_input_time = 60/max_input_time = 300/g' /usr/local/etc/php/php.ini



# Set Apache LimitRequestBody
RUN echo "LimitRequestBody 2000000000" >> /etc/apache2/apache2.conf

# Enable Apache modules
RUN a2enmod rewrite headers
# Set working directory
WORKDIR /var/www/html
# Copy composer.json and composer.lock
COPY composer.json composer.lock ./
# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Install project dependencies
RUN composer install --no-scripts --no-autoloader
# Copy the rest of the application
COPY . .
# Generate optimized autoloader
RUN composer dump-autoload --optimize
# Set permissions for storage directories
RUN mkdir -p public/uploads/images public/uploads/videos public/uploads/thumbnails && \
    chmod -R 777 public/uploads
# Update Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/../!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
# Create a copy of .env.example to .env if .env doesn't exist
RUN if [ ! -f .env ]; then cp .env.example .env || echo "No .env.example found"; fi
# Expose port 80
EXPOSE 80
COPY custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini
# Start Apache
CMD ["apache2-foreground"]
