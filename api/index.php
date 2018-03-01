<?php 
 
if (PHP_SAPI == 'cli-server') {
$url  = parse_url($_SERVER['REQUEST_URI']);
$file = __DIR__ . $url['path'];
if (is_file($file)) {return false;}
}
 
// !!! Указываем директорию где будет храниться json db !!!
$_db = __DIR__ . '/_db_/';
 
// Composer
require __DIR__ . '/../../vendor/autoload.php';
     
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
 
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Handler\StreamHandler;
 
use Defuse\Crypto\Key;
use Flow\JSONPath\JSONPath;
 
use jsonDB\Db;
use jsonDB\Database as jsonDb;
use jsonDB\Validate;
use jsonDB\dbException;
 
// Проверяем папки DB, если нет создаем
if (!file_exists($_db)){mkdir($_db);}
if (!file_exists($_db . 'core')){mkdir($_db . 'core');}

// Создаем ключ доступа
if (!file_exists($_db . 'core/key_db.txt')){
    $ajax_key = Key::createNewRandomKey();
    $key_db = $ajax_key->saveToAsciiSafeString();
    file_put_contents($_db . 'core/key_db.txt', $key_db);
}

// Конфигурация
$config = [];
$config['settings']['db']['dir'] = $_db;
$config['settings']['db']['key_cryp'] = Key::loadFromAsciiSafeString(file_get_contents($_db . 'core/key_db.txt', true));
$config['settings']['db']['public_key'] = file_get_contents($_db . 'core/key_db.txt', true);
$config['settings']['db']['access_key'] = false;
$config['settings']['determineRouteBeforeAppMiddleware'] = true;
$config['settings']['displayErrorDetails'] = true;
$config['settings']['addContentLengthHeader'] = true;
$config['settings']['debug'] = true;
$config['settings']['http-codes'] = "https://github.com/pllano/APIS-2018/tree/master/http-codes/";

// Запускаем json db
$db = new Db($_db);
$db->setCached(false);
$db->setCacheLifetime(60);
$db->setTemp(false);
$db->setApi(false);
$db->setCrypt(false);
$db->setKey($config['settings']['db']['public_key']);
$db->run();

// Подключаем Slim
$app = new App($config);

$container = $app->getContainer();

$container['logger'] = function ($logger) {
    $logger = new Logger("db_json_api");
    $logger->pushProcessor(new UidProcessor());
    $logger->pushHandler(new StreamHandler(isset($_ENV['docker']) ? 'php://stdout' : $_db . 'log/_monolog/app.log', Logger::DEBUG));
    return $logger;
};

