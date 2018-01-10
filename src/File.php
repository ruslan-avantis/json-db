<?php
 
namespace jsonDB;

use jsonDB\dbException;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;

/**
 * File managing class
 *
 * @category Helpers

 */
class File implements FileInterface {

    /**
     * File name
     * @var string
     */
    protected $name;

    /**
     * File type (data|config)
     * @var string
     */
    protected $type;

    public static function table($name)
    {
        $file = new File;
        $file->name = $name;

        return $file;
    }

    public final function setType($type)
    {
        $this->type = $type;
    }

    public final function getPath()
    {
        if (!defined('JSON_DB_PATH'))
        {
            throw new dbException('Please define constant JSON_DB_PATH (check README.md)');
        }
        else if (!empty($this->type))
        {
            return JSON_DB_PATH . $this->name . '.' . $this->type . '.json';
        }
        else
        {
            throw new dbException('Please specify the type of file in class: ' . __CLASS__);
        }
    }

    public final function get($assoc = false)
    {
    
        $getPath = file_get_contents($this->getPath());
        
        // Если ключ шифрования установлен расшифровываем
        if (defined('JSON_DB_KEY') && JSON_DB_CRYPT == true){

            try {
            $decrypt = Crypto::decrypt($getPath, Key::loadFromAsciiSafeString(JSON_DB_KEY));
            } catch(WrongKeyOrModifiedCiphertextException $ex){
            // Если файл еще не закодирован кодируем
            $data = json_decode($getPath, $assoc);
            file_put_contents($this->getPath(), Crypto::encrypt(json_encode($data), Key::loadFromAsciiSafeString(JSON_DB_KEY)));
            $newPath = Crypto::decrypt(file_get_contents($this->getPath()), Key::loadFromAsciiSafeString(JSON_DB_KEY));
            $decrypt = $newPath;
            }
            return json_decode($decrypt, $assoc);
    
        } else {
            // Если ключ шифрования не установлен не шифруем и расшифровываем все и пересохраняем
            try {
            return json_decode($getPath, $assoc);
            } catch(dbException $e){
                
                if (defined('JSON_DB_KEY')){
                    $decrypt = Crypto::decrypt($getPath, Key::loadFromAsciiSafeString(JSON_DB_KEY));
                    file_put_contents($this->getPath(), $decrypt);
                    return json_decode($decrypt, $assoc);
                } else {
                    return json_decode($getPath, $assoc);
                }
                
            }
        }
        
    }

    public final function put($data)
    {
        // Если ключ шифрования установлен шифруем
        if (defined('JSON_DB_KEY') && JSON_DB_CRYPT == true){
            try {
                $getPath = Crypto::encrypt(json_encode($data), Key::loadFromAsciiSafeString(JSON_DB_KEY));
            } catch(WrongKeyOrModifiedCiphertextException $ex){
                $getPath = json_encode($data);
            }
        } else {
            $getPath = json_encode($data);
        }
        
        if ($getPath != null || $getPath != "") {
            return file_put_contents($this->getPath(), $getPath);
        } else {
            return null;
        }
    }

    public final function exists()
    {
        return file_exists($this->getPath());
    }

    public final function remove()
    {
        $type = ucfirst($this->type);
        if ($this->exists())
        {
            if (unlink($this->getPath()))
                return TRUE;

            throw new dbException($type . ': Deleting failed');
        }

        throw new dbException($type . ': File does not exists');
    }

}
 
