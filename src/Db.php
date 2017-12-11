<?php 
	
namespace jsonDB;
	
use jsonDB\Database as jsonDb;
use jsonDB\Validate;
use jsonDB\Relation;
use jsonDB\File;
use jsonDB\dbException;
	
class Db {
		
	/**
	* global dir db
	* @var string bd_path
	*/
	protected $bd_path;
		
	/**
	* global param db
	*/
	private $temp			= true; //	Очередь на запись. true|false
	private $api			= true; //	true|false Если установить false база будет работать как основное хранилище
	private $cached			= true; //	Кеширование. true|false
	private $cache_lifetime		= 30; // Min
	private $export			= 'false';
	private $size			= 50000;
	private $max_size		= 1000000;
	private $dir_core		= 'db.core';
	private $dir_temp		= 'db.temp';
	private $dir_log		= 'db.log';
	private $dir_cached		= 'db.cached';
	private $dir_request		= 'db.request';
		
	public function __construct($bd_path)
	{
		$this->bd_path = $bd_path; // Директория в которой будет находится база данных
	}
		
	public function run()
	{
		//	Запускаем контролирующие базу процессы
		$date = date("Y-m-d H:i:s");
		
		//	Устанавливаем константу - Каталог базы данных
		define('JSON_DB_PATH', $this->bd_path);
		
		//	Проверяем наличие каталога базы данных, если нет создаем
		if (!file_exists($this->bd_path)){mkdir($this->bd_path);}
		
		// Проверяем наличие главной таблицы если нет создаем
		try {Validate::table('db')->exists();
			
		// Обновляем таблицу конфигурации db из параметров (new Db($bd_path, $temp, $api, $cached))->run();
		$update = jsonDb::table('db')->find(1); // Edit with ID 1
		$update->bd_path		= $this->bd_path;
		$update->cached			= $this->cached;
		$update->temp			= $this->temp;
		$update->api			= $this->api;
		$update->cache_lifetime		= $this->cache_lifetime;
		$update->export			= $this->export;
		$update->size			= $this->size;
		$update->max_size		= $this->max_size;
		$update->dir_core		= $this->dir_core;
		$update->dir_temp		= $this->dir_temp;
		$update->dir_log		= $this->dir_log;
		$update->dir_cached		= $this->dir_cached;
		$update->dir_request		= $this->dir_request;
		$update->save();
			
		} catch(dbException $e){
			
		//	Создаем главную таблицу конфигурации db
		jsonDb::create('db', array(
		'id'			=> 'integer',
		'type'			=> 'string',
		'table'			=> 'string',
		'version'		=> 'string',
		'time'			=> 'string',
		'user_key'		=> 'string',
		'password'		=> 'string',
		'temp'			=> 'boolean',
		'api'			=> 'boolean',
		'cached'		=> 'boolean',
		'cache_lifetime'	=> 'integer',
		'export'		=> 'string',
		'size'			=> 'integer',
		'max_size'		=> 'integer',
		'bd_path'		=> 'string',
		'dir_core'		=> 'string',
		'dir_temp'		=> 'string',
		'dir_log'		=> 'string',
		'dir_cached'		=> 'string',
		'dir_request'		=> 'string'
		));
			
		}
		
		// Проверяем наличие таблицы cached
		try {Validate::table('cached')->exists();} catch(dbException $e){
			
		//	Создаем таблицу cached
		jsonDb::create('cached', array(
		'cached_count'	=> 'integer',
		'cached_uri'	=> 'string',
		'cached_file'	=> 'string',
		'cached_time'	=> 'string'
		));
			
		}
		
		try {jsonDb::table('db')->find(1);} catch(dbException $e){
			
		//	Создаем основную запись в главной таблице
		$row = jsonDb::table('db');
		$row->type		= 'root';
		$row->table		= 'root';
		$row->version		= '1.0.1';
		$row->time		= $date;
		$row->user_key		= $this->randomUid();
		$row->password		= $this->randomUid();
		$row->temp		= $this->temp;
		$row->api		= $this->api;
		$row->cached		= $this->cached;
		$row->cache_lifetime	= $this->cache_lifetime;
		$row->export		= $this->export;
		$row->size		= $this->size;
		$row->max_size		= $this->max_size;
		$row->bd_path		= $this->bd_path;
		$row->dir_core		= $this->dir_core;
		$row->dir_temp		= $this->dir_temp;
		$row->dir_log		= $this->dir_log;
		$row->dir_cached	= $this->dir_cached;
		$row->dir_request	= $this->dir_request;
		$row->save();
			
		//	$row->user->name = $this->api;
		//	Добавление записи в связанную таблицу user с автоматической привязкой id
		}
		
		//	Читаем главную таблицу
		$table = jsonDb::table('db')->find(1);
		
		//	Создаем константы
		define('JSON_DB_TEMP', $table->temp);
		define('JSON_DB_API', $table->api);
		define('JSON_DB_CACHET', $table->cached);
		define('JSON_DB_USER_KEY', $table->user_key);
		define('JSON_DB_PASSWORD', $table->password);
		define('JSON_DB_EXPORT', $table->export);
		define('JSON_DB_SIZE', $table->size);
		define('JSON_DB_MAX_SIZE', $table->max_size);
		define('JSON_DB_CACHE_LIFE_TIME', $table->cache_lifetime);
		define('JSON_DB_bd_path', $table->bd_path);
		define('JSON_DB_DIR_CORE', str_replace('db.', $table->bd_path, $table->dir_core));
		define('JSON_DB_DIR_TEMP', str_replace('db.', $table->bd_path, $table->dir_temp));
		define('JSON_DB_DIR_LOG', str_replace('db.', $table->bd_path, $table->dir_log));
		define('JSON_DB_DIR_CACHET', str_replace('db.', $table->bd_path, $table->dir_cached));
		define('JSON_DB_DIR_REQUEST', str_replace('db.', $table->bd_path, $table->dir_request));
		
		//	Проверяем существуют ли необходимые каталоги, если нет создаем
		if (!file_exists(JSON_DB_bd_path)){mkdir(JSON_DB_bd_path);}
		if (!file_exists(JSON_DB_DIR_CORE)){mkdir(JSON_DB_DIR_CORE);}
		if (!file_exists(JSON_DB_DIR_TEMP)){mkdir(JSON_DB_DIR_TEMP);}
		if (!file_exists(JSON_DB_DIR_LOG)){mkdir(JSON_DB_DIR_LOG);}
		if (!file_exists(JSON_DB_DIR_CACHET)){mkdir(JSON_DB_DIR_CACHET);}
		if (!file_exists(JSON_DB_DIR_REQUEST)){mkdir(JSON_DB_DIR_REQUEST);}
		
		//	Автоматически создает таблицы указанные в файле db.json если их нет
		if (file_exists(JSON_DB_DIR_CORE.'/db.json')){
			
			//	Получаем файл установки таблиц
			$data = json_decode(file_get_contents(JSON_DB_DIR_CORE.'/db.json'), true);
			$dataCount = count($data);
			
			if ($dataCount >= 1) {
				foreach($data as $unit){
					//	Если существует поле table
					if (isset($unit["table"])) {
					
						//	Проверяем существуют ли необходимые таблицы. Если нет создаем.
						try {Validate::table($unit["table"])->exists();
						
							if ($unit["action"] == 'update' || $unit["action"] == 'create') {
							//	Обновляем параметры таблиц
							//	Если таблицы есть создаем зависимости
							$unitCount = count($unit["relations"]);
								if ($unitCount >= 1) {
									foreach($unit["relations"] as $rel_key => $rel_value){
										$has = $rel_value["type"];
										Relation::table($unit["table"])
											->$has($rel_key)->localKey($rel_value["keys"]["local"])
											->foreignKey($rel_value["keys"]["foreign"])->setRelation();
									}
								}
							}
						
							elseif ($unit["action"] == 're-create') {
							//	Удаляем таблицы и создаем заново
						
							jsonDb::remove($unit["table"]);
							//	Создаем таблицы
							$unitCount = count($unit["schema"]);
						
								if ($unitCount >= 1) {
									$row = array();
									foreach($unit["schema"] as $key => $value){
										if (isset($key) && isset($value)) {
										$row[$key] = $value;
										}
									}
							
									jsonDb::create($unit["table"], $row);
							
								}
							}
						
							elseif ($unit["action"] == 'delete') {
								//	Удаляем таблицы 
								jsonDb::remove($unit["table"]);
							}
						
						} catch(dbException $e){
						
							if ($unit["action"] == 'create') {
							
								//	Создаем таблицы
								$unitCount = count($unit["schema"]);
								
								if ($unitCount >= 1) {
									$row = array();
									
									foreach($unit["schema"] as $key => $value){
										if (isset($key) && isset($value)) {
										$row[$key] = $value;
										}
									}
							
									jsonDb::create($unit["table"], $row);
								}
							}
						
						}
					
					}
				}
			}
			
		}
		
	}
		