$app->get('/', function (Request $request, Response $response, array $args) {
    $param = $request->getQueryParams();
    $public_key = (isset($param['public_key'])) ? Db::clean($param['public_key']) : null;
    if ($public_key == $this->get('settings')['db']["public_key"]) {
        $resp["headers"]["status"] = "200 OK";
        $resp["headers"]["code"] = 200;
        $resp["headers"]["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["code"].".md";
        echo json_encode($resp, JSON_PRETTY_PRINT);
    } else {
        $resp["headers"]["status"] = "200 OK";
        $resp["headers"]["code"] = 200;
        $resp["headers"]["message"] = "RESTfull API json DB works!";
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        echo json_encode($resp, JSON_PRETTY_PRINT);
    }
    return $response->withStatus(200)->withHeader('Content-Type','application/json');
});

$app->get('/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {
    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $getParams = $request->getQueryParams();
    $getUri = $request->getUri();
    // Если авторизация по ключу
    if ($this->get('settings')['db']["access_key"] == true){
        $public_key = (isset($getParams['public_key'])) ? Db::clean($getParams['public_key']) : "none";
    } else {
        $public_key = $this->get('settings')['db']["public_key"];
    }
    if ($this->get('settings')['db']["public_key"] == $public_key) {
        if (isset($resource)) {
            // Проверяем наличие главной базы
            try {Validate::table($resource)->exists();
                // Конфигурация таблицы
                $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);
                // Парсим URL
                if (parse_url($getUri, PHP_URL_QUERY)) {$url_query = '?'.parse_url($getUri, PHP_URL_QUERY);} else {$url_query = '';}
                $url_path = parse_url($getUri, PHP_URL_PATH);
                // Формируем url для работы с кешем
                $cacheUri = $url_path.''.$url_query;
                // Читаем данные в кеше
                $cacheReader = Db::cacheReader($cacheUri);
                // Если кеш отдал null, формируем запрос к базе
                if ($cacheReader == null) {
                    // Если указан id
                    if ($id >= 1) {
                        $res = jsonDb::table($resource)->where('id', '=', $id)->findAll();
                        
                        $resCount = count($res);
                        if ($resCount == 1) {
                            
                            $resp["headers"]["status"] = "200 OK";
                            $resp["headers"]["code"] = 200;
                            $resp["headers"]["message"] = "OK";
                            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                            $resp["response"]["source"] = "db";
                            $resp["response"]["total"] = $resCount;
                            $resp["request"]["query"] = "GET";
                            $resp["request"]["resource"] = $resource;
                            $resp["request"]["id"] = $id;
                            
                            parse_str(parse_url($getUri, PHP_URL_QUERY), $query);
                            
                                if (isset($query["relation"])) {
                                    $id = null;
                                    $resource_id = $resource.'_id';
                                    $relation = null;
                                    $foreach = 0;
                                    if (base64_decode($query["relation"], true) != false){
                                        $relation = base64_decode($query["relation"]);
                                        if (json_decode($relation, true) != null){
                                            $relation = json_decode($relation, true);
                                            $foreach = 1;
                                        } else {
                                            $relation = $query["relation"];
                                        }
                                    } else {
                                        $relation = $query["relation"];
                                    }
                                    $resp["request"]["relation"] = $relation;
 
                                    foreach($res as $key => $arr){
                                        if (isset($key) && isset($arr)) {
                                            $id = $arr->{$resource_id};
                                            $newArr = (array)$arr;
                                            //print_r($newdArr);
                                            if (isset($id)) {
                                                if ($foreach == 1) {
                                                    foreach($relation as $key => $value) {
                                                        $rel = jsonDb::table($key)->where($resource_id, '=', $id)->findAll();
                                                        foreach($rel as $k => $v) {
                                                            if (in_array($k, $value)) {
                                                                $a = array($k, $v);
                                                                unset($a["0"]);
                                                                $a = $a["1"];
                                                                $r[$key][] = $a;
                                                            }
                                                        }
                                                        $newArr = array_merge($newArr, $r);
                                                    }
                                                } else {
                                                    $rel = null;
                                                    $ex = explode(",", $relation);
                                                    foreach($ex as $ex_keys => $ex_val) {
                                                        $ex_pos = strripos($ex_val, ":");
                                                        $new_ex = [];
                                                        if ($ex_pos === false) {
                                                            $val = $ex_val;
                                                            $c = 0;
                                                        } else {
                                                            $ex_new = explode(":", $ex_val);
                                                            $val = $ex_new["0"];
                                                            unset($ex_new["0"]);
                                                            //print_r($ex_new);
                                                            //print("<br>");
                                                            $new_ex = array_flip($ex_new);
                                                            $c = 1;
                                                        }

                                                        $val_name = $val.'_id';
                                                        if (isset($newArr[$val_name])) {
                                                            $val_id = $newArr[$val_name];
                                                        }
                                                        
                                                        $rel_table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$val.'.config.json'), true);

                                                        if (array_key_exists($resource_id, $rel_table_config["schema"]) && isset($id)) {
                                                            
                                                            $rel = jsonDb::table($val)->where($resource_id, '=', $id)->findAll();
                                                            if ($c == 1){
                                                                $control = $new_ex;
                                                            } else {
                                                                $control = $rel_table_config["schema"];
                                                            }
                                                            
                                                        } elseif(array_key_exists($val_name, $table_config["schema"]) && isset($val_id)) {
                                                        
                                                            $rel = jsonDb::table($val)->where($val_name, '=', $val_id)->findAll();
                                                            if ($c == 1){
                                                                $control = $new_ex;
                                                            } else {
                                                                $control = $rel_table_config["schema"];
                                                            }
                                                        }

                                                        if (count($rel) >= 1) {
                                                            $r = [];
                                                            foreach($rel as $k => $v) {
                                                                $vv = (array)$v;
                                                                $ar = [];
                                                                foreach($vv as $key => $va) {
                                                                    if (array_key_exists($key, $control) && $key != "password" && $key != "cookie") {
                                                                        $ar[$key] = $va;
                                                                    }
                                                                }
                                                            //$arr = 
                                                            //print_r($v);
                                                            //print("<br>");
                                                                $a = array($k, $ar);
                                                                unset($a["0"]);
                                                                $a = $a["1"];
                                                                $r[$val][] = $a;
                                                            }
                                                            $newArr = array_merge($newArr, $r);
                                                        }
                                                    }
                                                }
                                            }
                                            $newArr = (object)$newArr;
                                        }
                                        $array = array($key, $newArr);
                                        unset($array["0"]);
                                        $array = $array["1"];
                                        $item["item"] = $array;
                                        $items['items'][] = $item;
                                    }
                                    $resp['body'] = $items;
                                } else {
                                    foreach($res as $key => $arr){
                                        if (isset($key) && isset($arr)) {
                                            $array = array($key, $arr);
                                            unset($array["0"]);
                                            $array = $array["1"];
                                            $item["item"] = $array;
                                            $items['items'][] = $item;
                                        }
                                    }
                                    $resp['body'] = $items;
                                }
                            
                        } else {
                            $resp["headers"]["status"] = '404 Not Found';
                            $resp["headers"]["code"] = 404;
                            $resp["headers"]["message"] = 'Not Found';
                            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                            $resp["response"]["source"] = "db";
                            $resp["response"]["total"] = 0;
                            $resp["request"]["query"] = "GET";
                            $resp["request"]["resource"] = $resource;
                            $resp["request"]["id"] = $id;
                            $resp["body"]["items"]["item"] = "[]";
                        }
                        
                    } else {
                        // id не указан, формируем запрос списка
                        // Указываем таблицу
                        $count = jsonDb::table($resource);
                        $res = jsonDb::table($resource);
                        // Парсим URL
                        parse_str(parse_url($getUri, PHP_URL_QUERY), $query);
                        // Если есть параметры
                        $quertyCount = count($query);
                        if ($quertyCount >= 1) {
                            $resp["headers"]["status"] = "200 OK";
                            $resp["headers"]["code"] = 200;
                            $resp["headers"]["message"] = "OK";
                            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                            
                            if (isset($query["JSONPath"]) || isset($query["jsonpath"])) {
                                if (isset($query["JSONPath"])) {$unit = $query["JSONPath"];    }
                                if (isset($query["jsonpath"])) {$unit = $query["jsonpath"];    }
                                $unit = str_replace('"', '', $unit);
                                $file = $this->get('settings')['db']["dir"].''.$resource.'.data.json';
                                $data = json_decode(file_get_contents($file));
                                $resp["items"][] = (new JSONPath($data))->find($unit);
                            }
                            
                            if (isset($query["JmesPath"]) || isset($query["jmespath"])) {
                                if (isset($query["JmesPath"])) {$unit = $query["JmesPath"];}
                                if (isset($query["jmespath"])) {$unit = $query["jmespath"];    }
                                $unit = str_replace('"', '', $unit);
                                $file = $this->get('settings')['db']["dir"].''.$resource.'.data.json';
                                $data = json_decode(file_get_contents($file));
                                $resp["items"][] = \JmesPath\search($unit, $data);
                                /*
                                    $resp = new JmesPath\CompilerRuntime($data);
                                    $resp($querty);
                                */
                            }
                            
                            if (isset($query["JSONPath"]) == false && isset($query["jsonpath"]) == false && isset($query["JmesPath"]) == false && isset($query["jmespath"]) == false) {
                                
                                foreach($query as $key => $value){
                                    if(!in_array($key, ['andWhere',
                                    'orWhere',
                                    'asArray',
                                    'LIKE',
                                    'relation',
                                    'order',
                                    'sort',
                                    'limit',
                                    'offset',
                                    'JSONPath',
                                    'jsonpath',
                                    'JmesPath',
                                    'jmespath'
                                    ], true)){
                                        
                                        if (isset($key) && isset($value)) {
                                            if (array_key_exists($key, $table_config["schema"])) {
                                                // Убираем пробелы и одинарные кавычки
                                                $key = str_replace(array(" ", "'", "%", "%27", "%20"), "", $key);
                                                $value = str_replace(array(" ", "'", "%", "%27", "%20"), "", $value);
                                                $count->where($key, '=', $value);
                                                $res->where($key, '=', $value);
                                                $resp["request"][$key] = $value;
                                            }
                                        }
                                    }
                                }
                                
                                if (isset($query["andWhere"])) {
                                    // Убираем пробелы и одинарные кавычки
                                    $andWhere = str_replace(array(" ", "'", "%"), "", $query["andWhere"]);
                                    // Ищем разделитель , запятую
                                    $pos = strripos($andWhere, ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->andWhere('id', '=', $andWhere);
                                        $res->andWhere('id', '=', $andWhere);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $andWhere);
                                        $count->andWhere($explode["0"], $explode["1"], $explode["2"]);
                                        $res->andWhere($explode["0"], $explode["1"], $explode["2"]);
                                    }
                                    $resp["request"]["andWhere"] = $query["andWhere"];
                                }
                                
                                if (isset($query["orWhere"])) {
                                    // Убираем пробелы и одинарные кавычки
                                    $orWhere = str_replace(array(" ", "'", "%"), "", $query["orWhere"]);
                                    // Ищем разделитель , запятую
                                    $pos = strripos($orWhere, ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->orWhere('id', '=', $orWhere);
                                        $res->orWhere('id', '=', $orWhere);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $relation);
                                        $count->orWhere($explode["0"], $explode["1"], $explode["2"]);
                                        $res->orWhere($explode["0"], $explode["1"], $explode["2"]);
                                    }
                                    $resp["request"]["orWhere"] = $query["orWhere"];
                                }
                                
                                if (isset($query["LIKE"])) {
                                    // Ищем разделитель , запятую
                                    $pos = strripos($query["LIKE"], ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->where('id', 'LIKE', $query["LIKE"]);
                                        $res->where('id', 'LIKE', $query["LIKE"]);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $query["LIKE"]);
                                        $count->where(str_replace(array(" ", "'"), "", $explode["0"]), 'LIKE', str_replace(array("<", ">", "'"), "", $explode["1"]));
                                        $res->where(str_replace(array(" ", "'"), "", $explode["0"]), 'LIKE', str_replace(array("<", ">", "'"), "", $explode["1"]));
                                    }
                                    $resp["request"]["LIKE"] = $query["LIKE"];
                                }
                                
                                if (isset($query["order"]) || isset($query["sort"])) {
                                    
                                    $order = "DESC";
                                    $sort = "id";
                                    
                                    if (isset($query["order"])) {
                                        if ($query["order"] == "DESC" || $query["order"] == "ASC" || $query["order"] == "desc" || $query["order"] == "asc") {
                                            $order = $query["offset"];
                                        }
                                    }
                                    
                                    if (isset($query["sort"])) {if (preg_match("/^[A-Za-z0-9]+$/", $query["sort"])) {
                                        $sort = $query["sort"];
                                    }}
                                    
                                    $res->orderBy($sort, $order);
                                    $resp["request"]["order"] = $order;
                                    $resp["request"]["sort"] = $sort;
                                }
                                
                                if (isset($query["limit"]) && isset($query["offset"]) == false) {
                                    $limit = intval($query["limit"]);
                                    $res->limit($limit);
                                    $resp["request"]["limit"] = $limit;
                                    $resp["request"]["offset"] = 0;
                                    } elseif (isset($query["limit"]) && isset($query["offset"])) {
                                    $limit = intval($query["limit"]);
                                    $offset = intval($query["offset"]);
                                    $res->limit($limit, $offset);
                                    $resp["request"]["limit"] = $limit;
                                    $resp["request"]["offset"] = $offset;
                                }
                                
                                $res->findAll();
                                
                                if (isset($query["asArray"])) {
                                    // Не работает в этом случае. Если цепочкой то работает.
                                    if ($query["asArray"] == true) {
                                        $res->asArray();
                                        $resp["request"]["asArray"] = true;
                                    }
                                }
                                
                                $count->findAll()->count();
                                $newCount = count($count);
                            }
                            
                            $resCount = count($res);
                            if ($resCount >= 1) {
                                $resp["headers"]["status"] = "200 OK";
                                $resp["headers"]["code"] = 200;
                                $resp["headers"]["message"] = "OK";
                                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                                $resp["response"]["source"] = "db";
                                $resp["response"]["total"] = $newCount;
                                $resp["request"]["query"] = "GET";
                                $resp["request"]["resource"] = $resource;
                                if (isset($query["relation"])) {
                                    $id = null;
                                    $resource_id = $resource.'_id';
                                    $relation = null;
                                    $foreach = 0;
                                    if (base64_decode($query["relation"], true) != false){
                                        $relation = base64_decode($query["relation"]);
                                        if (json_decode($relation, true) != null){
                                            $relation = json_decode($relation, true);
                                            $foreach = 1;
                                        } else {
                                            $relation = $query["relation"];
                                        }
                                    } else {
                                        $relation = $query["relation"];
                                    }
                                    $resp["request"]["relation"] = $relation;
                                    foreach($res as $key => $arr){
                                        if (isset($key) && isset($arr)) {
                                            $id = $arr->{$resource_id};
                                            $newArr = (array)$arr;
                                            if (isset($id)) {
                                                if ($foreach == 1) {
                                                    foreach($relation as $key => $value) {
                                                        $rel = jsonDb::table($key)->where($resource_id, '=', $id)->findAll();
                                                        foreach($rel as $k => $v) {
                                                            if (in_array($k, $value)) {
                                                                $a = array($k, $v);
                                                                unset($a["0"]);
                                                                $a = $a["1"];
                                                                $r[$key][] = $a;
                                                            }
                                                        }
                                                        $newArr = array_merge($newArr, $r);
                                                    }
                                                } else {
                                                    $rel = null;
                                                    $ex = explode(",", $relation);
                                                    foreach($ex as $ex_keys => $ex_val) {
                                                        $ex_pos = strripos($ex_val, ":");
                                                        $new_ex = [];
                                                        if ($ex_pos === false) {
                                                            $val = $ex_val;
                                                            $c = 0;
                                                        } else {
                                                            $ex_new = explode(":", $ex_val);
                                                            $val = $ex_new["0"];
                                                            unset($ex_new["0"]);
                                                            $new_ex = array_flip($ex_new);
                                                            $c = 1;
                                                        }
                                                        $val_name = $val.'_id';
                                                        if (isset($newArr[$val_name])) {
                                                            $val_id = $newArr[$val_name];
                                                        }
                                                        $rel_table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$val.'.config.json'), true);
                                                        if (array_key_exists($resource_id, $rel_table_config["schema"]) && isset($id)) {
                                                            
                                                            $rel = jsonDb::table($val)->where($resource_id, '=', $id)->findAll();
                                                            if ($c == 1){
                                                                $control = $new_ex;
                                                            } else {
                                                                $control = $rel_table_config["schema"];
                                                            }
                                                        } elseif(array_key_exists($val_name, $table_config["schema"]) && isset($val_id)) {
                                                            $rel = jsonDb::table($val)->where($val_name, '=', $val_id)->findAll();
                                                            if ($c == 1){
                                                                $control = $new_ex;
                                                            } else {
                                                                $control = $rel_table_config["schema"];
                                                            }
                                                        }
                                                        if (count($rel) >= 1) {
                                                            $r = [];
                                                            foreach($rel as $k => $v) {
                                                                $vv = (array)$v;
                                                                $ar = [];
                                                                foreach($vv as $key => $va) {
                                                                    if (array_key_exists($key, $control) && $key != "password" && $key != "cookie") {
                                                                        $ar[$key] = $va;
                                                                    }
                                                                }
                                                            //$arr = 
                                                            //print_r($v);
                                                            //print("<br>");
                                                                $a = array($k, $ar);
                                                                unset($a["0"]);
                                                                $a = $a["1"];
                                                                $r[$val][] = $a;
                                                            }
                                                            $newArr = array_merge($newArr, $r);
                                                        }
                                                    }
                                                }
                                            }
                                            $newArr = (object)$newArr;
                                        }
                                        $array = array($key, $newArr);
                                        unset($array["0"]);
                                        $array = $array["1"];
                                        $item["item"] = $array;
                                        $items['items'][] = $item;
                                    }
                                    $resp['body'] = $items;
                                } else {
                                    foreach($res as $key => $arr){
                                        if (isset($key) && isset($arr)) {
                                            $array = array($key, $arr);
                                            unset($array["0"]);
                                            $array = $array["1"];
                                            $item["item"] = $array;
                                            $items['items'][] = $item;
                                        }
                                    }
                                    $resp['body'] = $items;
                                }
                            } else {
                                // База вернула 0 записей или null
                                $resp["headers"]["status"] = "404 Not Found";
                                $resp["headers"]["code"] = 404;
                                $resp["headers"]["message"] = "Not Found";
                                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                                $resp["response"]["source"] = "db";
                                $resp["response"]["total"] = 0;
                                $resp["request"]["query"] = "GET";
                                $resp["request"]["resource"] = $resource;
                            }
                            
                            // Записываем данные в кеш
                            Db::cacheWriter($cacheUri, $resp);
                            
                        } else {
                            // Параметров нет отдаем все записи
                            $res = jsonDb::table($resource)->findAll();
                            $resCount = count($res);
                            if ($resCount >= 1) {
                                $resp["headers"]["status"] = "200 OK";
                                $resp["headers"]["code"] = 200;
                                $resp["headers"]["message"] = "OK";
                                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                                $resp["response"]["source"] = "db";
                                $resp["response"]["total"] = $resCount;
                                $resp["request"]["query"] = "GET";
                                $resp["request"]["resource"] = $resource;

                                foreach($res as $key => $value){
                                    if (isset($key) && isset($value)) {
                                        $array = array($key, $value);
                                        unset($array["0"]);
                                        $array = $array["1"];
                                        $item["item"] = $array;
                                        $items['items'][] = $item;
                                    }
                                }
                                $resp['body'] = $items;
                            } else {
                                $resp["headers"]["status"] = "404 Not Found";
                                $resp["headers"]["code"] = 404;
                                $resp["headers"]["message"] = "Not Found";
                                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                                $resp["response"]["source"] = "db";
                                // База вернула 0 записей или null
                                $resp["response"]["total"] = 0;
                                $resp["request"]["query"] = "GET";
                                $resp["request"]["resource"] = $resource;
                            }
                            
                            // Записываем данные в кеш
                            Db::cacheWriter($cacheUri, $resp);
                        }
                    }
                    
                } else {
                    // Если нашли в кеше отдаем с кеша
                    $resp = $cacheReader;
                }
            } catch(dbException $e) {
                // Такой таблицы не существует
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'resource Not Found';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
                $resp["request"]["query"] = "GET";
                $resp["request"]["resource"] = '';
            }
        } else {
            // Название таблицы не задано.
            $resp["headers"]["status"] = '403 Access is denied';
            $resp["headers"]["code"] = 403;
            $resp["headers"]["message"] = 'Access is denied';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
            $resp["request"]["query"] = "GET";
            $resp["request"]["resource"] = '';
        }
    } else {
        // Ключ доступа не совпадает.
        $resp["headers"]["status"] = '403 Access is denied';
        $resp["headers"]["code"] = 403;
        $resp["headers"]["message"] = 'Access is denied';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
        $resp["request"]["query"] = "GET";
        $resp["request"]["resource"] = '';
    }
 
    // Выводим результат
    echo json_encode($resp, JSON_PRETTY_PRINT);
    return $response->withStatus(200)->withHeader('Content-Type','application/json');
 
});

