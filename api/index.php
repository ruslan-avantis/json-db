<?php
	if (PHP_SAPI == 'cli-server') {
		$url  = parse_url($_SERVER['REQUEST_URI']);
		$file = __DIR__ . $url['path'];
		if (is_file($file)) {return false;}
	}
	
	// !!! Указываем директорию где будет храниться json db !!!
	$_db = __DIR__ . '/../../_db_json/';
	
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
	
	// Создаем ключ доступа
	if (!file_exists($_db . 'core/key_db.txt')){
		$ajax_key = Key::createNewRandomKey();
		$key_db = $ajax_key->saveToAsciiSafeString();
		file_put_contents($_db . 'core/key_db.txt', $key_db);
	}
	
	// Запускаем json db
	$db = new Db($_db);
	$db->setCached(true);
	$db->setCacheLifetime(5);
	$db->setTemp(true);
	$db->setApi(true);
	$db->run();
	
	// Конфигурация
	$config = array();
	$config['settings']['db']['dir'] = $_db;
	$config['settings']['db']['key_cryp'] = Key::loadFromAsciiSafeString(file_get_contents($_db . 'core/key_db.txt', true));
	$config['settings']['db']['key'] = file_get_contents($_db . 'core/key_db.txt', true);
	$config['settings']['db']['access_key'] = false;
	$config['settings']['displayErrorDetails'] = true;
	$config['settings']['addContentLengthHeader'] = false;
	$config['settings']['determineRouteBeforeAppMiddleware'] = true;
	$config['settings']['debug'] = true;
	
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
		$param_key = (isset($param['key'])) ? Db::clean($param['key']) : null;
		
		if ($param_key == $this->get('settings')['db']["key"]) {
			$resp["status"] = "OK";
			$resp["code"] = 200;
			$resp["message"] = 'db_json_api works! To work, you need a key.';
			echo json_encode($resp, JSON_PRETTY_PRINT);
			} else {
			$resp["status"] = "OK";
			$resp["code"] = 200;
			$resp["message"] = 'db_json_api works!';
			echo json_encode($resp, JSON_PRETTY_PRINT);
		}
		
		return $response->withStatus(200)->withHeader('Content-Type','application/json');
		
	});
	
	$app->get('/{table}[/{id}]', function (Request $request, Response $response, array $args) {
		
		//	https://ru.pllano.com/db_json_api/property?offset=250&limit=5
		
		$table_name = $request->getAttribute('table');
		$id = $request->getAttribute('id');
		$getParams = $request->getQueryParams();
		$getUri = $request->getUri();
		
		if ($this->get('settings')['db']["access_key"] == true){
			$param_key = (isset($getParams['key'])) ? Db::clean($getParams['key']) : "none";
			} else {
			$param_key = $this->get('settings')['db']["key"];
		}
		
		if ($this->get('settings')['db']["key"] == $param_key) {
			
			$resp = array();
			
			if (isset($table_name)) {
				
				// Проверяем наличие главной базы если нет даем ошибку
				try {
					Validate::table($table_name)->exists();
					
					$table_config = json_decode(file_get_contents($this->get('settings')['db']["dir"].'/'.$table_name.'.config.json'), true);
					
					if (parse_url($getUri, PHP_URL_QUERY)) {$url_query = '?'.parse_url($getUri, PHP_URL_QUERY);} else {$url_query = '';}
					$url_path = parse_url($getUri, PHP_URL_PATH);
					
					//	Формируем url для работы с кешем
					$cacheUri = $url_path.''.$url_query;
					
					//	Читаем данные в кеше
					$cacheReader = Db::cacheReader($cacheUri);
					if ($cacheReader == null) { // Начало
						
						if (isset($id)) {
							$res = jsonDb::table($table_name)->where('id', '=', $id)->findAll(); // https://ru.pllano.com/db_json_api/property/17004
							
							//print_r($res);
							
							$resCount = count($res);
							if ($resCount == 1) {
								
								$resp["headers"]["status"] = "200 OK";
								$resp["headers"]["code"] = 200;
								$resp["headers"]["message"] = "OK";
								$resp["headers"]["message_id"] = 0;
								$resp["response"]["source"] = "db";
								$resp["response"]["total"] = $resCount;
								$resp["request"]["query"] = "GET";
								$resp["request"]["model"] = $table_name;
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
								$resp["headers"]["message_id"] = 1;
								$resp["response"]["source"] = "db";
								$resp["response"]["total"] = 0;
								$resp["request"]["query"] = "GET";
								$resp["request"]["model"] = $table_name;
								$resp["request"]["id"] = $id;
								$resp["body"]["items"]["item"] = "[]";
							}
							
						}
						else {
							
							// Указываем таблицу
							$count = jsonDb::table($table_name);
							$res = jsonDb::table($table_name);
							
							parse_str(parse_url($getUri, PHP_URL_QUERY), $query);
							
							//print_r($query);
							$quertyCount = count($query);
							if ($quertyCount >= 1) {
								
								$resp["headers"]["status"] = "200 OK";
								$resp["headers"]["code"] = 200;
								$resp["headers"]["message"] = "OK";
								$resp["headers"]["message_id"] = "";
								
								if (isset($query["JSONPath"]) || isset($query["jsonpath"])) {
									//	https://ru.pllano.com/db_json_api/vm_product?JSONPath="$..[?(@.id=126333)]"
									//	https://ru.pllano.com/db_json_api/property?JSONPath="$..[?(@.id=17004)]"
									if (isset($query["JSONPath"])) {$unit = $query["JSONPath"];	}
									if (isset($query["jsonpath"])) {$unit = $query["jsonpath"];	}
									$unit = str_replace('"', '', $unit);
									$file = $this->get('settings')['db']["dir"].''.$table_name.'.data.json';
									$data = json_decode(file_get_contents($file));
									$resp["items"] = (new JSONPath($data))->find($unit);
								}
								
								if (isset($query["JmesPath"]) || isset($query["jmespath"])) {
									//	https://ru.pllano.com/db_json_api/property?jmespath=[17004]
									if (isset($query["JmesPath"])) {$unit = $query["JmesPath"];}
									if (isset($query["jmespath"])) {$unit = $query["jmespath"];	}
									$unit = str_replace('"', '', $unit);
									$file = $this->get('settings')['db']["dir"].''.$table_name.'.data.json';
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
												
												//print_r($resp);
												//print_r(array_keys($table_config["schema"]));
												//if (in_array($key, $table_config["schema"])) {}
												
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
								
								//	Записываем данные в кеш
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
					$resp["headers"]["status"] = '403';
					$resp["headers"]["code"] = 403;
					$resp["headers"]["message"] = 'Access is denied';
					$resp["response"]["total"] = 0;
				}
				
			}
			else {
				// Доступ запрещен. Название таблицы не задано.
				$resp["headers"]["status"] = '403';
				$resp["headers"]["code"] = 403;
				$resp["headers"]["message"] = 'Access is denied';
				$resp["response"]["total"] = 0;
			}
			
		}
		else {
			// Доступ запрещен. Ключ доступа не совпадает.
			$resp["headers"]["status"] = '403';
			$resp["headers"]["code"] = 403;
			$resp["headers"]["message"] = 'Access is denied';
			$resp["response"]["total"] = 0;
		}
		
		echo json_encode($resp, JSON_PRETTY_PRINT);
		return $response->withStatus(200)->withHeader('Content-Type','application/json');
		
	});
	
	$app->post('/{table}', function (Request $request, Response $response, array $args) {
		
		// В разработке
		$table = $request->getAttribute('table');
		$param = $request->getQueryParams();
		$post = $request->getParsedBody();
		$post_lang = filter_var($post["id"], FILTER_SANITIZE_STRING);
		
	});
	
	$app->put('/{table}[/{id}]', function (Request $request, Response $response, array $args) {
		
		// В разработке
		$table = $request->getAttribute('table');
		$param = $request->getQueryParams();
		
	});
	
	$app->delete('/{table}[/{id}]', function (Request $request, Response $response, array $args) {
		
		// В разработке
		$table = $request->getAttribute('table');
		$param = $request->getQueryParams();
		
	});
	
	// Запускаем Slim
	$app->run();
