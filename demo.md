# Демонстрация работы json-db
Для удобства мы отключили авторизацию через `public_key`

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

### Демо база данных [`https://xti.com.ua/json-db/`](https://xti.com.ua/json-db/)

Переходя по ссылкам вы будете создавать, обновлять или запрашивать данные

Заказ с `order_id` = 4

https://xti.com.ua/json-db/order/4

Удалить заказ с `order_id` = 8

https://xti.com.ua/json-db/_delete/order/8

Список первых 5 заказов с данными из других ресурсов заданные через параметр [`relation`](https://github.com/pllano/APIS-2018/blob/master/structure/relations.md)

https://xti.com.ua/json-db/order?relation=cart,user:phone:email:fname:iname,address&limit=5&offset=0

Первые два заказа пользователя `user_id` = 2

https://xti.com.ua/json-db/order?user_id=2&limit=2&offset=0

Список всех пользователей

https://xti.com.ua/json-db/user

Список пользователей с именем Admin 

https://xti.com.ua/json-db/user?iname=Admin

### json-db потдерживает стандарт [APIS-2018](https://github.com/pllano/APIS-2018)
Все поддерживаемые json-db типы запросов и ресурсов вы можете найти в документации [APIS-2018](https://github.com/pllano/APIS-2018)

