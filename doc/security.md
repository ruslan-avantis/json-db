# Безопасность

## Используйте ключ доступа

При запуске база автоматически создаст файл с ключом доступа который находится в директории: `/www/_db_/core/key_db.txt`

Активация доступа по ключу устанавливается в файле [index.php](https://github.com/pllano/api-json-db/blob/master/api/index.php)

```php
$config['settings']['db']['access_key'] = true;
```

Если установлен флаг `true` - тогда во всех запросах к db необходимо передавать параметр `key`

`curl https://example.com/_12345_/table_name?key=key`

`curl --request POST "https://example.com/_12345_/table_name" --data "key=key"`

При запросе без ключа API будет отдавать ответ

```json
{
    "status": "403",
    "code": 403,
    "message": "Access is denied"
}
```

## Ограничить доступ в .htaccess

Вы также можете разрешить доступ к API DB только для своих IP с помощью [.htaccess](https://github.com/pllano/api-json-db/blob/master/api/.htaccess)

ВАЖНО !!! Не попутайте с основным `https://example.com/.htaccess` который запретит доступ ко всему сайту

В файле `https://example.com/_12345_/.htaccess` добавить

```
Order Deny,Allow
Deny from all
Allow from 192.168.1.1
```

## Используйте шифрование

```php
$db = new jsonDB\Db(__DIR__ . '/../../_db_/');
$db->setCrypt(true); // Шифруем таблицы true|false
$db->setKey(file_get_contents($_db . 'core/key_db.txt', true)); // Загружаем ключ шифрования
$db->run();
```