$app->post('/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {
 
    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $post = $request->getParsedBody();
 
    if (isset($resource)) {
        if ($id >= 1) {
            // Если указан id даем ошибку: 400 Bad Request «плохой, неверный запрос»
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        } else {

            // Проверяем наличие главной базы если нет даем ошибку
            try {Validate::table($resource)->exists();
            
                $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);

                $public_key = '';
                if ($this->get('settings')['db']["access_key"] == true){
                    $public_key = filter_var($post['public_key'], FILTER_SANITIZE_STRING);
                } else {
                    $public_key = $this->get('settings')['db']["public_key"];
                }

                if ($this->get('settings')['db']["public_key"] == Db::clean($public_key)) {

                    // Подключаем таблицу
                    $row = jsonDb::table($resource);
    
                    // Разбираем параметры полученные в теле запроса
                    foreach($post as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id" && $key != "public_key") {
                                if (array_key_exists($key, $table_config["schema"])) {
                                    $key = str_replace(array("&#39;","&#34;"), "", $key);
                                    $value = str_replace(array("&#39;","&#34;"), "", $value);
                                    $value = str_replace(array("%20;")," ", $value);
                                    
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = intval($value);
                                        } else {
                                            $value = 0;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value * 1)) {
                                            //$value = floatval($value);
                                            $value = (float)$value;
                                        } else {
                                            $value = (float)$value;
                                            //$value = number_format($value, 2, '.', '');
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = boolval($value);
                                        } else {
                                            $value = false;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
                                        } else {
                                            $value = null;
                                        }
                                    }
                                    try {
                                        $row->{$key} = $value;
                                    } catch(dbException $error){
                                        //echo $error;
                                    }
                                }
                            }
                        }
                    }
                    // Сохраняем
                    $row->save();

                    if ($row->id >= 1) {
                        // Добавляем вротой id
                        $update = jsonDb::table($resource)->find($row->id);
                        $update->{$resource."_id"} = $row->id;
                        $update->save();
                        
                        // Все ок. 201 Created «создано»
                        $resp["headers"]["status"] = "201 Created";
                        $resp["headers"]["code"] = 201;
                        $resp["headers"]["message"] = "Created";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $row->id;
                        $resp["request"]["query"] = "POST";
                        $resp["request"]["resource"] = $resource;
                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                } else {
                    // Доступ запрещен. Ключ доступа не совпадает.
                    $resp["headers"]["status"] = '401 Unauthorized';
                    $resp["headers"]["code"] = 401;
                    $resp["headers"]["message"] = 'Access is denied';
                    $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["total"] = 0;
                }

            } catch(dbException $e){
                // Таблица не существует даем ошибку 404
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'resource Not Found';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }
        }

    } else {
        // Если таблица не определена даем ошибку 400
        $resp["headers"]["status"] = '400 Bad Request';
        $resp["headers"]["code"] = 400;
        $resp["headers"]["message"] = 'Bad Request';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
    }

    echo json_encode($resp, JSON_PRETTY_PRINT);
    
    return $response->withStatus(200)->withHeader('Content-Type','application/json');

});