	public static function cacheReader($uri) // Читает кеш или удаляет кеш если время жизни просрочено
	{
		if (JSON_DB_CACHET == true) {
			
			$row = jsonDb::table('cached')->where('cached_uri', '=', $uri)->find();
			
			$rowCount = count($row);
			if ($rowCount >= 1) {
			
				$time = JSON_DB_CACHE_LIFE_TIME * 60; // minutes * sec
				$cached_time = date('Y-m-d h:i:s', $row->cached_time + $time);
				
					if (strtotime($cached_time) <= strtotime(date("Y-m-d H:i:s"))){
				
						return json_decode(file_get_contents($row->cached_file), true);
					
					} else {
				
						unlink($row->cached_file);
						jsonDb::table('cached')->find($row->id)->delete();
					
						return null;
					
					}
					
				} else {
			
					return null;
				
				}
			
			} else {
			
				$table = jsonDb::table('cached')->findAll();
				$tableCount = count($table);
			
				if ($tableCount >= 1) {
					foreach($table as $row)
					{
						unlink($row->cached_file);
					}
				}
			
				jsonDb::table('cached')->delete();
			
				return null;
				
			}
	}
		
	public static function cacheWriter($uri, $arr) // Создает кеш
	{
		$file_name = \jsonDB\Db::randomUid();
			
		file_put_contents(JSON_DB_DIR_CACHET.'/'.$file_name.'.json', json_encode($arr));
			
		$row = jsonDb::table('cached');
		$row->cached_count = 0;
		$row->cached_uri = $uri;
		$row->cached_file = JSON_DB_DIR_CACHET.'/'.$file_name.'.json';
		$row->cached_time = date("Y-m-d H:i:s");
		$row->save();
			
		return $row;
			
	}
		
