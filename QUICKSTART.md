# Брзи старт - Конвертор+

Брза упутства за покретање апликације за конвертовање латинице и ћирилице.

## За раднике (5 минута)

### Корак 1: Преузми и инсталирај

```bash
# Клонирај пројекат
git clone <repository-url> konvertor-plus
cd konvertor-plus

# Инсталирај зависности
composer install
```

### Корак 2: Конфигуриши

```bash
# Креирај .env фајл
cp .env.example .env

# Генериши апликациони кључ
php artisan key:generate
```

### Корак 3: Покрени

```bash
php artisan serve
```

✅ **Отвори**: http://localhost:8000

---

## За почетнике

### Шта треба да имам?

- **PHP 8.1 или новији** - Преузми са https://www.php.net/downloads
- **Composer** - Преузми са https://getcomposer.org/download/

### Windows корисници

1. Преузми и инсталирај **XAMPP**: https://www.apachefriends.org/
2. Отвори Command Prompt у фолдеру пројекта
3. Изврши горње команде

### macOS корисници

```bash
# Инсталирај Homebrew ако немаш
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Инсталирај PHP
brew install php composer
```

### Linux корисници (Ubuntu/Debian)

```bash
# Инсталирај PHP и Composer
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-common php8.1-zip php8.1-gd php8.1-mbstring php8.1-xml composer
```

---

## Брзо тестирање

### Тест 1: Текст

1. Отвори http://localhost:8000
2. Унеси: `Dobar dan, kako si?`
3. Кликни **Конвертуј у ћирилицу**
4. Требало би да видиш: `Добар дан, како си?`

### Тест 2: DOCX фајл

1. Иди на таб **DOCX превод**
2. Одабери **Ћирилица** или **Латиница**
3. Upload-уј `.docx` фајл
4. Кликни **Конвертуј**
5. Преузми конвертован фајл

### Тест 3: XLSX фајл

1. Иди на таб **XLSX превод**
2. Одабери смер превођења
3. Upload-уј `.xlsx` фајл
4. Опционално штиклирај колоне које желиш да прескочиш
5. Кликни **Конвертуј**

---

## Решавање честих проблема

### Проблем: `composer: command not found`

**Решење**: Инсталирај Composer са https://getcomposer.org/

### Проблем: `PHP version required >= 8.1`

**Решење**: Ажурирај PHP:
```bash
# Ubuntu/Debian
sudo apt install php8.1

# macOS
brew upgrade php
```

### Проблем: `The stream or file could not be opened`

**Решење**:
```bash
chmod -R 775 storage bootstrap/cache
```

### Проблем: Upload не ради

**Решење**: Креирај upload директоријуме:
```bash
mkdir -p storage/app/uploads/{docx,xlsx}
chmod -R 775 storage
```

### Проблем: `Class not found`

**Решење**:
```bash
composer dump-autoload
```

---

## Напредне опције

### Промени порт

```bash
php artisan serve --port=8080
```

### Покрени у мрежи (доступно са других рачунара)

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### Production deploy

Погледај детаљна упутства у **INSTALL.md**

---

## Помоћ и подршка

- 📖 **Документација**: Погледај `README.md`
- 🔧 **Детаљна инсталација**: Погледај `INSTALL.md`
- 🏗️ **Структура пројекта**: Погледај `PROJECT_STRUCTURE.md`
- 📝 **Логови**: `storage/logs/laravel.log`

---

## Следећи кораци

1. ✅ Тестирај све функције
2. 📚 Прочитај пуну документацију (README.md)
3. 🚀 Deploy на production (INSTALL.md)
4. 🎨 Прилагоди изглед (resources/views/converter.blade.php)

---

**Срећно коришћење!** 🎉

Ако имаш проблеме, провери `storage/logs/laravel.log` за детаље.
