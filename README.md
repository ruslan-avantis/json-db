# «API json DB»

Система управления базами данных с открытым исходным кодом которая использует JSON документы и схему базы данных. Написана на PHP. Распространяется по лицензии [MIT](https://opensource.org/licenses/MIT). Подключается через Composer как обычный пакет PHP, после подключения сама настраивается за несколько секунд. Имеет свой RESTful API интерфейс, что позволяет использовать ее с любым другим языком программирования. Фактически это менеджер json файлов с возможностью кеширования популярных запросов, проверкой валидности файлов и очередью на запись при блокировке таблиц (файлов db) на запись другими процессами. 

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

Автоматическое разворачиваение
-------
При первом запуске в папке `_db_` которую вы указали база создаст:   
Таблицу своей конфигурации `db.data.json` и `db.config.json`   
Таблицу для кеша `cached.data.json` и `cached.config.json`  

### Автоматическое создание таблиц базы данных
Для автоматического создания всех необходимых таблиц отредактируйте файл `/_db_/core/db.json`	 

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

### Прямое подключение к DB без RESTful API

Если вам не нужен API роутинг Вы можете работать с базой данных напрямую без REST API интерфейса - [Документация](https://github.com/pllano/api-json-db/blob/master/db.md) или использовать оригинальный пакет [Lazer-Database](https://github.com/Greg0/Lazer-Database/).

## RESTful API роутинг для cURL запросов

«API json DB» имеет свой RESTfull API роутинг для cURL запросов который написан на PHP с использованием Micro Framework [Slim](https://github.com/slimphp), что позволяет использовать «API json DB» с любым другим языком программирования. Для унификации обмена данными сервер-сервер и клиент-сервер используется стандарт [APIS-2018](https://github.com/pllano/APIS-2018/). `Стандарт APIS-2018 - не является общепринятым` и является нашим взглядом в будущее и рекомендацией для унификации построения легких движков интернет-магазинов нового поколения.

### RESTfull API состоит из двух файлов:
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

