# Конвертор+ (Laravel верзија)

Laravel веб апликација за конвертовање текста, DOCX и XLSX фајлова између српске латинице и ћирилице.

## Карактеристике

- ✅ **Превођење текста** - Брза конвертација текста директно у претраживачу
- ✅ **DOCX конвертор** - Подршка за Word документе са очувањем форматирања
- ✅ **XLSX конвертор** - Обрада Excel табела са опцијом прескакања одређених колона
- ✅ **Batch обрада** - Могућност конвертовања више фајлова одједном
- ✅ **Паметна конвертација** - Узима у обзир изузетке за речи као "adjektiv", "injekција", итд.
- ✅ **Drag & Drop** - Превлачење фајлова за лакшу употребу

## Технологије

- **Backend**: Laravel 10.x, PHP 8.1+
- **Frontend**: Vanilla JavaScript (без зависности)
- **Библиотеке**:
  - `phpoffice/phpword` - за обраду Word докумената
  - `phpoffice/phpspreadsheet` - за обраду Excel табела

## Инсталација

### Предуслови

- PHP >= 8.1
- Composer
- PHP екстензије: `zip`, `xml`, `mbstring`, `gd`

### Кораци

1. **Клонирајте репозиторијум**
   ```bash
   git clone <repository-url>
   cd konvertor-plus
   ```

2. **Инсталирајте зависности**
   ```bash
   composer install
   ```

3. **Креирајте .env фајл**
   ```bash
   cp .env.example .env
   ```

4. **Генеришите апликациони кључ**
   ```bash
   php artisan key:generate
   ```

5. **Креирајте потребне директоријуме**
   ```bash
   mkdir -p storage/app/uploads/{docx,xlsx}
   chmod -R 775 storage bootstrap/cache
   ```

6. **Покрените апликацију**
   ```bash
   php artisan serve
   ```

7. **Отворите у претраживачу**
   ```
   http://localhost:8000
   ```

## Коришћење

### 1. Превођење текста

- Идите на таб "Превођење текста"
- Унесите или налепите текст у горње поље
- Кликните "Конвертуј у латиницу" или "Конвертуј у ћирилицу"
- Резултат ће се приказати у доњем пољу
- Користите дугме "📋 Копирај" да копирате резултат

### 2. DOCX превод

- Идите на таб "DOCX превод"
- Одаберите правац превођења (ћирилица или латиница)
- Кликните на област за upload или превуците DOCX фајлове
- Кликните "Конвертуј"
- Фајл(ови) ће се аутоматски преузети

### 3. XLSX превод

- Идите на таб "XLSX превод"
- Одаберите правац превођења
- Кликните на област за upload или превуците XLSX фајлове
- Опционално одаберите колоне које желите да прескочите (нпр. ID колоне, бројеви)
- Кликните "Конвертуј"
- Фајл(ови) ће се аутоматски преузети

## Структура пројекта

```
konvertor-plus/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── ConversionController.php  # Главни контролер
│   └── Services/
│       ├── TextConverterService.php      # Сервис за текстуалну конвертацију
│       ├── DocxConverterService.php      # Сервис за DOCX конвертацију
│       └── XlsxConverterService.php      # Сервис за XLSX конвертацију
├── resources/
│   └── views/
│       └── converter.blade.php           # Главни view
├── routes/
│   └── web.php                           # Web рута
├── storage/
│   └── app/
│       └── uploads/                      # Привремени фајлови
└── composer.json                         # PHP зависности
```

## API Endpoints

### POST `/convert/text`
Конвертује текст.

**Request:**
```json
{
  "text": "Текст за конвертовање",
  "direction": "cirilica" // ili "latinica"
}
```

**Response:**
```json
{
  "success": true,
  "result": "Конвертовани текст"
}
```

### POST `/convert/docx`
Конвертује DOCX фајл(ове).

**Request:** `multipart/form-data`
- `direction`: "cirilica" ili "latinica"
- `files[]`: Array of DOCX files

**Response:** File download (single file or ZIP)

### POST `/convert/xlsx`
Конвертује XLSX фајл(ове).

**Request:** `multipart/form-data`
- `direction`: "cirilica" ili "latinica"
- `files[]`: Array of XLSX files
- `skip_columns[]`: Optional array of column headers to skip

**Response:** File download (single file or ZIP)

### POST `/xlsx/headers`
Добија загlavlja из XLSX фајла.

**Request:** `multipart/form-data`
- `file`: XLSX file

**Response:**
```json
{
  "success": true,
  "headers": [
    {"column": "A", "name": "Име колоне"},
    {"column": "B", "name": "Друга колона"}
  ]
}
```

## Конвертација - Детаљи

### Мапирање слова

**Ћирилица → Латиница:**
- Ђ/ђ → Dj/dj
- Ж/ж → Ž/ž
- Ч/ч → Č/č
- Ћ/ћ → Ć/ć
- Џ/џ → Dž/dž
- Њ/њ → Nj/nj
- Љ/љ → Lj/lj
- Ш/ш → Š/š
- И сва остала слова...

### Изузеци

Апликација води рачуна о речима које **не треба** конвертовати стандардно:

- **"dj" изузеци**: adjektiv, djed, gdje, negdje, itd. (150+ речи)
- **"nj" изузеци**: injekcija, konjugacija, konjunktura, итд.
- **"dž" изузеци**: podžanr, nadživ, итд.

Ове речи се конвертују без диграфа (нпр. "injekcija" → "инјекција", a не "инђекција").

## Ограничења

- Максимална величина фајла: **10MB**
- Подржани формати: **DOCX**, **XLSX** (не DOC или XLS)
- Upload се ради преко HTTP (за production користити HTTPS)

## Лиценца

MIT License

## Аутор

Конвертована из C# WPF апликације у Laravel апликацију.

---

**Напомена**: Ова апликација је намењена за конвертовање српског језика између латиничног и ћириличног писма, водећи рачуна о специфичностима српске ортографије.
