<?php
namespace Src\poo;

use PDO;
use PDOException;

class Conexion
{
    private static $host = '127.0.0.1';
    private static $dbName = 'jugueteria_bd';
    private static $user = 'root'; // Cambiar según configuración
    private static $pass = '';    // Cambiar según configuración
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO("mysql:host=" . self::$host . ";dbname=" . self::$dbName, self::$user, self::$pass);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Error al conectar con la base de datos: " . $e->getMessage());
            }
        }
        return self::$connection;
    }
}
