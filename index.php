<?php
if (PHP_SAPI == 'cli-server') {
  $url  = parse_url($_SERVER['REQUEST_URI']); 
  $file = __DIR__ . $url['path']; 
  if (is_file($file)) {return false;}
}

// Composer
require __DIR__ . '/../../vendor/autoload.php';

// Локальное подключение
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/dbException.php';
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/Database.php';
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/Relation.php';
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/FileInterface.php';
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/File.php';
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/Validate.php';
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/Data.php';
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/Config.php';
// require __DIR__ . '/../../vendor/pllano/api-json-db/src/Run.php';

// !!! Указываем директорию где будет храниться json db !!!
// !!! На уровень ниже корневой директории сайта !!!
$_db = __DIR__ . '/../../_db_/';

if (!file_exists($_db . 'core/key_db.txt')){
$ajax_key = \Defuse\Crypto\Key::createNewRandomKey();
$key_db = $ajax_key->saveToAsciiSafeString();
file_put_contents($_db . 'core/key_db.txt', $key_db);
}

// Конфигурация
$config = array();
$config['settings']['db']['dir'] = $_db;
$config['settings']['db']['key_cryp'] = \Defuse\Crypto\Key::loadFromAsciiSafeString(file_get_contents($_db . 'core/key_db.txt', true));
$config['settings']['db']['key'] = file_get_contents($_db . 'core/key_db.txt', true);
$config['settings']['db']['access_key'] = false;
$config['settings']['displayErrorDetails'] = true;
$config['settings']['addContentLengthHeader'] = false;
$config['settings']['determineRouteBeforeAppMiddleware'] = true;
$config['settings']['debug'] = true;

// Подключаем Slim
$app = new \Slim\App($config);

// Запускаем json db
$db = new \jsonDB\Db($_db);
$db->setCached(true);
$db->setCacheLifetime(5);
$db->setTemp(true);
$db->setApi(true);
$db->run();

// Подключаем роутер
require __DIR__ . '/router.php';

$container = $app->getContainer();

$container['logger'] = function ($logger) {
$logger = new Monolog\Logger("db_json_api");
$logger->pushProcessor(new Monolog\Processor\UidProcessor());
$logger->pushHandler(new Monolog\Handler\StreamHandler(isset($_ENV['docker']) ? 'php://stdout' : $_db_json . 'log/_monolog/app.log', \Monolog\Logger::DEBUG));
return $logger;
};

// Запускаем Slim
$app->run();