$app->map(['PUT', 'PATCH'], '/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {
//$app->put('/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {
    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $put = $request->getParsedBody();
    //print_r($put);
    if (isset($resource)) {
        // Проверяем наличие главной базы если нет даем ошибку
        try {Validate::table($resource)->exists();
    
            $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);
    
            $public_key = '';
            if ($this->get('settings')['db']["access_key"] == true){
                $public_key = filter_var($put["request"]['public_key'], FILTER_SANITIZE_STRING);
            } else {
                $public_key = $this->get('settings')['db']["public_key"];
            }

            if ($this->get('settings')['db']["public_key"] == Db::clean($public_key)) {

                // Если указан id обновляем одну запись
                if ($id >= 1) {
                    // Подключаем таблицу
                    $row = jsonDb::table($resource)->find($id);
                    // Разбираем параметры полученные в теле запроса
                    foreach($put as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id" && $key != "public_key") {
                                if (array_key_exists($key, $table_config["schema"])) {
                                    $key = str_replace(array("&#39;","&#34;"), "", $key);
                                    $value = str_replace(array("&#39;","&#34;"), "", $value);
                                    $value = str_replace(array("%20;")," ", $value);
                                    
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = intval($value);
                                        } else {
                                            $value = 0;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value * 1)) {
                                            $value = (float)$value;
                                        } else {
                                            $value = (float)$value;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = boolval($value);
                                        } else {
                                            $value = false;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
                                        } else {
                                            $value = null;
                                        }
                                        
                                    }
                                    else {
                                        $value = null;
                                    }
                                    try {
                                        $row->{$key} = $value;
                                        
                                    } catch(dbException $error){
                                        //echo $error;
                                    }
                                }
                            }
                        }
                    }
                    // Сохраняем изменения
                    $row->save();

                    if ($row == 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $id;
                        $resp["request"]["query"] = "PUT";
                        $resp["request"]["resource"] = $resource;

                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                } else {
                    // Обновляем несколько записей
                    // Разбираем параметры полученные в теле запроса
                    foreach($put as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id" && $key != "public_key") {
                                if (array_key_exists($key, $table_config["schema"])) {
                                    $key = str_replace(array("&#39;","&#34;"), "", $key);
                                    $value = str_replace(array("&#39;","&#34;"), "", $value);
                                    $value = str_replace(array("%20;")," ", $value);
                                    
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = intval($value);
                                        } else {
                                            $value = 0;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value)) {
                                            $value = floatval($value);
                                        } else {
                                            $value = 0.00;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = boolval($value);
                                        } else {
                                            $value = false;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
                                        } else {
                                            $value = null;
                                        }
                                        
                                    }
                                    else {
                                        $value = null;
                                    }
 
                                    try {
                                        $row->{$key} = $value;
                                        
                                    } catch(dbException $error){
                                        //echo $error;
                                    }
                                }
                            }
                        }
                    }
                    // Сохраняем изменения
                    $row->save();

                    if ($row->id >= 1) {
                        
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 1;
                        $resp["response"]["id"] = '';
                        $resp["request"]["query"] = "PUT";
                        $resp["request"]["resource"] = $resource;
                        
                    } else {
                        
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                }

            } else {

                // Доступ запрещен. Ключ доступа не совпадает.
                $resp["headers"]["status"] = '401 Unauthorized';
                $resp["headers"]["code"] = 401;
                $resp["headers"]["message"] = 'Access is denied';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }

        } catch(dbException $e){

            // Таблица не существует даем ошибку 404
            $resp["headers"]["status"] = '404 Not Found';
            $resp["headers"]["code"] = 404;
            $resp["headers"]["message"] = 'Not Found';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;

        }

    } else {

        // Если таблица не определена даем ошибку 400
        $resp["headers"]["status"] = '400 Bad Request';
        $resp["headers"]["code"] = 400;
        $resp["headers"]["message"] = 'Bad Request';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
    }

    echo json_encode($resp, JSON_PRETTY_PRINT);

    return $response->withStatus(200)->withHeader('Content-Type','application/json');

});

$app->delete('/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {

    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $delete = $request->getParsedBody();

    if (isset($resource)) {

        // Проверяем наличие главной базы если нет даем ошибку
        try {Validate::table($resource)->exists();
    
            $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);
 
            if ($this->get('settings')['db']["access_key"] == true){
                $public_key = filter_var($delete["request"]['public_key'], FILTER_SANITIZE_STRING);
            } else {
                $public_key = $this->get('settings')['db']["public_key"];
            }

            if ($this->get('settings')['db']["public_key"] == Db::clean($public_key)) {

                // Если указан id удаляем одну запись
                if ($id >= 1) {
    
                    // Удаляем запись из таблицы
                    $row = jsonDb::table($resource)->find($id)->delete();

                    if ($row == 1) {
                    
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "200 Removed";
                        $resp["headers"]["code"] = 200;
                        $resp["headers"]["message"] = "Removed";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $id;
                        $resp["request"]["query"] = "DELETE";
                        $resp["request"]["resource"] = $resource;

                    } else {

                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                } else {

                    try {
                    
                        $file = $this->get('settings')['db']["dir"].'/'.$resource.'.data.json';
                        // Открываем файл для получения существующего содержимого
                        $current = file_get_contents($file);
                        // Очищаем весь контент оставляем только []
                        $current = "[]";
                        // Пишем содержимое обратно в файл
                        file_put_contents($file, $current);
                        
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "200 Removed";
                        $resp["headers"]["code"] = 200;
                        $resp["headers"]["message"] = "Deleted all rows";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = 'All';
                        $resp["request"]["query"] = "DELETE";
                        $resp["request"]["resource"] = $resource;
                        
                    } catch(dbException $e){
                        
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                }

            } else {

                // Доступ запрещен. Ключ доступа не совпадает.
                $resp["headers"]["status"] = '401 Unauthorized';
                $resp["headers"]["code"] = 401;
                $resp["headers"]["message"] = 'Access is denied';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }

        } catch(dbException $e){

            // Таблица не существует даем ошибку 404
            $resp["headers"]["status"] = '404 Not Found';
            $resp["headers"]["code"] = 404;
            $resp["headers"]["message"] = 'Not Found';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;

        }

    } else {

        // Если таблица не определена даем ошибку 400
        $resp["headers"]["status"] = '400 Bad Request';
        $resp["headers"]["code"] = 400;
        $resp["headers"]["message"] = 'Bad Request';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
    }

    echo json_encode($resp, JSON_PRETTY_PRINT);

    return $response->withStatus(200)->withHeader('Content-Type','application/json');

});

