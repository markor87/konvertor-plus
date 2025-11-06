# Упутство за инсталацију - Конвертор+

Детаљна упутства за инсталацију и покретање Laravel апликације за конвертовање латинице и ћирилице.

## Системски захтеви

### Обавезно:
- **PHP** >= 8.1
- **Composer** (најновија верзија)
- **Web сервер** (Apache, Nginx, или Laravel development server)

### PHP екстензије:
- BCMath PHP Extension
- Ctype PHP Extension
- Fileinfo PHP Extension
- JSON PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- PDO PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- ZIP PHP Extension
- GD PHP Extension

## Инсталација корак по корак

### 1. Преузмите пројекат

```bash
git clone <repository-url> konvertor-plus
cd konvertor-plus
```

### 2. Инсталирајте PHP зависности

```bash
composer install
```

Ако добијете грешку о недостајућим екстензијама, инсталирајте их:

**Ubuntu/Debian:**
```bash
sudo apt-get install php8.1-zip php8.1-xml php8.1-mbstring php8.1-gd
```

**macOS (Homebrew):**
```bash
brew install php@8.1
brew install composer
```

**Windows:**
Преузмите XAMPP или користите WAMP/Laragon који долазе са потребним екстензијама.

### 3. Конфигуришите окружење

```bash
# Копирајте .env пример фајл
cp .env.example .env

# Генеришите апликациони кључ
php artisan key:generate
```

### 4. Креирајте потребне директоријуме

```bash
# Креирајте директоријуме за upload
mkdir -p storage/app/uploads/docx
mkdir -p storage/app/uploads/xlsx
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Поставите дозволе (Linux/macOS)
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

**Windows:**
Није потребно мењати дозволе.

### 5. Покрените апликацију

```bash
php artisan serve
```

Апликација ће бити доступна на: **http://localhost:8000**

Алтернативно, можете користити други порт:
```bash
php artisan serve --port=8080
```

### 6. Отворите у претраживачу

Идите на: `http://localhost:8000`

## Production Deploy

### Коришћење Apache

1. **Подесите Apache виртуални хост:**

```apache
<VirtualHost *:80>
    ServerName konvertor.example.com
    DocumentRoot /var/www/konvertor-plus/public

    <Directory /var/www/konvertor-plus/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/konvertor-error.log
    CustomLog ${APACHE_LOG_DIR}/konvertor-access.log combined
</VirtualHost>
```

2. **Омогућите mod_rewrite:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Коришћење Nginx

```nginx
server {
    listen 80;
    server_name konvertor.example.com;
    root /var/www/konvertor-plus/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Production оптимизација

```bash
# Кеширај конфигурацију
php artisan config:cache

# Кеширај руте
php artisan route:cache

# Кеширај views
php artisan view:cache

# Оптимизуј Composer autoloader
composer install --optimize-autoloader --no-dev

# Подеси .env за production
APP_ENV=production
APP_DEBUG=false
```

## Решавање проблема

### Грешка: "The stream or file could not be opened"

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Грешка: "No application encryption key has been specified"

```bash
php artisan key:generate
```

### Грешка: "Class not found"

```bash
composer dump-autoload
```

### Upload не ради

Проверите `php.ini` подешавања:
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

Рестартујте веб сервер после измена.

### ZIP екстензија недостаје

**Ubuntu/Debian:**
```bash
sudo apt-get install php8.1-zip
sudo systemctl restart apache2
```

**macOS:**
```bash
pecl install zip
```

## Тестирање

Да проверите да ли све ради:

1. Отворите http://localhost:8000
2. Идите на "Превођење текста"
3. Унесите: "Dobar dan"
4. Кликните "Конвертуј у ћирилицу"
5. Требало би да видите: "Добар дан"

## Безбедност

За production окружење:

1. **Увек користите HTTPS**
2. **Поставите APP_DEBUG=false у .env**
3. **Огранчите upload величину**
4. **Регуларно ажурирајте зависности:**
   ```bash
   composer update
   ```

## Подршка

Ако наиђете на проблеме:
1. Проверите Laravel лог: `storage/logs/laravel.log`
2. Проверите PHP верзију: `php -v`
3. Проверите инсталиране екстензије: `php -m`

---

**Срећно коришћење Конвертор+ апликације!** 🎉
