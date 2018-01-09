# «jsonDB» - JSON база данных
JSON база данных с открытым исходным кодом. Написана на PHP. Распространяется по лицензии [MIT](https://opensource.org/licenses/MIT). Подключается через Composer как обычный пакет PHP, после подключения сама настраивается за несколько секунд. Имеет свой RESTful API интерфейс работающий по стандарту обмена информацией сервер-сервер «[APIS-2018](https://github.com/pllano/APIS-2018)», что позволяет использовать ее с любым другим языком программирования. «API json DB» это продвинутый менеджер json файлов с возможностью кеширования популярных запросов, шифрования файлов db, проверкой валидности файлов и очередью на запись при блокировке таблиц (файлов db) на запись другими процессами. Основанием для «API json DB» мы выбрали прекрасную работу [Greg0/Lazer-Database](https://github.com/Greg0/Lazer-Database/). Мы полностью изменили структуру оригинала и добавили: шифрование, API роутинг, кеширование, проверку валидности, очередь на запись и другой удобный функционал.

### Демо база данных [`https://xti.com.ua/json-db/`](https://xti.com.ua/json-db/) через RESTful API интерфейс
Для удобства мы отключили авторизацию через `public_key`

Примеры демо запросов: [demo](https://github.com/pllano/json-db/blob/master/demo.md)

Демо сайт работающий на «jsonDB» - https://xti.com.ua/

## Старт за несколько минут
Подключить с помощью [Composer](https://getcomposer.org/)
```json
"require": {
	"pllano/json-db": "^1.0.5"
}
```
Подключить с помощью [AutoRequire](https://github.com/pllano/auto-require)
```json
"require" [
    {
        "namespace": "jsonDB",
        "dir": "/pllano/json-db/src",
        "link": "https://github.com/pllano/json-db/archive/master.zip",
        "name": "json-db",
        "version": "master",
        "vendor": "pllano"
    }
]
```

### Инструменты для тестирования API
- [Postman](https://www.getpostman.com/postman) - это мощный набор инструментов тестирования API
- [SOAPUI](https://www.soapui.org/rest-testing/getting-started.html) - приложение для тестирования, мониторинга и проверки функциональности REST API.
### Инструменты для работы с API
- [cURL](http://php.net/manual/ru/book.curl.php) - Клиентская библиотека PHP работы с URL
- [Guzzle](https://github.com/guzzle/guzzle) - HTTP-клиент PHP
## RESTful API роутинг для cURL запросов
«API json DB» имеет свой RESTfull API роутинг для cURL запросов который написан на PHP с использованием [Micro Framework Slim](https://github.com/slimphp), что позволяет использовать «API json DB» с любым другим языком программирования. Для унификации обмена данными и структуры базы данных используется наш собственный стандарт [APIS-2018](https://github.com/pllano/APIS-2018/).
### RESTfull API состоит всего из двух файлов:
- [index.php](https://github.com/pllano/json-db/blob/master/api/index.php) и [.htaccess](https://github.com/pllano/json-db/blob/master/api/.htaccess)
### Для установки `RESTful API` выполните следующие действия:
- В файле [index.php](https://github.com/pllano/json-db/blob/master/api/index.php) укажите директорию где хранится база, например `/www/_db_/` или `__DIR__ . '/../../_db_/'`.
- Перенесите файлы [index.php](https://github.com/pllano/json-db/blob/master/api/index.php) и [.htaccess](https://github.com/pllano/json-db/blob/master/api/.htaccess) в директорию доступную через URL. Например: `https://example.com/_12345_/`
- Запустите API перейдя по ссылке `https://example.com/_12345_/`		
- Если база работает Вы увидите следующий результат:
```json
{
    "headers": {
        "status": "200 OK",
        "code": 200,
        "message": "RESTfull API json DB works!",
        "message_id": "https:\/\/github.com\/pllano\/APIS-2018\/tree\/master\/http-codes\/200.md"
    }
}
```
### Автоматическое разворачиваение
При запуске база создаст в папке `_db_` которую вы указали:
- Таблицу своей конфигурации `db.data.json` и `db.config.json`  
- Таблицу для кеша `cached.data.json` и `cached.config.json`
- Таблицу для очереди запросов `queue.data.json` и `queue.config.json`
- Директории: `cached` `core` `log`
- В директории `core` сгенерирует файл с ключем для http запросов key_db.txt если его там еще нет.
- В директорию `core` скачает этот [db.json](https://github.com/pllano/db.json/blob/master/db.json) файл структуры если его там еще нет.
### Автоматическое создание ресурсов
База автоматически создаст все ресурсы и связи указанные в файле [db.json](https://github.com/pllano/json-db/blob/master/_db_/core/db.json). Для создания индивидуальной конфигурации ресурсов отредактируйте файл [db.json](https://github.com/pllano/json-db/blob/master/_db_/core/db.json) и перед запуском скопируйте его в директорию `/_db_/core/`.
### Поддерживаемые типы данных в db.json
- `boolean` — Логический тип `true` или `false`
- `integer` — Целое число
- `double` — Число с плавающей точкой
- `string` — Строка
### Структура базы данных для интернет-магазина
Структура базы данных [db.json](https://github.com/pllano/db.json) выведена в отдельный репозиторий
### URL запросов к RESTful API jsonDB
- `https://example.com/{api_dir}/{table_name}/{id}`
- `{api_dir}` - папка в которой лежит [index.php](https://github.com/pllano/json-db/blob/master/api/index.php)
- `{resource}` - название ресурса к которому обращаемся. Например price или user.
- `{id}` - уникальный индефикатор
- `{param}` - праметры запроса
### GET запрос к RESTful API jsonDB
`?offset={offset}&limit={limit}&order={order}&sort={sort}&public_key={public_key}`
- `{public_key}` - Ключ доступа к RESTful API
- `{limit}` - Записей на страницу. По умолчанию 10
- `{offset}` - Страница. По умолчанию 0
- `{order}` - Тип сортировки. По умолчанию asc
- `{sort}` - Поле сортировки. По умолчанию id
- `{*}` - Любое из полей таблицы
### RESTful API jsonDB потдерживает `POST` `GET` `PUT` `PATCH` `DELETE` запросы:
- `POST /{resource}` Создание записи 
- `POST /{resource}/{id}` Ошибка
- `GET /{resource}` Список всех записей
- `GET /{resource}?{param}` Список всех записей с фильтром по параметрам
- `GET /{resource}/{id}` Данные конкретной записи
- `PUT /{resource}` Обновить данные записей
- `PUT /{resource}/{id}` Обновить данные конкретной записи
- `PATCH /{resource}` Обновить данные записей
- `PATCH /{resource}/{id}` Обновить данные конкретной записи
- `DELETE /{resource}` Удалить все записи
- `DELETE /{resource}/{id}` Удалить конкретную запись
### При желании Вы можете использовать только `GET` запросы:
- `GET /_post/{resource}?{param}` Создание записи 
- `GET /_post/{resource}/{id}` Ошибка
- `GET /_get/{resource}?{param}` Список всех записей с фильтром по параметрам
- `GET /_get/{resource}/{id}` Данные конкретной записи
- `GET /_put/{resource}?{param}` Обновить данные записей
- `GET /_put/{resource}/{id}?{param}` Обновить данные конкретной записи
- `GET /_patch/{resource}?{param}` Обновить данные записей
- `GET /_patch/{resource}/{id}?{param}` Обновить данные конкретной записи
- `GET /_delete/{resource}` Удалить все записи
- `GET /_delete/{resource}/{id}` Удалить конкретную запись
### Пример использования с HTTP клиентом Guzzle
``` php	
use GuzzleHttp\Client as Guzzle;

$public_key = $config['settings']['db']['public_key']; // Взять key из конфигурации `https://example.com/_12345_/index.php`

$resource = 'db';
$id = '1';

// $uri = 'https://example.com/_12345_/'.$resource.'?public_key='.$public_key;
$uri = 'https://example.com/_12345_/'.$resource.'/'.$id.'?public_key='.$public_key;

$client = new Guzzle();
$resp = $client->request('GET', $uri);
$get_body = $resp->getBody();

// Чистим все что не нужно, иначе json_decode не сможет конвертировать json в массив
for ($i = 0; $i <= 31; ++$i) {$get_body = str_replace(chr($i), "", $get_body);}
$get_body = str_replace(chr(127), "", $get_body);
if (0 === strpos(bin2hex($get_body), 'efbbbf')) {$get_body = substr($get_body, 3);}

$response = json_decode($get_body, true);

if (isset($response["headers"]["code"])) {
    if ($response["headers"]["code"] == 200) {
        $count = count($response["body"]["items"]);
        if ($count >= 1) {
            foreach($response["body"]["items"] as $item)
            {
                // Если $value object переводим в array
                $item = is_array($value["item"]) ? $item["item"] : (array)$value["item"];
                // Получаем данные
                print_r($item["name"]);
            }
        }
    }
}
```

### RESTful API jsonDB - Всегда возвращает код 200 даже при логических ошибках !

`HTTP/1.1 200 OK`

`Content-Type: application/json`

### В теле ответа RESTful API jsonDB вернет код состояния HTTP, статус и описание.

[Коды состояния HTTP](https://github.com/pllano/APIS-2018/tree/master/http-codes)

## Безопасность
[Советы по увеличению безопасности API json DB](https://github.com/pllano/json-db/blob/master/doc/security.md)

## Прямое подключение к DB
Если вам не нужен API роутинг Вы можете работать с базой данных напрямую без REST API интерфейса - [Документация - работа с DB напрямую](https://github.com/pllano/json-db/blob/master/doc/db.md) или если вам не нужны (кеширование, шифрование) использовать оригинальный пакет [Lazer-Database](https://github.com/Greg0/Lazer-Database/).

### Запуск одной строчкой кода
```php
(new \jsonDB\Db(__DIR__ . '/../../_db_/'))->run();

// Или так
$_db = __DIR__ . '/../../_db_/';
$db = new Db($_db);
$db->run();
```
Запуск с параметрами
```php
use jsonDB\Db;
$_db = __DIR__ . '/../../_db_/'; // Указываем директорию где будет храниться json db

$db = new Db($_db);
$db->setPrefixTable("sf"); // Установить префикс таблиц
$db->setPrefixColumn("jhbg5r"); // Установить префикс полей
$db->setCached(false); // Включаем кеширование true|false
$db->setCacheLifetime(60); // Время жизни кеша 60 минут
$db->setTemp(false); // Используем очередь true|false
$db->setApi(false); // Если работаем как основная база устанавливаем false
$db->setStructure(""); // URL к файлу структуры db.json (Не обезательно)
$db->setPublicKey(""); // Установить public_key (Не обезательно)
$db->setCrypt(false); // Шифруем таблицы true|false
$db->setCryptKey(file_get_contents($_db . 'core/key_db.txt', true)); // Загружаем ключ шифрования
$db->run();
```
Примечание: Если вы будете пользоваться RESTful API роутингом для cURL запросов, вам не нужно выполнять запуск базы, роутер [index.php](https://github.com/pllano/json-db/blob/master/api/index.php) сделает все сам. Вам достаточно установить пакет с помощью Composer и выпонить дейсвия с настройкой API роутинга описаны выше.

#### Создать ресурс в базе данных
```php
use jsonDB\Database as jsonDb;

$arr = array(
    'id' => 'integer',
    'name' => 'string',
    'название_поля' => 'тип данных'
);

jsonDb::create('resource_name', $arr);
```	
#### Удалить ресурс в базе данных
```php
use jsonDB\Database as jsonDb;

jsonDb::remove('resource_name');
```
#### Очистить ресурс
```php
use jsonDB\Database as jsonDb;

jsonDb::table('resource_name')->delete();
```
#### Проверьте, существует ли ресурс в базе данных
```php
use jsonDB\Validate;
use jsonDB\dbException;

try{
    Validate::table('resource_name')->exists();
} catch(dbException $e){
    // Ресурс не существует
}
```
#### Создать запись
```php
use jsonDB\Database as jsonDb;

$row = jsonDb::table('resource_name');
$row->name = 'Ivan';
$row->save();
```
Примечание: Если тип поля `integer` а вы передаете число в кавычках, будет ошибка: `неверный тип данных`.
Для того чтобы избежать ошибки, добавляйте проверку и передавайте число без кавычек как в примере ниже.
```php
use jsonDB\Database as jsonDb;
 
$row = jsonDb::table('resource_name');
$row->num = $num;
$row->save();
```
#### Получить данные
```php
use jsonDB\Database as jsonDb;

echo jsonDb::table('resource_name')->where('name', '=', 'Ivan')->findAll();
// или по id
echo jsonDb::table('resource_name')->where('id', '=', '10')->findAll();
```
#### Обновить данные
```php
use jsonDB\Database as jsonDb;

$row = jsonDb::table('resource_name')->find(10);
$row->name = 'Andrey';
$row->save();
```
#### Удалить запись по id
```php
use jsonDB\Database as jsonDb;

jsonDb::table('resource_name')->find(10)->delete();
```

<a name="feedback"></a>
## Поддержка, обратная связь, новости

Общайтесь с нами через почту open.source@pllano.com

Если вы нашли баг в API json DB загляните в [issues](https://github.com/pllano/json-db/issues), возможно, про него мы уже знаем и
постараемся исправить в ближайшем будущем. Если нет, лучше всего сообщить о нём там. Там же вы можете оставлять свои пожелания и предложения.

За новостями вы можете следить по
[коммитам](https://github.com/pllano/json-db/commits/master) в этом репозитории.
[RSS](https://github.com/pllano/json-db/commits/master.atom).

Лицензия
-------

The MIT License (MIT). Please see [LICENSE](https://github.com/pllano/json-db/blob/master/LICENSE) for more information.