$app->get('/_get/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {
    
    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $getParams = $request->getQueryParams();
    $getUri = $request->getUri();
    
    if ($this->get('settings')['db']["access_key"] == true){
        $public_key = (isset($getParams['public_key'])) ? Db::clean($getParams['public_key']) : "none";
        } else {
        $public_key = $this->get('settings')['db']["public_key"];
    }
    
    if ($this->get('settings')['db']["public_key"] == $public_key) {
        
        $resp = [];
        
        if (isset($resource)) {
            
            // Проверяем наличие главной базы если нет даем ошибку
            try {
                Validate::table($resource)->exists();
                
                $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);
                
                if (parse_url($getUri, PHP_URL_QUERY)) {$url_query = '?'.parse_url($getUri, PHP_URL_QUERY);} else {$url_query = '';}
                $url_path = parse_url($getUri, PHP_URL_PATH);
                
                // Формируем url для работы с кешем
                $cacheUri = $url_path.''.$url_query;
                
                // Читаем данные в кеше
                $cacheReader = Db::cacheReader($cacheUri);
                if ($cacheReader == null) { // Начало
                    
                    if ($id >= 1) {
                        $res = jsonDb::table($resource)->where('id', '=', $id)->findAll();
                        
                        //print_r($res);
                        
                        $resCount = count($res);
                        if ($resCount == 1) {
                            
                            $resp["headers"]["status"] = "200 OK";
                            $resp["headers"]["code"] = 200;
                            $resp["headers"]["message"] = "OK";
                            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                            $resp["response"]["source"] = "db";
                            $resp["response"]["total"] = $resCount;
                            $resp["request"]["query"] = "GET";
                            $resp["request"]["resource"] = $resource;
                            $resp["request"]["id"] = $id;
                            
                            foreach($res AS $key => $unit){
                                if (isset($key) && isset($unit)) {
                                    $item[$key] = $unit;
                                }
                            }
                            
                            $resp["body"]["items"]["item"] = $item;
                            
                        }
                        else {
                            $resp["headers"]["status"] = '404 Not Found';
                            $resp["headers"]["code"] = 404;
                            $resp["headers"]["message"] = 'Not Found';
                            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                            $resp["response"]["source"] = "db";
                            $resp["response"]["total"] = 0;
                            $resp["request"]["query"] = "GET";
                            $resp["request"]["resource"] = $resource;
                            $resp["request"]["id"] = $id;
                            $resp["body"]["items"]["item"] = "[]";
                        }
                        
                    }
                    else {
                        
                        // Указываем таблицу
                        $count = jsonDb::table($resource);
                        $res = jsonDb::table($resource);
                        
                        parse_str(parse_url($getUri, PHP_URL_QUERY), $query);
                        
                        //print_r($query);
                        $quertyCount = count($query);
                        if ($quertyCount >= 1) {
                            
                            $resp["headers"]["status"] = "200 OK";
                            $resp["headers"]["code"] = 200;
                            $resp["headers"]["message"] = "OK";
                            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                            
                            if (isset($query["JSONPath"]) || isset($query["jsonpath"])) {
                                if (isset($query["JSONPath"])) {$unit = $query["JSONPath"];    }
                                if (isset($query["jsonpath"])) {$unit = $query["jsonpath"];    }
                                $unit = str_replace('"', '', $unit);
                                $file = $this->get('settings')['db']["dir"].''.$resource.'.data.json';
                                $data = json_decode(file_get_contents($file));
                                $resp["items"] = (new JSONPath($data))->find($unit);
                            }
                            
                            if (isset($query["JmesPath"]) || isset($query["jmespath"])) {
                                if (isset($query["JmesPath"])) {$unit = $query["JmesPath"];}
                                if (isset($query["jmespath"])) {$unit = $query["jmespath"];    }
                                $unit = str_replace('"', '', $unit);
                                $file = $this->get('settings')['db']["dir"].''.$resource.'.data.json';
                                $data = json_decode(file_get_contents($file));
                                $resp["items"] = \JmesPath\search($unit, $data);
                                /*
                                    $resp = new JmesPath\CompilerRuntime($data);
                                    $resp($querty);
                                */
                            }
                            
                            if (
                            isset($query["JSONPath"]) == false
                            && isset($query["jsonpath"]) == false
                            && isset($query["JmesPath"]) == false
                            && isset($query["jmespath"]) == false
                            ) {
                                
                                foreach($query as $key => $value){
                                    if(!in_array($key, array(
                                    'andWhere',
                                    'orWhere',
                                    'asArray',
                                    'LIKE',
                                    'relation',
                                    'order',
                                    'sort',
                                    'limit',
                                    'offset',
                                    'JSONPath',
                                    'jsonpath',
                                    'JmesPath',
                                    'jmespath'
                                    ), true)){
                                        
                                        if (isset($key) && isset($value)) {
                                            
                                            if (array_key_exists($key, $table_config["schema"])) {
                                                // Убираем пробелы и одинарные кавычки
                                                $key = str_replace(array(" ", "'", "%", "%27", "%20"), "", $key);
                                                $value = str_replace(array(" ", "'", "%", "%27", "%20"), "", $value);
                                                $count->where($key, '=', $value);
                                                $res->where($key, '=', $value);
                                                $resp["request"][$key] = $value;
                                            }
                                        }
                                    }
                                }
                                
                                if (isset($query["andWhere"])) {
                                    // Убираем пробелы и одинарные кавычки
                                    $andWhere = str_replace(array(" ", "'", "%"), "", $query["andWhere"]);
                                    // Ищем разделитель , запятую
                                    $pos = strripos($andWhere, ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->andWhere('id', '=', $andWhere);
                                        $res->andWhere('id', '=', $andWhere);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $andWhere);
                                        $count->andWhere($explode["0"], $explode["1"], $explode["2"]);
                                        $res->andWhere($explode["0"], $explode["1"], $explode["2"]);
                                    }
                                    $resp["request"]["andWhere"] = $query["andWhere"];
                                }
                                
                                if (isset($query["orWhere"])) {
                                    // Убираем пробелы и одинарные кавычки
                                    $orWhere = str_replace(array(" ", "'", "%"), "", $query["orWhere"]);
                                    // Ищем разделитель , запятую
                                    $pos = strripos($orWhere, ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->orWhere('id', '=', $orWhere);
                                        $res->orWhere('id', '=', $orWhere);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $relation);
                                        $count->orWhere($explode["0"], $explode["1"], $explode["2"]);
                                        $res->orWhere($explode["0"], $explode["1"], $explode["2"]);
                                    }
                                    $resp["request"]["orWhere"] = $query["orWhere"];
                                }
                                
                                if (isset($query["LIKE"])) {
                                    // Ищем разделитель , запятую
                                    $pos = strripos($query["LIKE"], ",");
                                    if ($pos === false) {
                                        // : запятая не найдена
                                        $count->where('id', 'LIKE', $query["LIKE"]);
                                        $res->where('id', 'LIKE', $query["LIKE"]);
                                        } else {
                                        // , запятая найдена
                                        $explode = explode(",", $query["LIKE"]);
                                        $count->where(str_replace(array(" ", "'"), "", $explode["0"]), 'LIKE', str_replace(array("<", ">", "'"), "", $explode["1"]));
                                        $res->where(str_replace(array(" ", "'"), "", $explode["0"]), 'LIKE', str_replace(array("<", ">", "'"), "", $explode["1"]));
                                    }
                                    $resp["request"]["LIKE"] = $query["LIKE"];
                                }
                                
                                // Если ключ команда
                                if (isset($query["relation"])) {
                                    
                                    // Убираем пробелы и одинарные кавычки
                                    $relation = str_replace(array(" ", "'", "%", "%27", "%20"), "", $query["relation"]);
                                    // Ищем разделитель , запятую
                                    $pos = strripos($relation, ",");
                                    if ($pos === false) {
                                        // , запятая не найдена
                                        $count->with($relation);
                                        $res->with($relation);
                                        $resp["request"]["relation"] = $relation;
                                        } else {
                                        $explode = explode(",", $relation);
                                        // , запятая найдена
                                        
                                        // Здесь не работает !!!
                                        foreach($explode as $key => $relation){
                                            if (isset($relation)) {
                                                $count->with($relation);
                                                $res->with($relation);
                                                $resp["request"]["relation"] = $relation;
                                            }
                                        }
                                        
                                    }
                                    
                                }
                                
                                if (isset($query["order"]) || isset($query["sort"])) {
                                    
                                    $order = "DESC";
                                    $sort = "id";
                                    
                                    if (isset($query["order"])) {
                                        if ($query["order"] == "DESC" || $query["order"] == "ASC" || $query["order"] == "desc" || $query["order"] == "asc") {
                                            $order = $query["offset"];
                                        }
                                    }
                                    
                                    if (isset($query["sort"])) {if (preg_match("/^[A-Za-z0-9]+$/", $query["sort"])) {
                                        $sort = $query["sort"];
                                    }}
                                    
                                    $res->orderBy($sort, $order);
                                    $resp["request"]["order"] = $order;
                                    $resp["request"]["sort"] = $sort;
                                }
                                
                                if (isset($query["limit"]) && isset($query["offset"]) == false) {
                                    $limit = intval($query["limit"]);
                                    $res->limit($limit);
                                    $resp["request"]["limit"] = $limit;
                                    $resp["request"]["offset"] = 0;
                                    } elseif (isset($query["limit"]) && isset($query["offset"])) {
                                    $limit = intval($query["limit"]);
                                    $offset = intval($query["offset"]);
                                    $res->limit($limit, $offset);
                                    $resp["request"]["limit"] = $limit;
                                    $resp["request"]["offset"] = $offset;
                                }
                                
                                $res->findAll();
                                
                                if (isset($query["asArray"])) {
                                    // Не работает в этом случае. Если цепочкой то работает.
                                    if ($query["asArray"] == true) {
                                        $res->asArray();
                                        $resp["request"]["asArray"] = true;
                                    }
                                }
                                
                                $count->findAll()->count();
                                $newCount = count($count);
                                
                            }
                            
                            //print_r($res);
                            
                            $resCount = count($res);
                            if ($resCount >= 1) {
                                $resp["response"]["total"] = $newCount;
                                foreach($res AS $key => $unit){
                                    if (isset($key) && isset($unit)) {
                                        $item[$key] = $unit;
                                        $items["item"] = $item;
                                    }
                                }
                                
                                $resp["body"]["items"] = $items;
                                
                            }
                            else {
                                // База вернула 0 записей или null
                                $resp["response"]["total"] = 0;
                            }
                            
                            // Записываем данные в кеш
                            Db::cacheWriter($cacheUri, $resp);
                            
                        }
                    }
                    
                } // Конец
                else {
                    // Отдаем с кеша
                    $resp = $cacheReader;
                }
                
            }
            catch(dbException $e){
                // Доступ запрещен. Такой таблицы не существует.
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'Table Not Found';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }
            
        }
        else {
            // Доступ запрещен. Название таблицы не задано.
            $resp["headers"]["status"] = '403';
            $resp["headers"]["code"] = 403;
            $resp["headers"]["message"] = 'Access is denied';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        }
        
    }
    else {
        // Доступ запрещен. Ключ доступа не совпадает.
        $resp["headers"]["status"] = '403';
        $resp["headers"]["code"] = 403;
        $resp["headers"]["message"] = 'Access is denied';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
    }
    
    echo json_encode($resp, JSON_PRETTY_PRINT);
    return $response->withStatus(200)->withHeader('Content-Type','application/json');
    
});

