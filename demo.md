# Демонстрация работы json-db

### Для демонстрации работы мы выбрали режим `GET` запросов
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

### Демо база данных `https://pllano.eu/json-db/`

Переходя по ссылкам вы будете создавать, обновлять  или запрашивать данные.

Для демонстрации мы выбрали ресурс `user` - струтктура ресурса [`user`](https://github.com/pllano/db.json/blob/master/db/user.md)

Создадим пользователя с следующими данными

`role_id=1` `password=12345` `email=user@example.com` `phone="380671002001"` `language="ru"` `ticketed=1` `admin_access=0` `fname="Ivanova"` `iname="Anna"` `oname="Ivanovna"` `cookie="1234324325"` `created="2017-12-30 17:01"` `alias="1234324325"`

Нажмите на эту ссылку: https://pllano.eu/json-db/_post/user?role_id=1&password=12345&email="user@example.com"&phone="380671002001"&language="ru"&iname="Anna"&fname="Ivanova"&oname="Ivanovna"&alias="1234324325"&cookie="1234324325"&created="2017-12-30%2017:01"

```json
{
    "headers": {
        "status": "201 Created",
        "code": 201,
        "message": "Created",
        "message_id": "https:\/\/github.com\/pllano\/APIS-2018\/tree\/master\/http-codes\/201.md"
    },
    "response": {
        "id": "В ответе будет id созданного пользователя"
    },
    "request": {
        "query": "POST",
        "table": "user"
    }
}
```

Список всех пользователей 

https://pllano.eu/json-db/user

Список пользователей с именем Anna 

https://pllano.eu/json-db/user?iname=Anna

Первые два пользователя с именем Anna 

https://pllano.eu/json-db/user?iname=Anna&limit=2&offset=0
