Использование jsonDb без RESTful API интерфейса
=============

Требования
-------
- PHP 5.4+
- Composer

Установка
-------
Самый простой способ установить «jsonDb» через Composer. Конечно, вы можете использовать свой автозагрузчик, но вы должны настроить его самостоятельно. Вы можете найти пакет установки на [Packagist.org](https://packagist.org/packages/pllano/api-json-db).

Добавьте в свой файл `composer.json`

```json
"require": {
	"pllano/api-json-db": "*"
}
```
Быстрый старт
------
```php

// !!! Указываем директорию где будет храниться json db !!!
// !!! На уровень ниже корневой директории сайта !!!
$_db = __DIR__ . '/../../_db_/';

// Запускаем jsonDB
$db = new \jsonDB\Db($_db);
$db->run();
```
или
```php
$db = new \jsonDB\Db($_db);
$db->setCached(true); // Включить кеширование
$db->setCacheLifetime(5); // Продолжительность жизни кеша в мин.
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

### Структура файлов таблиц базы данных состоит из двух файлов:
`table_name.data.json` - файл таблицы с данными   
`table_name.config.json` - файл таблицы с конфигурацией

### Методы

##### Цепные методы

- `limit()` - установить лимит. Должен использоваться непосредственно перед окончанием метода `find_all()`.
- `orderBy()` - сортировка строк.
- `groupBy()` - группировать строки по полю.
- `where()` - фильтровать записи. Alias: `and_where()`.
- `orWhere()` - фильтровать с OR.
- `with()` - получить данные с других связанных таблиц

##### Конечные методы

- `addFields()` - добавить новые поля в существующую таблицу
- `deleteFields()` - удаление полей из существующей таблицы
- `save()` - вставить или обновить данные.
- `delete()` - удаление данных.
- `relations()` - возвращает массив с связями таблицы
- `config()` - возвращает объект с конфигурацией.
- `fields()` - возвращает массив с именами полей.
- `schema()` - возвращает ассоциативный массив с именами и типом полей `field => type`.
- `lastId()` - возвращает последний идентификатор из таблицы.
- `find()` - возвращает одну строку с указанным идентификатором.
- `findAll()` - возвращает все найденные по параметрам или все строки если параметры не указанны.
- `asArray()` - возвращает данные как индексированные или ассоциированные массивы: `['field_name' => 'field_name']`. Должен использоваться после окончания метода `find_all()` или `find()`.
- `count()` - возвращает количество строк. Должен использоваться после окончания метода `find_all()` или `find()`.

### Select (Выборки)

#### Multiple select (Мульти выборки)
```php
$table = jsonDb::table('table_name')->findAll();
    
foreach($table as $row)
{
    print_r($row);
}
```
#### Single record select
```php
$row = jsonDb::table('table_name')->find(1);

echo $row->id;
```
Type ID of row in `find()` method.

You also can do something like that to get first matching record:
```php
$row = jsonDb::table('table_name')->where('name', '=', 'John')->find();

echo $row->id;
```

### Insert
```php
$row = jsonDb::table('table_name');

$row->nickname = 'new_user';
$row->save();
```
Do not set the ID.

### Update

It's very smilar to `Inserting`.
```php
$row = jsonDb::table('table_name')->find(1); //Edit row with ID 1

$row->nickname = 'edited_user';

$row->save();
```
### Remove

#### Single record deleting
```php
jsonDb::table('table_name')->find(1)->delete(); //Will remove row with ID 1

// OR

jsonDb::table('table_name')->where('name', '=', 'John')->find()->delete(); //Will remove John from DB

```
#### Multiple records deleting
```php
jsonDb::table('table_name')->where('nickname', '=', 'edited_user')->delete();
```
#### Clear table
```php
jsonDb::table('table_name')->delete();
```
### Relations

To work with relations use class Relation
```php
use jsonDb\Classes\Relation; // example
```

#### Relation types

- `belongsTo` - relation many to one
- `hasMany` - relation one to many
- `hasAndBelongsToMany` - relation many to many

#### Methods

##### Chain methods

- `belongsTo()` - set relation belongsTo
- `hasMany()` - set relation hasMany
- `hasAndBelongsToMany()` - set relation hasAndBelongsToMany
- `localKey()` - set relation local key
- `foreignKey()` - set relation foreign key
- `with()` - allow to work on existing relation

##### Ending methods

- `setRelation()` - creating specified relation
- `removeRelation()` - creating specified relation
- `getRelation()` - return informations about relation
- `getJunction()` - return name of junction table in `hasAndBelongsToMany` relation

#### Create relation
```php
Relation::table('table1')->belongsTo('table2')->localKey('table2_id')->foreignKey('id')->setRelation();
Relation::table('table2')->hasMany('table1')->localKey('id')->foreignKey('table2_id')->setRelation();
Relation::table('table2')->hasAndBelongsToMany('table1')->localKey('id')->foreignKey('id')->setRelation(); // Junction table will be crete automaticly
```

#### Remove relation
```php
Relation::table('table1')->with('table2')->removeRelation();
```
#### Get relation information
You can do it by two ways. Use Standard Database class or Relation but results will be different.
```php
Relation::table('table1')->with('table2')->getRelation(); // relation with specified table
jsonDb::table('table1')->relations(); // all relations
jsonDb::table('table1')->relations('table2'); // relation with specified table
```
