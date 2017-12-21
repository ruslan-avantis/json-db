# Демонстрация работы json-db

## Для демонстрации работы мы выбрали режим `GET` запросов
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

## Демо ссылки
Переходя по ссылкам вы будете создавать, обновлять  или запрашивать данные.

### Создание
Струтктура ресурса [`user`](https://github.com/pllano/db.json/blob/master/db/user.md)

Создадим пользователя с следующими данными:
- role_id=1
- password=12345
- email=user@example.com
- phone="380671002001"
- language="ru"
- ticketed=1
- admin_access=0
- fname="Ivanova"
- iname="Anna"
- oname="Ivanovna"
- cookie="1234324325"
- created="2017-12-30 17:01"
- alias="1234324325"
- state=1
- score=1

Нажмите на ссылку: [post/user](https://pllano.eu/json-db/_post/user?role_id=1&password=12345&email='user@example.com'&phone='380671002001'&language='ru'&iname='ru'&fname='ru'&oname='ru'&alias='1234324325'&cookie='1234324325'&created='2017-12-30%2017:01'&state=1&score=1)
