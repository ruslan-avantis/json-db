# RESTful API роутинг для cURL запросов
«API json DB» имеет свой RESTfull API роутинг для cURL запросов который написан на PHP с использованием [Micro Framework Slim](https://github.com/slimphp), что позволяет использовать «API json DB» с любым другим языком программирования. Для унификации обмена данными и структуры базы данных используется наш собственный стандарт [APIS-2018](https://github.com/pllano/APIS-2018/).
## RESTfull API состоит всего из двух файлов:
- [index.php](https://github.com/pllano/json-db/blob/master/api/index.php) и [.htaccess](https://github.com/pllano/json-db/blob/master/api/.htaccess)
## Для установки `RESTful API` выполните следующие действия:
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
## Автоматическое разворачиваение
При запуске база создаст в папке `_db_` которую вы указали:   
- Ресурс для своей конфигурации `db.data.json` и `db.config.json`   
- Ресурс для кеша `cached.data.json` и `cached.config.json`
- Директории: `cached` `core` `log` `request` `temp`
- В директории `core` сгенерирует файл с ключем для http запросов key_db.txt если его там еще нет.
- В директорию `core` скачает этот [db.json](https://github.com/pllano/db.json/blob/master/db.json) файл структуры если его там нет.
### Автоматическое создание ресурсов
База автоматически создаст все ресурсы и связи указанные в файле [db.json](https://github.com/pllano/db.json/blob/master/db.json). Для создания индивидуальной конфигурации таблиц отредактируйте файл [db.json](https://github.com/pllano/db.json/blob/master/db.json) и перед запуском скопируйте его в директорию `/_db_/core/`.
### Поддерживаемые типы данных в DB
- `boolean` — Логический тип `true` или `false`
- `integer` — Целое число	
- `string` — Строковый тип
- `double` — Число с плавающей точкой
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
### Вы можете отправлять только `GET` запросы:
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
### Инструменты для тестирования API
- [Postman](https://www.getpostman.com/postman) - это мощный набор инструментов тестирования API
- [SOAPUI](https://www.soapui.org/rest-testing/getting-started.html) - приложение для тестирования, мониторинга и проверки функциональности REST API.
### Инструменты для работы с API
- [cURL](http://php.net/manual/ru/book.curl.php) - Клиентская библиотека PHP работы с URL
- [Guzzle](https://github.com/guzzle/guzzle) - HTTP-клиент PHP
### Пример использования с HTTP клиентом Guzzle
``` php	
use GuzzleHttp\Client as Guzzle;

// Взять public_key из конфигурации в файле `https://example.com/_12345_/index.php`
$public_key = $config['settings']['db']['public_key'];
// Название ресурса
$resource = 'db';
// id записи
$id = '1';

// Формируем URL запроса
// $uri = 'https://example.com/_12345_/'.$resource.'?public_key='.$public_key;
$uri = 'https://example.com/_12345_/'.$resource.'/'.$id.'?public_key='.$public_key;

// Подключаем Guzzle
$guzzle = new Guzzle();
// Отправляем запрос
$response = $guzzle->request('GET', $uri);
// Получаем тело ответа
$output = $response->getBody();
// json в массив
$records = json_decode($output, true);

// Работаем с массивом
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
``` php
// Вывести на экран json
print_r($records);
```
### RESTful API jsonDB - Всегда возвращает код 200 даже при логических ошибках !

`HTTP/1.1 200 OK`

`Content-Type: application/json`

### В теле ответа RESTful API jsonDB вернет код состояния HTTP, статус и описание.

[Коды состояния HTTP](https://github.com/pllano/APIS-2018/tree/master/http-codes)

## Безопасность
[Советы по увеличению безопасности API json DB](https://github.com/pllano/json-db/blob/master/doc/security.md)