$app->get('/_post/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {
 
    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $getParams = $request->getQueryParams();
    $getUri = $request->getUri();
 
    if (isset($resource)) {
        if ($id >= 1) {
            // Если указан id даем ошибку: 400 Bad Request «плохой, неверный запрос»
            $resp["headers"]["status"] = '400 Bad Request';
            $resp["headers"]["code"] = 400;
            $resp["headers"]["message"] = 'Bad Request';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        } else {
 
            // Проверяем наличие главной базы если нет даем ошибку
            try {Validate::table($resource)->exists();
            
                $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);
 
                if ($this->get('settings')['db']["access_key"] == true){
                    $public_key = (isset($getParams['public_key'])) ? Db::clean($getParams['public_key']) : "none";
                } else {
                    $public_key = $this->get('settings')['db']["public_key"];
                }
 
                if ($this->get('settings')['db']["public_key"] == $public_key) {
                    parse_str(parse_url($getUri, PHP_URL_QUERY), $query);
                    $queryCount = count($query);
                    if ($queryCount >= 1) {
                        // Подключаем таблицу
                        $row = jsonDb::table($resource);
                        // Разбираем параметры полученные в теле запроса
                        foreach($query as $key => $value){
                            if (isset($key) && isset($value)) {
                                if ($key != "id" && $key != "public_key") {
                                    if (array_key_exists($key, $table_config["schema"])) {
                                        $key = str_replace(array("&#39;","&#34;"), "", $key);
                                        $value = str_replace(array("&#39;","&#34;"), "", $value);
                                        $value = str_replace(array("%20;")," ", $value);
                                        if ($table_config["schema"][$key] == "integer") {
                                            if (is_numeric($value)) {
                                                $value = intval($value);
                                            } else {
                                                $value = 0;
                                            }
                                        }
                                        if ($table_config["schema"][$key] == "double") {
                                            if (is_float($value * 1)) {
                                                $value = (float)$value;
                                            } else {
                                                $value = (float)$value;
                                            }
                                        }
                                        if ($table_config["schema"][$key] == "boolean") {
                                            if (is_bool($value)) {
                                                $value = boolval($value);
                                            } else {
                                                $value = false;
                                            }
                                        }
                                        if ($table_config["schema"][$key] == "string") {
                                            if (is_string($value)) {
                                                $value = strval($value);
                                            } else {
                                                $value = null;
                                            }
                                        }
                                        try {
                                            $row->{$key} = $value;
                                        } catch(dbException $error){
                                            //echo $error;
                                        }
                                    }
                                }
                            }
                        }
                        // Сохраняем
                        $row->save();

                        if ($row->id >= 1) {
                            // Добавляем вротой id
                            $update = jsonDb::table($resource)->find($row->id);
                            $update->{$resource."_id"} = $row->id;
                            $update->save();
                            // Все ок. 201 Created «создано»
                            $resp["headers"]["status"] = "201 Created";
                            $resp["headers"]["code"] = 201;
                            $resp["headers"]["message"] = "Created";
                            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                            $resp["response"]["id"] = $row->id;
                            $resp["request"]["query"] = "POST";
                            $resp["request"]["resource"] = $resource;
                        } else {
                            // Не удалось создать. 501 Not Implemented «не реализовано»
                            $resp["headers"]["status"] = '501 Not Implemented';
                            $resp["headers"]["code"] = 501;
                            $resp["headers"]["message"] = 'Not Implemented';
                            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                            $resp["response"]["total"] = 0;
                        }
 
                    } else {
                        // Не удалось создать. 400 Bad Request «плохой, неверный запрос»
                        $resp["headers"]["status"] = '400 Bad Request';
                        $resp["headers"]["code"] = 400;
                        $resp["headers"]["message"] = 'Bad Request';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }
 
                } else {
                    // Доступ запрещен. Ключ доступа не совпадает.
                    $resp["headers"]["status"] = '401 Unauthorized';
                    $resp["headers"]["code"] = 401;
                    $resp["headers"]["message"] = 'Access is denied';
                    $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["total"] = 0;
                }
 
            } catch(dbException $e){
                // Таблица не существует даем ошибку 404
                $resp["headers"]["status"] = '404 Not Found';
                $resp["headers"]["code"] = 404;
                $resp["headers"]["message"] = 'Table Not Found - 3';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }
        }
 
    } else {
        // Если таблица не определена даем ошибку 400
        $resp["headers"]["status"] = '400 Bad Request';
        $resp["headers"]["code"] = 400;
        $resp["headers"]["message"] = 'Bad Request - 1';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
    }
 
    echo json_encode($resp, JSON_PRETTY_PRINT);
 
    return $response->withStatus(200)->withHeader('Content-Type','application/json');
 
});

