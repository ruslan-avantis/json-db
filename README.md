# «API json DB»
Система управления базами данных с открытым исходным кодом которая использует JSON схему и документы. Написана на PHP. Распространяется по лицензии [MIT](https://opensource.org/licenses/MIT). Подключается через Composer как обычный пакет PHP, после подключения сама настраивается за несколько секунд. Имеет свой RESTful API интерфейс, что позволяет использовать ее с любым другим языком программирования. Фактически это менеджер json файлов с возможностью кеширования популярных запросов, проверкой валидности файлов и очередью на запись при блокировке таблиц (файлов db) на запись другими процессами. 

Ядром для «API json DB» мы выбрали прекрасную работу [Greg0/Lazer-Database](https://github.com/Greg0/Lazer-Database/). 
В связи с тем что нам необходимо добавлять новые файлы и вносить изменение в файлы мы не используем оригинальный пакет.

## Старт
Подключить пакет с помощью Composer
```json
"require": {
	"pllano/api-json-db": "*"
}
```
```php
// Указываем директорию где будет храниться json db
$_db = __DIR__ . '/../../_db_/';

// Запускаем jsonDB
$db = new \jsonDB\Db($_db);
$db->run();
```
или одной строчкой
```php
(new \jsonDB\Db(__DIR__ . '/../../_db_/'))->run();
```
### Автоматическое разворачиваение
При запуске в папке `_db_` которую вы указали база создаст:   
Таблицу своей конфигурации `db.data.json` и `db.config.json`   
Таблицу для кеша `cached.data.json` и `cached.config.json`  

### Автоматическое создание таблиц базы данных
Для автоматического создания таблиц отредактируйте файл [db.json](https://github.com/pllano/api-json-db/blob/master/_db_/core/db.json) и скопируйте его в директорию `/_db_/core/`	 

### Поддерживаемые типы данных
- `boolean` — Логический тип `true` или `false`
- `integer` — Целое число	
- `string` — Строковый тип
- `double` — Число с плавающей точкой
### Создать таблицу в базе данных
```php
$arr = array(
    'id' => 'integer',
    'name' => 'string',
    'название_поля' => 'тип данных'
);

jsonDb::create('table_name', $arr);
```	
### Удалить таблицу в базе данных
```php
jsonDb::remove('table_name');
```
#### Очистить таблицу
```php
jsonDb::table('table_name')->delete();
```
### Проверьте, существует ли таблица в базе данных
```php
try{
    \jsonDb\Validate::table('table_name')->exists();
} catch(\jsonDb\jsonDbException $e){
    // Таблица не существует
}
```
#### Создать запись в таблице
```php
$row = jsonDb::table('table_name');
$row->name = 'Ivan';
$row->save();
```
#### Получить данные с таблицы
```php
echo jsonDb::table('table_name')->where('name', '=', 'Ivan')->findAll();
// или по id
echo jsonDb::table('table_name')->where('id', '=', '10')->findAll();
```
#### Обновить данные
```php
$row = jsonDb::table('table_name')->find(10);
$row->name = 'Andrey';
$row->save();
```
#### Удалить запись по id
```php
jsonDb::table('table_name')->find(10)->delete();
```

## RESTful API роутинг для cURL запросов
«API json DB» имеет свой RESTfull API роутинг для cURL запросов который написан на PHP с использованием Micro Framework [Slim](https://github.com/slimphp), что позволяет использовать «API json DB» с любым другим языком программирования. Для унификации обмена данными сервер-сервер и клиент-сервер используется стандарт [APIS-2018](https://github.com/pllano/APIS-2018/). `Стандарт APIS-2018 - не является общепринятым` и является нашим взглядом в будущее и рекомендацией для унификации построения легких движков интернет-магазинов нового поколения.

### RESTfull API состоит всего из двух файлов:
- [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) и [.htaccess](https://github.com/pllano/api-json-db/blob/master/api/.htaccess)

Для установки `RESTful API` выполните следующие действия:

- В файле [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) укажите директорию где хранится база, например `/www/_db_/` или `__DIR__ . '/../../_db_/'`.

- Перенесите файлы [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) и [.htaccess](https://github.com/pllano/api-json-db/blob/master/api/.htaccess) в директорию доступную через URL например: `https://example.com/_12345_/`

- Запустите `https://example.com/_12345_/`		
- Если база работает Вы увидите следующий результат:
```json
{
    "status": "OK",
    "code": 200,
    "message": "db_json_api works!"
}
```

### RESTful API jsonDB потдерживает запросы:
- `POST /price` Создание записи 
- `POST /price/42` Ошибка
- `GET /price` Список прайс-строк
- `GET /price/42` Данные конкретной прайс-строки
- `PUT /price` Обновить данные прайс-строк
- `PUT /price/42` Обновить данные конкретной прайс-строки
- `DELETE /price` Удалить все прайс-строки
- `DELETE /price/42` Удалить конкретную прайс-строку

Для тех кто может отправлять только с `POST` и `GET` запросы мы дублируем тип запроса в параметре `query`

### URL RESTful API jsonDB
- `https://example.com/{api_dir}/{table_name}/{id}`
- `{api_dir}` - папка в которой лежит 
- `{table_name}` - название таблицы к которой обращаемся. Например price или user.
- `{id}` - уникальный индефикатор

### GET запрос к RESTful API jsonDB
`?offset={offset}&limit={limit}&order={order}&sort={sort}&key={key}`
- `{key}` - Ключ доступа к RESTful API
- `{limit}` - Записей на страницу. По умолчанию 10
- `{offset}` - Страница. По умолчанию 0
- `{order}` - Тип сортировки. По умолчанию asc
- `{sort}` - Поле сортировки. По умолчанию id
- `{*}` - Любое из полей таблицы

[Список всех параметров запроса](doc/query.md)

### RESTful API jsonDB - Всегда возвращает код 200 даже при логических ошибках !

`HTTP/1.1 200 OK`

`Content-Type: application/json`

### В теле ответа RESTful API jsonDB вернет код ошибки, статус и описание ошибки.

[Коды ошибок HTTP](doc/errors.md)

### Прямое подключение к DB
Если вам не нужен API роутинг Вы можете работать с базой данных напрямую без REST API интерфейса - [Документация](https://github.com/pllano/api-json-db/blob/master/db.md) или использовать оригинальный пакет [Lazer-Database](https://github.com/Greg0/Lazer-Database/).

<a name="feedback"></a>
## Поддержка, обратная связь, новости

Общайтесь с нами через почту open.source@pllano.com

Если вы нашли баг в работе API json DB загляните в
[issues](https://github.com/pllano/api-json-db/issues), возможно, про него мы уже знаем и
чиним. Если нет, лучше всего сообщить о нём там. Там же вы можете оставлять свои
пожелания и предложения.

За новостями вы можете следить по
[коммитам](https://github.com/pllano/api-json-db/commits/master) в этом репозитории.
[RSS](https://github.com/pllano/api-json-db/commits/master.atom).

Лицензия API json DB
-------

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.