	public static function dbReader() // Управляет записью в базу
	{
	 // Если в настройки $this->temp передано true при записи в базу будет использоваться очередь на запись
	 // Когда файл таблицы заблокирован для записи база создаст файл в папке temp и запишет в таблицу при первой возможности
	 // Это компромис между скоростью работы и актальностью данных
	}
		
	public function runApi() // Управление получением данных через API
	{
		//	Если в настройки $this->api передано true база будет работать в режиме синхронизации
		//	Данные будут получаться через API и будут синхронизироватся с базой
	}
		
	/**
	* @param true|false $temp
	*/
	public function setTemp($temp)
	{
		$this->temp = $temp;
	}
		
	/**
	* @param true|false $api
	*/
	public function setApi($api)
	{
		$this->api = $api;
	}
		
	/**
	* @param true|false $cached
	*/
	public function setCached($cached)
	{
		$this->cached = $cached;
	}
		
	/**
	* @param Min $cache_lifetime
	*/
	public function setCacheLifetime($cache_lifetime)
	{
		$this->cache_lifetime = $cache_lifetime;
	}
		
	/**
	* @param false|uri $export
	*/
	public function setExport($export)
	{
		$this->export = $export;
	}
		
	/**
	* @param integer $size
	*/
	public function setSize($size)
	{
		$this->size = $size;
	}
		
	/**
	* @param integer $max_size
	*/
	public function setMaxSize($max_size)
	{
		$this->max_size = $max_size;
	}
		
	/**
	* @param 'db.core' or uri
	*/
	public function setDirCore($dir_core)
	{
		$this->dir_core = $dir_core;
	}
		
	/**
	* @param 'db.temp' or uri
	*/
	public function setDirTemp($dir_temp)
	{
		$this->dir_temp = $dir_temp;
	}
		
	/**
	* @param 'db.log' or uri
	*/
	public function setDirLog($dir_log)
	{
		$this->dir_log = $dir_log;
	}
		
	/**
	* @param 'db.cached' or uri
	*/
	public function setDirCached($dir_cached)
	{
		$this->dir_cached = $dir_cached;
	}
		
	/**
	* @param 'db.request' or uri
	*/
	public function setDirRequest($dir_request)
	{
		$this->dir_request = $dir_request;
	}
		
	//	Генерация uid
	//	По умолчанию длина 32 символа, если количество символов не передано в параметре $length
	public static function randomUid($length = 16)
	{
		if(!isset($length) || intval($length) <= 8 ){
			$length = 16;
		}
		
		if (function_exists('random_bytes')) {
			return bin2hex(random_bytes($length));
		}
		
		if (function_exists('mcrypt_create_iv')) {
			return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
		}
		
		if (function_exists('openssl_random_pseudo_bytes')) {
			return bin2hex(openssl_random_pseudo_bytes($length));
		}
	}
		
	// Функция клинер. Усиленная замена htmlspecialchars
	public static function clean($value = "") {
		
		// Убираем пробелы вначале и в конце
		$value = trim($value);
		
		// Убираем слеши, если надо
		// Удаляет экранирование символов
		$value = stripslashes($value); 
		
		// Удаляет HTML и PHP-теги из строки
		$value = strip_tags($value); 
		
		// Заменяем служебные символы HTML на эквиваленты 
		// Преобразует специальные символы в HTML-сущности
		$value = htmlspecialchars($value, ENT_QUOTES); 
		
		return $value;
		
	}
		
}
	
