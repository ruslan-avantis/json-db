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
    * @var string db_path
    */
    protected $db_path;

    /**
    * global param db
    */
    private $key = '0'; // Передаем ключ шифрования файлов
    private $crypt = '0'; // true|false Шифруем или нет
    private $temp = '0'; // Очередь на запись. true|false
    private $api = '0'; // true|false Если установить false база будет работать как основное хранилище
    private $export = 'false';
    private $size = '50000';
    private $max_size = '1000000';
    private $dir_core = 'db.core';
    private $dir_log = 'db.log';
    private $structure = '0';
    private $auto_create = null;
 
    public function __construct($db_path)
    {
        $this->db_path = $db_path; // Директория в которой будет находится база данных
    }

    public function run()
    {
        // Запускаем контролирующие базу процессы
        $date = date("Y-m-d H:i:s");

        // Устанавливаем константу - Каталог базы данных
        define('JSON_DB_PATH', $this->db_path);
        define('JSON_DB_KEY', $this->key);
        define('JSON_DB_CRYPT', $this->crypt);
        define('JSON_DB_TEMP', $this->temp);
        define('JSON_DB_API', $this->api);
        define('JSON_DB_EXPORT', $this->export);
        define('JSON_DB_SIZE', $this->size);
        define('JSON_DB_MAX_SIZE', $this->max_size);
        define('JSON_DB_DB_PATH', $this->db_path);
        define('JSON_DB_DIR_CORE', str_replace('db.', $this->db_path, $this->dir_core));
        define('JSON_DB_DIR_LOG', str_replace('db.', $this->db_path, $this->dir_log));

        // Проверяем наличие каталога базы данных, если нет создаем
        if (!file_exists($this->db_path)){mkdir($this->db_path);}
 
        // Проверяем наличие таблицы queue
        try {
            Validate::table('queue')->exists();} catch(dbException $e){

            // Создаем таблицу queue
            jsonDb::create('queue', array(
                'db' => 'string',
                'resource' => 'string',
                'resource_id' => 'integer',
                'request' => 'string',
                'request_body' => 'string'
            ));

        }
 
        // Проверяем существуют ли необходимые каталоги, если нет создаем
        if (!file_exists(JSON_DB_DB_PATH)){mkdir(JSON_DB_DB_PATH);}
        if (!file_exists(JSON_DB_DIR_CORE)){mkdir(JSON_DB_DIR_CORE);}
        if (!file_exists(JSON_DB_DIR_LOG)){mkdir(JSON_DB_DIR_LOG);}
 
        // Если файла структуры базы данных нет, скачиваем его с github
        if (!file_exists(JSON_DB_DIR_CORE.'/db.json')) {
            if (isset($this->structure)) {
                file_put_contents(JSON_DB_DIR_CORE.'/db.json', file_get_contents($this->structure));
            } else {
                file_put_contents(JSON_DB_DIR_CORE.'/db.json', file_get_contents('https://raw.githubusercontent.com/pllano/structure-db/master/db.json'));
            }
        }
 
        if($this->auto_create == 1) {
        // Проверяем наличие файла повторно
        // Автоматически создает таблицы указанные в файле db.json если их нет
        if (file_exists(JSON_DB_DIR_CORE.'/db.json')) {
            // Получаем файл установки таблиц
            $data = json_decode(file_get_contents(JSON_DB_DIR_CORE.'/db.json'), true);
            $dataCount = count($data);
 
            if ($dataCount >= 1) {
                foreach($data as $unit)
                {
                    // Если существует поле table
                    if (isset($unit["table"])) {
 
                        // Проверяем существуют ли необходимые таблицы. Если нет создаем.
                        try {
                            Validate::table($unit["table"])->exists();
 
                            if ($unit["action"] == 'update' || $unit["action"] == 'create') {
                                // Обновляем параметры таблиц
                                // Если таблицы есть создаем зависимости
                                if (isset($unit["relations"])) {
                                    $unitCount = count($unit["relations"]);
                                    if ($unitCount >= 1) {
                                        foreach($unit["relations"] as $rel_key => $rel_value)
                                        {
                                            $has = $rel_value["type"];
                                            Relation::table($unit["table"])
                                                ->$has($rel_key)->localKey($rel_value["keys"]["local"])
                                                ->foreignKey($rel_value["keys"]["foreign"])->setRelation();
                                        }
                                    }
                                }
                            } elseif ($unit["action"] == 're-create') {
                                // Удаляем таблицы и создаем заново
 
                                jsonDb::remove($unit["table"]);
                                // Создаем таблицы
                                $unitCount = count($unit["schema"]);
 
                                if ($unitCount >= 1) {
                                    $row = array();

                                    foreach($unit["schema"] as $key => $value)
                                    {
                                        if (isset($key) && isset($value)) {
                                            $row[$key] = $value;
                                        }
                                    }
 
                                    jsonDb::create($unit["table"], $row);

                                }
                            } elseif ($unit["action"] == 'delete') {
                                // Удаляем таблицы
                                jsonDb::remove($unit["table"]);
                            }

                        } catch(dbException $e){
 
                            try {
                                Validate::table($unit["table"])->exists();
                            }  catch(dbException $e) {
 
                                if ($unit["action"] == 'create') {

                                    // Создаем таблицы
                                    $unitCount = count($unit["schema"]);

                                    if ($unitCount >= 1) {

                                        $row = array();
                                        foreach($unit["schema"] as $key => $value)
                                        {
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
        }
    
    }
 
    public function runApi() // Управление получением данных через API
    {
        // Если в настройки $this->api передано true база будет работать в режиме синхронизации
        // Данные будут получаться через API и будут синхронизироватся с базой
    }

    /**
    * @param true|false $temp
    */
    public function setTemp($temp)
    {
        if (is_numeric($temp)) {$temp = intval($temp);}
        if (is_float($temp)) {$temp = float($temp);}
        $this->temp = $temp;
    }

    /**
    * @param true|false $api
    */
    public function setApi($api)
    {
        if (is_numeric($api)) {$api = intval($api);}
        if (is_float($api)) {$api = float($api);}
        $this->api = $api;
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
        if (is_numeric($size)) {$size = intval($size);}
        if (is_float($size)) {$size = float($size);}
        $this->size = $size;
    }

    /**
    * @param integer $max_size
    */
    public function setMaxSize($max_size)
    {
        if (is_numeric($max_size)) {$max_size = intval($max_size);}
        if (is_float($max_size)) {$max_size = float($max_size);}
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
    * @param 'db.request' or uri
    */
    public function setDirRequest($dir_request)
    {
        $this->dir_request = $dir_request;
    }

    /**
    * @param key
    */
    public function setKey($key)
    {
        $this->key = $key;
    }
 
    /**
    * @param key
    */
    public function setAutoCreate($auto_create)
    {
        $this->auto_create = (int)$auto_create;
    }
 
    /**
    * @param crypt
    */
    public function setCrypt($crypt)
    {
        if (is_numeric($crypt)) {$crypt = intval($crypt);}
        if (is_float($crypt)) {$crypt = float($crypt);}
        $this->crypt = $crypt;
    }
 
    /**
    * @param structure
    */
    public function setStructure($structure)
    {
        $this->structure = $structure;
    }
 
    /**
    * @param structure
    */
    public function setPublicKey($public_key)
    {
        $this->public_key = $public_key;
    }

    // Генерация uid
    // По умолчанию длина 32 символа, если количество символов не передано в параметре $length
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

        $value = trim($value); // Убираем пробелы вначале и в конце
        $value = stripslashes($value); // Убираем слеши, если надо // Удаляет экранирование символов
        $value = strip_tags($value); // Удаляет HTML и PHP-теги из строки
        $value = htmlspecialchars($value, ENT_QUOTES); // Заменяем служебные символы HTML на эквиваленты // Преобразует специальные символы в HTML-сущности

        return $value;

    }

}
 