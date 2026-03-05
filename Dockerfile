# --- 階段 1: 編譯前端 Vue 資源 ---
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# --- 階段 2: 建立 PHP 運行環境 ---
FROM php:8.3-fpm-alpine

# 安裝系統套件與 PHP 擴展
RUN apk add --no-cache \
    nginx \
    wget \
    icu-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git

RUN docker-php-ext-install pdo_mysql intl zip bcmath

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 複製後端檔案
COPY . .
# 從前端階段複製編譯好的資源
COPY --from=frontend-builder /app/public/build ./public/build

# 安裝 PHP 依賴
RUN composer install --no-dev --optimize-autoloader

# 設定權限
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 複製 Nginx 設定
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# 暴露 8080 埠 (Cloud Run 預設)
EXPOSE 8080

# 啟動腳本
CMD php-fpm -D && nginx -g "daemon off;"
