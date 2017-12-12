# api-json-db
## json DB с открытым кодом

«API json DB» — система управления базами данных с открытым исходным кодом которая использует JSON документы и схему базы данных. Написана на PHP. Распространяется по лицензии [MIT](https://opensource.org/licenses/MIT). Подключается через Composer как обычный пакет PHP, после подключения сама настраивается за несколько секунд. Имеет свой RESTful API интерфейс, что позволяет использовать ее с любым другим языком программирования. Фактически это менеджер json файлов с возможностью кеширования популярных запросов, проверкой валидности файлов и очередью на запись при блокировке таблиц (файлов db) на запись другими процессами. 

Главная задача «API json DB» обеспечить быструю отдачу контента (кэширование) и бесперебойную работу сайта при сбоях или недоступности API от которой она получает данные.

Ядром для «API json DB» мы выбрали прекрасную работу [Greg0/Lazer-Database](https://github.com/Greg0/Lazer-Database/). 
В связи с тем что нам необходимо добавлять новые файлы и вносить изменение в файлы мы не используем оригинальный пакет.

## Сферы применения
- Хранилище для кеша если `"cached": "true"`
- Хранилище для логов или других данных с разбивкой на файлы с лимитом записей или размером файла.
- Основная база данных с таблицами до 1 млн. записей. `"api": "false"`
- Автоматически подключение когда API недоступен. Сайт продолжает работать и пишет все `POST`, `PUT`, `DELETE` запросы в request для последующей синхронизации с API. Когда API снова станет доступный база сначала синхронизирует все данные пока не обработает и не удалит все файлы из папки request и после этого переключится на API

## Старт
Подключить пакет с помощью Composer

```json
"require": {
	"pllano/api-json-db": "*"
}
```

Сразу после установки пакета у вас есть json база данных и вы можете уже создавать таблицы и работать с db

### Прямое подключение к DB без RESTful API

Если вам не нужен API роутинг Вы можете работать с базой данных напрямую без REST API интерфейса - [Документация](https://github.com/pllano/api-json-db/blob/master/db.md) или использовать оригинальный пакет [Lazer-Database](https://github.com/Greg0/Lazer-Database/).

## RESTful API роутинг для cURL запросов

«API json DB» имеет свой RESTfull API роутинг для cURL запросов который написан на PHP с использованием Micro Framework [Slim](https://github.com/slimphp), что позволяет использовать «API json DB» с любым другим языком программирования. Для унификации обмена данными сервер-сервер и клиент-сервер используется стандарт [APIS-2018](https://github.com/pllano/APIS-2018/). `Стандарт APIS-2018 - не является общепринятым` и является нашим взглядом в будущее и рекомендацией для унификации построения легких движков интернет-магазинов нового поколения.

## RESTfull API состоит из двух файлов:
- [index.php](https://github.com/pllano/api-json-db/blob/master/index.php) - Основной файл
- [.htaccess](https://github.com/pllano/api-json-db/blob/master/.htaccess)


Если вы хотите использовать `RESTful API роутинг` выполните следующие действия:

- В файле [index.php](https://github.com/pllano/api-json-db/blob/master/index.php) указать директорию где будет находится база желательно ниже корневой директории вашего сайта. (Пример: ваш сайт находится в папке `/www/example.com/public/` разместите базу в `/www/_db_/` таким образом к ней будет доступ только у скриптов). 

- Перенесите файлы [index.php](https://github.com/pllano/api-json-db/blob/master/index.php) и [.htaccess](https://github.com/pllano/api-json-db/blob/master/.htaccess) в директорию к которой будет доступ через url (Пример: `https://example.com/_12345_/` название директории должно быть максимально сложным)

- Запустите `https://example.com/_12345_/`		
- Вы увидите следующий результат если все хорошо 
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

## Создание таблиц

По умолчанию при первом запуске `api-json-db` автоматически создает таблицы по стандарту `APIS-2018`, таким образом у вас есть полностью работоспособная база данных. Стуктура и взаимосвязи таблиц изменяются в файле [db.json](https://github.com/pllano/api-json-db/blob/master/_db_/core/db.json)

Вы можете создавать таблицы автоматически с использованием файла `/www/_db_/core/db.json` отредактируйте [db.json](https://github.com/pllano/api-json-db/blob/master/_db_/core/db.json) замените им `/www/_db_/core/db.json`

При запуске база проверяет файл `/www/_db_/core/db.json` и создает все таблицы которых еще не существует.

### Вы можете создавать свою конфигурацию DB для этого:
- Отредактируйте [db.json](https://github.com/pllano/api-json-db/blob/master/_db_/core/db.json)
- Удалите лишние таблицы в `/www/_db_/`
