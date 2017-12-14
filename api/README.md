# RESTful API роутинг для cURL запросов
«API json DB» имеет свой RESTfull API роутинг для cURL запросов который написан на PHP с использованием [Micro Framework Slim](https://github.com/slimphp), что позволяет использовать «API json DB» с любым другим языком программирования. Для унификации обмена данными и структуры базы данных используется наш собственный стандарт [APIS-2018](https://github.com/pllano/APIS-2018/).
## RESTfull API состоит всего из двух файлов:
- [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) и [.htaccess](https://github.com/pllano/api-json-db/blob/master/api/.htaccess)
## Для установки `RESTful API` выполните следующие действия:
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
## Автоматическое разворачиваение
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
### Инструменты для тестирования API
- [Postman](https://www.getpostman.com/postman)
- [SOAPUI](https://www.soapui.org/rest-testing/getting-started.html)
### Инструменты для работы с API
- Клиентская библиотека PHP работы с URL [cURL](http://php.net/manual/ru/book.curl.php)
- HTTP-клиент PHP [Guzzle](https://github.com/guzzle/guzzle)
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
