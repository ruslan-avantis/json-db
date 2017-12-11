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

### Проверьте, существует ли таблица в базе данных
```php
try{
    \jsonDb\Validate::table('table_name')->exists();
} catch(\jsonDb\jsonDbException $e){
    // Таблица не существует
}
```
## RESTful API роутинг для cURL запросов
«API json DB» имеет свой RESTfull API роутинг для cURL запросов который написан на PHP с использованием Micro Framework [Slim](https://github.com/slimphp), что позволяет использовать «API json DB» с любым другим языком программирования. Для унификации обмена данными сервер-сервер и клиент-сервер используется стандарт [APIS-2018](https://github.com/pllano/APIS-2018/). `Стандарт APIS-2018 - не является общепринятым` и является нашим взглядом в будущее и рекомендацией для унификации построения легких движков интернет-магазинов нового поколения.
### RESTfull API состоит из двух файлов:
- [index.php]https://github.com/pllano/api-json-db/blob/master/api/index.php) - RESTfull API
- [.htaccess](https://github.com/pllano/api-json-db/blob/master/api/.htaccess)

Если вы хотите использовать `RESTful API роутинг` выполните следующие действия:

- В файле [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php) укажите директорию где будет хранится база, желательно ниже корневой директории вашего сайта, например `/www/_db_/`.

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