$app->get('/_delete/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {

    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $getParams = $request->getQueryParams();
    $getUri = $request->getUri();
    if (isset($resource)) {
        // Проверяем наличие главной базы если нет даем ошибку
        try {Validate::table($resource)->exists();
            $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);
            if ($this->get('settings')['db']["access_key"] == true){
                $public_key = (isset($getParams['public_key'])) ? Db::clean($getParams['public_key']) : "none";
            } else {
                $public_key = $this->get('settings')['db']["public_key"];
            }

            if ($this->get('settings')['db']["public_key"] == $public_key) {
                // Если указан id удаляем одну запись
                if ($id >= 1) {
                    // Удаляем запись из таблицы
                    $row = jsonDb::table($resource)->find($id)->delete();
                    if ($row == 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "200 Removed";
                        $resp["headers"]["code"] = 200;
                        $resp["headers"]["message"] = "Removed";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $id;
                        $resp["request"]["query"] = "DELETE";
                        $resp["request"]["resource"] = $resource;
                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }
                } else {
                    try {
                    
                        $file = $this->get('settings')['db']["dir"].'/'.$resource.'.data.json';
                        // Открываем файл для получения существующего содержимого
                        $current = file_get_contents($file);
                        // Очищаем весь контент оставляем только []
                        $current = "[]";
                        // Пишем содержимое обратно в файл
                        file_put_contents($file, $current);
                        
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "200 Removed";
                        $resp["headers"]["code"] = 200;
                        $resp["headers"]["message"] = "Deleted all rows";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = 'All';
                        $resp["request"]["query"] = "DELETE";
                        $resp["request"]["resource"] = $resource;
                        
                    } catch(dbException $e){
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }
                }
 
            } else {
                // Доступ запрещен. Ключ доступа не совпадает.
                $resp["headers"]["status"] = '401 Unauthorized';
                $resp["headers"]["code"] = 401;
                $resp["headers"]["message"] = 'Access is denied';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }
 
        } catch(dbException $e){
            // Таблица не существует даем ошибку 404
            $resp["headers"]["status"] = '404 Not Found';
            $resp["headers"]["code"] = 404;
            $resp["headers"]["message"] = 'Not Found';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        }
 
    } else {
        // Если таблица не определена даем ошибку 400
        $resp["headers"]["status"] = '400 Bad Request';
        $resp["headers"]["code"] = 400;
        $resp["headers"]["message"] = 'Bad Request';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
    }
 
    echo json_encode($resp, JSON_PRETTY_PRINT);
 
    return $response->withStatus(200)->withHeader('Content-Type','application/json');
 
});

