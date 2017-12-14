# «API json DB» - JSON база данных
JSON база данных с открытым исходным кодом. Написана на PHP. Распространяется по лицензии [MIT](https://opensource.org/licenses/MIT). Подключается через Composer как обычный пакет PHP, после подключения сама настраивается за несколько секунд. Имеет свой RESTful API интерфейс работающий по стандарту обмена информацие «APIS-2018», что позволяет использовать ее с любым другим языком программирования. «API json DB» это продвинутый менеджер json файлов с возможностью кеширования популярных запросов, шифрования файлов db, проверкой валидности файлов и очередью на запись при блокировке таблиц (файлов db) на запись другими процессами. Основанием для «API json DB» мы выбрали прекрасную работу [Greg0/Lazer-Database](https://github.com/Greg0/Lazer-Database/). Мы полностью изменили структуру оригинала и добавили: шифрование, API роутинг, кеширование, проверку валидности, очередь на запись и другой удобный функционал.
## Старт за несколько минут
Подключить пакет с помощью Composer - [Список зависимостей](https://github.com/pllano/api-json-db/blob/master/composer.json)
```json
"require": {
	"pllano/api-json-db": "^1.0"
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
- [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) и [.htaccess](https://github.com/pllano/api-json-db/blob/master/api/.htaccess)
### Для установки `RESTful API` выполните следующие действия:
- В файле [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) укажите директорию где хранится база, например `/www/_db_/` или `__DIR__ . '/../../_db_/'`.
- Перенесите файлы [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) и [.htaccess](https://github.com/pllano/api-json-db/blob/master/api/.htaccess) в директорию доступную через URL. Например: `https://example.com/_12345_/`
- Запустите API перейдя по ссылке `https://example.com/_12345_/`		
- Если база работает Вы увидите следующий результат:
```json
{
    "headers": {
        "status": "200 OK",
        "code": 200,
        "message": "RESTfull API json DB works!",
        "message_id": "https:\/\/github.com\/pllano\/api-json-db\/blob\/master\/doc\/http-codes\/200.md"
    }
}
```
### Автоматическое разворачиваение
При запуске база создаст в папке `_db_` которую вы указали:   
- Таблицу своей конфигурации `db.data.json` и `db.config.json`   
- Таблицу для кеша `cached.data.json` и `cached.config.json`
- Директории: `cached` `core` `log` `request` `temp`
- В директории `core` сгенерирует файл с ключем для http запросов key_db.txt если его там еще нет.
- В директорию `core` скачает этот [db.json](https://github.com/pllano/api-json-db/blob/master/_db_/core/db.json) файл структуры если его там нет.
### Автоматическое создание таблиц
База автоматически создаст все таблицы и взаимосвязи указанные в файле [db.json](https://github.com/pllano/api-json-db/blob/master/_db_/core/db.json). Для создания индивидуальной конфигурации таблиц отредактируйте файл [db.json](https://github.com/pllano/api-json-db/blob/master/_db_/core/db.json) и перед запуском скопируйте его в директорию `/_db_/core/`.
### Поддерживаемые типы данных в DB
- `boolean` — Логический тип `true` или `false`
- `integer` — Целое число	
- `string` — Строковый тип
- `double` — Число с плавающей точкой
### URL запросов к RESTful API jsonDB
- `https://example.com/{api_dir}/{table_name}/{id}`
- `{api_dir}` - папка в которой лежит [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php)
- `{table_name}` - название таблицы к которой обращаемся. Например price или user.
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
- `POST /{table_name}` Создание записи 
- `POST /{table_name}/{id}` Ошибка
- `GET /{table_name}` Список всех записей
- `GET /{table_name}?{param}` Список всех записей с фильтром по параметрам
- `GET /{table_name}/{id}` Данные конкретной записи
- `PUT /{table_name}` Обновить данные записей
- `PUT /{table_name}/{id}` Обновить данные конкретной записи
- `PATCH /{table_name}` Обновить данные записей
- `PATCH /{table_name}/{id}` Обновить данные конкретной записи
- `DELETE /{table_name}` Удалить все записи
- `DELETE /{table_name}/{id}` Удалить конкретную запись
### Вы можете отправлять только `GET` запросы:
- `GET /_post/{table_name}?{param}` Создание записи 
- `GET /_post/{table_name}/{id}` Ошибка
- `GET /_get/{table_name}?{param}` Список всех записей с фильтром по параметрам
- `GET /_get/{table_name}/{id}` Данные конкретной записи
- `GET /_put/{table_name}?{param}` Обновить данные записей
- `GET /_put/{table_name}/{id}?{param}` Обновить данные конкретной записи
- `GET /_patch/{table_name}?{param}` Обновить данные записей
- `GET /_patch/{table_name}/{id}?{param}` Обновить данные конкретной записи
- `GET /_delete/{table_name}` Удалить все записи
- `GET /_delete/{table_name}/{id}` Удалить конкретную запись
### Пример использования с HTTP клиентом Guzzle
``` php	
$key = $config['settings']['db']['key']; // Взять key из конфигурации `https://example.com/_12345_/index.php`

$table_name = 'db';
$id = '1';

// $uri = 'https://example.com/_12345_/api.php?key='.$key;
// $uri = 'https://example.com/_12345_/'.$table_name.'?key='.$key;
$uri = 'https://example.com/_12345_/'.$table_name.'/'.$id.'?key='.$key;

$client = new \GuzzleHttp\Client();
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

[Коды состояния HTTP](https://github.com/pllano/api-json-db/tree/master/doc/http-codes)

## Безопасность
[Советы по увеличению безопасности API json DB](https://github.com/pllano/api-json-db/blob/master/doc/security.md)

## Прямое подключение к DB
Если вам не нужен API роутинг Вы можете работать с базой данных напрямую без REST API интерфейса - [Документация - работа с DB напрямую](https://github.com/pllano/api-json-db/blob/master/doc/db.md) или если вам не нужны (кеширование, шифрование) использовать оригинальный пакет [Lazer-Database](https://github.com/Greg0/Lazer-Database/).

### Запуск одной строчкой кода
```php
(new \jsonDB\Db(__DIR__ . '/../../_db_/'))->run();
```
Запуск с параметрами
```php
use jsonDB\Db;
$_db = __DIR__ . '/../../_db_/'; // Указываем директорию где будет храниться json db
$db = new Db($_db);
$db->setCached(true); // Включаем кеширование true|false
$db->setCacheLifetime(60); // Время жижни кеша 60 минут
$db->setTemp(true); // Используем очередь true|false
$db->setApi(false); // Если работаем как основная база устанавливаем false
$db->setCrypt(true); // Шифруем таблицы true|false
$db->setKey(file_get_contents($_db . 'core/key_db.txt', true)); // Загружаем ключ шифрования
$db->run();
```
Примечание: Если вы будете пользоваться RESTful API роутингом для cURL запросов, вам не нужно выполнять запуск базы, роутер [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) сделает все сам. Вам достаточно установить пакет с помощью Composer и выпонить дейсвия с настройкой API роутинга описаны выше.

#### Создать таблицу в базе данных
```php
use jsonDB\Database as jsonDb;

$arr = array(
    'id' => 'integer',
    'name' => 'string',
    'название_поля' => 'тип данных'
);

jsonDb::create('table_name', $arr);
```	
#### Удалить таблицу в базе данных
```php
use jsonDB\Database as jsonDb;

jsonDb::remove('table_name');
```
#### Очистить таблицу
```php
use jsonDB\Database as jsonDb;

jsonDb::table('table_name')->delete();
```
#### Проверьте, существует ли таблица в базе данных
```php
use jsonDB\Validate;
use jsonDB\dbException;

try{
    Validate::table('table_name')->exists();
} catch(dbException $e){
    // Таблица не существует
}
```
#### Создать запись в таблице
```php
use jsonDB\Database as jsonDb;

$row = jsonDb::table('table_name');
$row->name = 'Ivan';
$row->save();
```
#### Получить данные с таблицы
```php
use jsonDB\Database as jsonDb;

echo jsonDb::table('table_name')->where('name', '=', 'Ivan')->findAll();
// или по id
echo jsonDb::table('table_name')->where('id', '=', '10')->findAll();
```
#### Обновить данные
```php
use jsonDB\Database as jsonDb;

$row = jsonDb::table('table_name')->find(10);
$row->name = 'Andrey';
$row->save();
```
#### Удалить запись по id
```php
use jsonDB\Database as jsonDb;

jsonDb::table('table_name')->find(10)->delete();
```

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

The MIT License (MIT). Please see [LICENSE](https://github.com/pllano/api-json-db/blob/master/LICENSE) for more information.

