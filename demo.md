# Демонстрация работы json-db

### Для демонстрации работы мы выбрали режим `GET` запросов
- `GET /_post/{resource}?{param}` Создание записи
- `GET /_post/{resource}/{id}` Ошибка
- `GET /_put/{resource}?{param}` Обновить данные записей
- `GET /_put/{resource}/{id}?{param}` Обновить данные конкретной записи
- `GET /_delete/{resource}` Удалить все записи
- `GET /_delete/{resource}/{id}` Удалить конкретную запись
### Обычные `GET` запросы вы можете пускать по стандартному пути
- `GET /{resource}?{param}` Список всех записей с фильтром по параметрам
- `GET /{resource}/{id}` Данные конкретной записи

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

### json-db потдерживает стандарт [APIS-2018](https://github.com/pllano/APIS-2018)
Все поддерживаемые json-db типы запросов и ресурсов вы можете найти в документации [APIS-2018](https://github.com/pllano/APIS-2018)