$app->get('/_put/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {
 
    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $getParams = $request->getQueryParams();
    $getUri = $request->getUri();
    $put = $request->getParsedBody();
 
    if (isset($resource)) {
 
        // Проверяем наличие главной базы если нет даем ошибку
        try {Validate::table($resource)->exists();
 
            $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);
 
            if ($this->get('settings')['db']["access_key"] == true){
                $public_key = (isset($getParams['public_key'])) ? Db::clean($getParams['public_key']) : "none";
            } else {
                $public_key = $this->get('settings')['db']["public_key"];
            }
            if ($this->get('settings')['db']["public_key"] == Db::clean($public_key)) {
 
                // Если указан id обновляем одну запись
                if ($id >= 1) {
                    // Подключаем таблицу
                    $row = jsonDb::table($resource)->find($id);
                    parse_str(parse_url($getUri, PHP_URL_QUERY), $query);
                    // Разбираем параметры полученные в теле запроса
                    foreach($query as $key => $value){
                        if (isset($key) && isset($value)) {
                            if ($key != "id" && $key != "public_key") {
                                if (array_key_exists($key, $table_config["schema"])) {
                                    $key = str_replace(array("&#39;","&#34;"), "", $key);
                                    $value = str_replace(array("&#39;","&#34;"), "", $value);
                                    $value = str_replace(array("%20;")," ", $value);
                                    if ($table_config["schema"][$key] == "integer") {
                                        if (is_numeric($value)) {
                                            $value = intval($value);
                                        } else {
                                            $value = 0;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "double") {
                                        if (is_float($value * 1)) {
                                            $value = (float)$value;
                                        } else {
                                            $value = (float)$value;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "boolean") {
                                        if (is_bool($value)) {
                                            $value = boolval($value);
                                        } else {
                                            $value = false;
                                        }
                                    }
                                    if ($table_config["schema"][$key] == "string") {
                                        if (is_string($value)) {
                                            $value = strval($value);
                                        } else {
                                            $value = null;
                                        }
                                    }
                                    try {
                                        $row->{$key} = $value;
                                    } catch(dbException $error){
                                        //echo $error;
                                    }
                                }
                            }
                        }
                    }
                    // Сохраняем
                    $row->save();

                    if ($row->id >= 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $id;
                        $resp["request"]["query"] = "PUT";
                        $resp["request"]["resource"] = $resource;

                        foreach($query as $key => $unit){
                            if (isset($key)) {
                                $item[$key] = $row->$key;
                            }
                        }
                        $resp["body"]["items"]["item"] = $item;
 
                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                } else {
                    // Обновляем несколько записей
                    parse_str(parse_url($getUri, PHP_URL_QUERY), $query);
                    
                    // Разбираем параметры полученные в теле запроса
                    foreach($query as $items){
                        $row = '';

                        foreach($items["item"] as $key => $unit){
                            if (isset($key) && isset($unit)) {
                                if ($key == "id") {
                                    $row = jsonDb::table($resource)->find($key);
                                }
                                if ($key != "key" && $key != "id" && array_key_exists($key, $table_config["schema"])) {

                                    $key = filter_var($key, FILTER_SANITIZE_STRING);
                                    $unit = filter_var($unit, FILTER_SANITIZE_STRING);
                                    if (is_numeric($unit)){$unit = intval($unit);}
                                    try {
                                        $row->{$key} = $unit;
                                    } catch(dbException $error){
                                        echo $error;
                                    }
                                }
                            }
                        }
                        // Сохраняем изменения
                        $row->save();
                    }

                    if ($row == 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = '';
                        $resp["request"]["query"] = "PUT";
                        $resp["request"]["resource"] = $resource;
                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                }

            } else {
                // Доступ запрещен. Ключ доступа не совпадает.
                $resp["headers"]["status"] = '401 Unauthorized';
                $resp["headers"]["code"] = 401;
                $resp["headers"]["message"] = 'Access is denied';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }

        } catch(dbException $e){
            // Таблица не существует даем ошибку 404
            $resp["headers"]["status"] = '404 Not Found';
            $resp["headers"]["code"] = 404;
            $resp["headers"]["message"] = 'Not Found';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        }

    } else {

        // Если таблица не определена даем ошибку 400
        $resp["headers"]["status"] = '400 Bad Request';
        $resp["headers"]["code"] = 400;
        $resp["headers"]["message"] = 'Bad Request';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
    }
 
    echo json_encode($resp, JSON_PRETTY_PRINT);
 
    return $response->withStatus(200)->withHeader('Content-Type','application/json');
 
});

$app->get('/_patch/{resource:[a-z0-9_]+}[/{id:[0-9]+}]', function (Request $request, Response $response, array $args) {

    $resource = $request->getAttribute('resource');
    $id = intval($request->getAttribute('id'));
    $getParams = $request->getQueryParams();
    $getUri = $request->getUri();
    
    if (isset($resource)) {

        // Проверяем наличие главной базы если нет даем ошибку
        try {Validate::table($resource)->exists();
    
            $table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$resource.'.config.json'), true);
    
            if ($this->get('settings')['db']["access_key"] == true){
                $public_key = (isset($getParams['public_key'])) ? Db::clean($getParams['public_key']) : "none";
            } else {
                $public_key = $this->get('settings')['db']["public_key"];
            }

            if ($this->get('settings')['db']["public_key"] == Db::clean($public_key)) {

                // Если указан id обновляем одну запись
                if ($id >= 1) {
 
                    // Подключаем таблицу
                    $row = jsonDb::table($resource)->find($id);
                    parse_str(parse_url($getUri, PHP_URL_QUERY), $query);

                    // Разбираем параметры полученные в теле запроса
                    foreach($query as $key => $unit){
                        if (isset($key) && isset($unit)) {
                            if ($key != "key") {
                                if (array_key_exists($key, $table_config["schema"])) {
                                    $key = filter_var($key, FILTER_SANITIZE_STRING);
                                    $unit = filter_var($unit, FILTER_SANITIZE_STRING);
                                    if (is_numeric($unit)){$unit = intval($unit);}
                                    try {
                                        $row->{$key} = $unit;
                                    } catch(dbException $error){
                                        echo $error;
                                    }
                                }
                            }
                        }
                    }
                    // Сохраняем изменения
                    $row->save();

                    if ($row->id >= 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = $id;
                        $resp["request"]["query"] = "PATCH";
                        $resp["request"]["resource"] = $resource;
 
                        foreach($query as $key => $unit){
                            if (isset($key)) {
                                $item[$key] = $row->$key;
                            }
                        }
 
                        $resp["body"]["items"]["item"] = $item;
 
                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }
 
                } else {
                    // Обновляем несколько записей
                    parse_str(parse_url($getUri, PHP_URL_QUERY), $query);
                    
                    // Разбираем параметры полученные в теле запроса
                    foreach($query as $items){
                        $row = '';
                        foreach($items["item"] as $key => $unit){
                            if (isset($key) && isset($unit)) {
                                if ($key == "id") {
                                    $row = jsonDb::table($resource)->find($key);
                                }
                                if ($key != "key" && $key != "id" && array_key_exists($key, $table_config["schema"])) {

                                    $key = filter_var($key, FILTER_SANITIZE_STRING);
                                    $unit = filter_var($unit, FILTER_SANITIZE_STRING);
                                    if (is_numeric($unit)){$unit = intval($unit);}
                                    try {
                                        $row->{$key} = $unit;
                                    } catch(dbException $error){
                                        echo $error;
                                    }
                                }
                            }
                        }
                        // Сохраняем изменения
                        $row->save();
                    }

                    if ($row == 1) {
                        // Все ок. 202 Accepted «принято»
                        $resp["headers"]["status"] = "202 Accepted";
                        $resp["headers"]["code"] = 202;
                        $resp["headers"]["message"] = "Accepted";
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["id"] = '';
                        $resp["request"]["query"] = "PATCH";
                        $resp["request"]["resource"] = $resource;
                    } else {
                        // Не удалось создать. 501 Not Implemented «не реализовано»
                        $resp["headers"]["status"] = '501 Not Implemented';
                        $resp["headers"]["code"] = 501;
                        $resp["headers"]["message"] = 'Not Implemented';
                        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                        $resp["response"]["total"] = 0;
                    }

                }

            } else {
                // Доступ запрещен. Ключ доступа не совпадает.
                $resp["headers"]["status"] = '401 Unauthorized';
                $resp["headers"]["code"] = 401;
                $resp["headers"]["message"] = 'Access is denied';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["total"] = 0;
            }

        } catch(dbException $e){
            // Таблица не существует даем ошибку 404
            $resp["headers"]["status"] = '404 Not Found';
            $resp["headers"]["code"] = 404;
            $resp["headers"]["message"] = 'Not Found';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["total"] = 0;
        }

    } else {
        // Если таблица не определена даем ошибку 400
        $resp["headers"]["status"] = '400 Bad Request';
        $resp["headers"]["code"] = 400;
        $resp["headers"]["message"] = 'Bad Request';
        $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
        $resp["response"]["total"] = 0;
    }
 
    echo json_encode($resp, JSON_PRETTY_PRINT);
 
    return $response->withStatus(200)->withHeader('Content-Type','application/json');

});

$app->get('/{resource:[a-z0-9_]+}/_last_id', function (Request $request, Response $response, array $args) {
 
    $resource = $request->getAttribute('resource');
    $getParams = $request->getQueryParams();
 
    if (isset($resource)) {
        // Проверяем наличие главной базы если нет даем ошибку
        try {Validate::table($resource)->exists();
 
            if ($this->get('settings')['db']["access_key"] == true){
                $public_key = (isset($getParams['public_key'])) ? Db::clean($getParams['public_key']) : "none";
            } else {
                $public_key = $this->get('settings')['db']["public_key"];
            }

            if ($this->get('settings')['db']["public_key"] == Db::clean($public_key)) {
 
                // Сам запрос :) Куча кода ради одной строчки
                $last_id = jsonDb::table($resource)->lastId();
 
                if (isset($last_id)) {
                    // Все ок. 200 OK
                    $resp["headers"]["status"] = "200 OK";
                    $resp["headers"]["code"] = 200;
                    $resp["headers"]["message"] = "OK";
                    $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["source"] = "jsonapi";
                    $resp["response"]["last_id"] = $last_id;
                    $resp["request"]["query"] = "GET";
                    $resp["request"]["resource"] = $resource;
                } else {
                    $resp["headers"]["status"] = "404 Not Found";
                    $resp["headers"]["code"] = 404;
                    $resp["headers"]["message"] = "Not Found";
                    $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                    $resp["response"]["source"] = "jsonapi";
                    // База вернула 0 записей или null
                    $resp["response"]["total"] = 0;
                    $resp["request"]["query"] = "GET";
                    $resp["request"]["resource"] = $resource;
                }
            } else {
                // Ключ доступа не совпадает.
                $resp["headers"]["status"] = '403 Access is denied';
                $resp["headers"]["code"] = 403;
                $resp["headers"]["message"] = 'Access is denied';
                $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
                $resp["response"]["source"] = "jsonapi";
                $resp["response"]["total"] = 0;
                $resp["request"]["query"] = "GET";
                $resp["request"]["resource"] = $resource;
            }
        } catch(dbException $e) {
            // Такой таблицы не существует
            $resp["headers"]["status"] = '404 Not Found';
            $resp["headers"]["code"] = 404;
            $resp["headers"]["message"] = 'resource Not Found';
            $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
            $resp["response"]["source"] = "jsonapi";
            $resp["response"]["total"] = 0;
            $resp["request"]["query"] = "GET";
            $resp["request"]["resource"] = '';
        }
    }
 
    echo json_encode($resp, JSON_PRETTY_PRINT);
 
    return $response->withStatus(200)->withHeader('Content-Type','application/json');
 
});
 
$app->get('/{resource:[a-z0-9_]+}/_search', function (Request $request, Response $response, array $args) {
 
    $resource = $request->getAttribute('resource');
    $getParams = $request->getQueryParams();
 
    // Такой таблицы не существует
    $resp["headers"]["status"] = '404 Not Found';
    $resp["headers"]["code"] = 404;
    $resp["headers"]["message"] = 'resource Not Found';
    $resp["headers"]["message_id"] = $this->get('settings')['http-codes']."".$resp["headers"]["code"].".md";
    $resp["response"]["source"] = "jsonapi";
    $resp["response"]["total"] = 0;
    $resp["request"]["query"] = "SEARCH";
    $resp["request"]["resource"] = $resource;
 
    echo json_encode($resp, JSON_PRETTY_PRINT);
 
    return $response->withStatus(200)->withHeader('Content-Type','application/json');
});
// Запускаем Slim
$app->run();
 