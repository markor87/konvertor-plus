# Структура пројекта - Конвертор+

## Директоријуми

```
konvertor-plus/
├── app/                          # Апликациони код
│   ├── Http/
│   │   └── Controllers/
│   │       └── ConversionController.php    # Главни контролер
│   └── Services/
│       ├── TextConverterService.php        # Текстуална конвертација
│       ├── DocxConverterService.php        # DOCX обрада
│       └── XlsxConverterService.php        # XLSX обрада
│
├── bootstrap/                    # Bootstrap скрипте
│   ├── app.php                   # Laravel bootstrap
│   └── cache/                    # Кеш
│
├── config/                       # Конфигурациони фајлови
│   ├── app.php                   # Апликациона конфигурација
│   ├── filesystems.php           # Filesystem конфигурација
│   └── session.php               # Session конфигурација
│
├── public/                       # Јавни директоријум (Document Root)
│   ├── index.php                 # Entry point
│   ├── .htaccess                 # Apache правила
│   └── robots.txt                # SEO robots
│
├── resources/                    # Views и assets
│   └── views/
│       └── converter.blade.php   # Главни view
│
├── routes/                       # Дефиниције рута
│   ├── web.php                   # Web руте
│   └── console.php               # Artisan команде
│
├── storage/                      # Storage за фајлове и логове
│   ├── app/
│   │   ├── public/               # Јавни storage
│   │   └── uploads/              # Upload директоријум
│   │       ├── docx/             # DOCX uploads
│   │       └── xlsx/             # XLSX uploads
│   ├── framework/                # Framework фајлови
│   │   ├── cache/
│   │   ├── sessions/
│   │   └── views/
│   └── logs/                     # Логови
│
├── .env.example                  # Пример environment фајла
├── .gitignore                    # Git ignore правила
├── artisan                       # Laravel CLI
├── composer.json                 # PHP зависности
├── INSTALL.md                    # Упутство за инсталацију
├── PROJECT_STRUCTURE.md          # Овај фајл
└── README.md                     # Документација

```

## Кључни фајлови

### Backend (PHP)

| Фајл | Опис |
|------|------|
| `app/Services/TextConverterService.php` | Сервис за конвертацију текста између латинице и ћирилице |
| `app/Services/DocxConverterService.php` | Сервис за обраду Word докумената |
| `app/Services/XlsxConverterService.php` | Сервис за обраду Excel табела |
| `app/Http/Controllers/ConversionController.php` | Главни контролер са методама за све типове конвертације |
| `routes/web.php` | Дефиниција web рута (GET /, POST /convert/*) |

### Frontend

| Фајл | Опис |
|------|------|
| `resources/views/converter.blade.php` | Single-page апликација са табовима и JavaScript логиком |

### Конфигурација

| Фајл | Опис |
|------|------|
| `.env.example` | Template за environment варијабле |
| `composer.json` | PHP зависности (Laravel, PHPWord, PhpSpreadsheet) |
| `config/app.php` | Laravel апликациона конфигурација |
| `config/filesystems.php` | Конфигурација storage система |

## Архитектура

### MVC Pattern

```
Request → Router → Controller → Service → Response
                        ↓
                      View
```

1. **Router** (`routes/web.php`) - Руте упућују на одговарајуће методе контролера
2. **Controller** (`ConversionController`) - Прима request, позива сервисе, враћа response
3. **Services** - Бизнис логика за конвертацију
4. **View** - Blade template са JavaScript за интеракцију

###Ток података

#### Превођење текста:
```
User input → POST /convert/text → ConversionController@convertText
                                         ↓
                                TextConverterService
                                         ↓
                                  JSON response
```

#### Превођење DOCX:
```
File upload → POST /convert/docx → ConversionController@convertDocx
                                         ↓
                                DocxConverterService
                                         ↓
                               File download (ZIP)
```

#### Превођење XLSX:
```
File upload → POST /convert/xlsx → ConversionController@convertXlsx
              (са колонама)               ↓
                                XlsxConverterService
                                         ↓
                               File download (ZIP)
```

## Зависности

### Composer packages (PHP)

- `laravel/framework` ^10.0 - Laravel framework
- `phpoffice/phpword` ^1.1 - Word документи
- `phpoffice/phpspreadsheet` ^1.29 - Excel табеле
- `guzzlehttp/guzzle` ^7.2 - HTTP клијент

### Frontend

- Vanilla JavaScript (без npm зависности)
- CSS3 за стилизацију
- Fetch API за AJAX позиве

## Storage структура

```
storage/
├── app/
│   ├── uploads/
│   │   ├── docx/              # Привремени DOCX фајлови
│   │   └── xlsx/              # Привремени XLSX фајлови
│   └── public/                # Јавно доступни фајлови
├── framework/
│   ├── cache/                 # Апликациони кеш
│   ├── sessions/              # Сесије
│   └── views/                 # Компајлирани Blade темплејти
└── logs/
    └── laravel.log            # Апликациони логови
```

## Проток обраде фајлова

1. **Upload** - Фајл се upload-ује преко form-data
2. **Validation** - Laravel валидација (тип, величина)
3. **Storage** - Чува се у `storage/app/uploads/{type}/`
4. **Processing** - Одговарајући сервис обрађује фајл
5. **Output** - Креира се нови фајл са суфиксом `_cirilica` или `_latinica`
6. **Download** - Фајл се шаље кориснику
7. **Cleanup** - Привремени фајлови се бришу

## API Endpoints

| Method | Path | Controller Method | Опис |
|--------|------|-------------------|------|
| GET | `/` | `index()` | Приказује главну страницу |
| POST | `/convert/text` | `convertText()` | Конвертује текст |
| POST | `/convert/docx` | `convertDocx()` | Конвертује DOCX |
| POST | `/convert/xlsx` | `convertXlsx()` | Конвертује XLSX |
| POST | `/xlsx/headers` | `getXlsxHeaders()` | Добија загlavlja из XLSX |

## Безбедност

- CSRF заштита (Laravel токени)
- Валидација типова фајлова
- Ограничење величине upload-а (10MB)
- Санитизација корисничког input-а
- Session security (HTTP-only cookies)

## Перформансе

- Lazy loading за велике фајлове
- Stream processing за XLSX
- Progress bar симулација
- Batch обрада више фајлова
- ZIP компресија за multiple downloads

---

**Напомена**: Ова структура следи Laravel best practices и омогућава лако одржавање и проширење апликације.
