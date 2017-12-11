<?php

namespace jsonDB;

use jsonDB\dbException;

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
        return json_decode(file_get_contents($this->getPath()), $assoc);
    }

    public final function put($data)
    {
        return file_put_contents($this->getPath(), json_encode($data));
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
