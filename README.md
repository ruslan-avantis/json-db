# «jsonDB» - JSON база данных
JSON база данных с открытым исходным кодом. Написана на PHP. Распространяется по лицензии [MIT](https://opensource.org/licenses/MIT). Подключается через Composer как обычный пакет PHP, после подключения сама настраивается за несколько секунд. Имеет свой RESTful API интерфейс работающий по стандарту обмена информацией сервер-сервер «[APIS-2018](https://github.com/pllano/APIS-2018)», что позволяет использовать ее с любым другим языком программирования. «API json DB» это продвинутый менеджер json файлов с возможностью кеширования популярных запросов, шифрования файлов db, проверкой валидности файлов и очередью на запись при блокировке таблиц (файлов db) на запись другими процессами. Основанием для «API json DB» мы выбрали прекрасную работу [Greg0/Lazer-Database](https://github.com/Greg0/Lazer-Database/). Мы полностью изменили структуру оригинала и добавили: шифрование, API роутинг, кеширование, проверку валидности, очередь на запись и другой удобный функционал.

## Демонстрация [demo](https://github.com/pllano/json-db/blob/master/demo.md)

## Старт за несколько минут
Подключить пакет с помощью Composer - [Список зависимостей](https://github.com/pllano/json-db/blob/master/composer.json)
```json
"require": {
	"pllano/json-db": "^1.0.5"
}
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
- Директории: `cached` `core` `log` `request` `temp`
- В директории `core` сгенерирует файл с ключем для http запросов key_db.txt если его там еще нет.
- В директорию `core` скачает этот [db.json](https://github.com/pllano/db.json/blob/master/db.json) файл структуры если его там еще нет.
### Автоматическое создание ресурсов
База автоматически создаст все ресурсы и связи указанные в файле [db.json](https://github.com/pllano/json-db/blob/master/_db_/core/db.json). Для создания индивидуальной конфигурации ресурсов отредактируйте файл [db.json](https://github.com/pllano/json-db/blob/master/_db_/core/db.json) и перед запуском скопируйте его в директорию `/_db_/core/`.
### Поддерживаемые типы данных в db.json
- `boolean` — Логический тип `true` или `false`
- `integer` — Целое число
- `double` — Число с плавающей точкой
- `string` — Строка
- `text` — Текст (Строка в которой разрешены символы `html`)
- `datetime` — Дата (Строка с проверкой на соответствие формату: `0000-00-00 00:00`)
### Структура базы данных для интернет-магазина
Структура базы данных [db.json](https://github.com/pllano/db.json) выведена в отдельный репозиторий
### URL запросов к RESTful API jsonDB
- `https://example.com/{api_dir}/{table_name}/{id}`
- `{api_dir}` - папка в которой лежит [index.php](https://github.com/pllano/json-db/blob/master/api/index.php)
- `{resource}` - название ресурса к которому обращаемся. Например price или user.
- `{id}` - уникальный индефикатор
- `{param}` - праметры запроса
### GET запрос к RESTful API jsonDB
`?offset={offset}&limit={limit}&order={order}&sort={sort}&key={key}`
- `{key}` - Ключ доступа к RESTful API
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

$key = $config['settings']['db']['key']; // Взять key из конфигурации `https://example.com/_12345_/index.php`

$resource = 'db';
$id = '1';

// $uri = 'https://example.com/_12345_/'.$resource.'?key='.$key;
$uri = 'https://example.com/_12345_/'.$resource.'/'.$id.'?key='.$key;

$client = new Guzzle();
$response = $client->request('GET', $uri);
$output = $response->getBody();

// Чистим все что не нужно, иначе json_decode не сможет конвертировать json в массив
for ($i = 0; $i <= 31; ++$i) {$output = str_replace(chr($i), "", $output);}
$output = str_replace(chr(127), "", $output);
if (0 === strpos(bin2hex($output), 'efbbbf')) {$output = substr($output, 3);}

$records = json_decode($output, true);

if (isset($records['headers']['code'])) {
if ($records['headers']['code'] == '200') {
	$count = count($records['body']['items']);
	if ($count >= 1) {
		foreach($records['body']['items'] as $item)
		{
			print_r($item['item']);
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
```
Запуск с параметрами
```php
use jsonDB\Db;
$_db = __DIR__ . '/../../_db_/'; // Указываем директорию где будет храниться json db
$db = new Db($_db);
$db->setPrefixTable("sf"); // Установить префикс таблиц
$db->setPrefixColumn("jhbg5r"); // Установить префикс полей
$db->setCached(true); // Включаем кеширование true|false
$db->setCacheLifetime(60); // Время жижни кеша 60 минут
$db->setTemp(true); // Используем очередь true|false
$db->setApi(false); // Если работаем как основная база устанавливаем false
$db->setCrypt(true); // Шифруем таблицы true|false
$db->setKey(file_get_contents($_db . 'core/key_db.txt', true)); // Загружаем ключ шифрования
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
Примичание: Если тип поля `integer` а вы передаете число в кавычках, будет ошибка: `неверный тип данных`.
Для того чтобы избежать ошибки, добавляйте проверку и передавайте число без кавычек как в примере ниже.
```php
use jsonDB\Database as jsonDb;

$row = jsonDb::table('resource_name');
// is_numeric проверяем что значение число, а intval уберет кавычки
if (is_numeric($num)){$num = intval($num);}
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

Лицензия API json DB
-------

The MIT License (MIT). Please see [LICENSE](https://github.com/pllano/json-db/blob/master/LICENSE) for more information.

