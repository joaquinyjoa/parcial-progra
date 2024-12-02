<?php
namespace Src\poo;

use Src\poo\Conexion;
use PDO;
use PDOException;

class Usuario
{
    public static function getAll() : array
    {
        try {
            $db = Conexion::getConnection();
            $query = $db->query("SELECT * FROM usuarios");
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public static function getByEmailAndPassword($correo, $clave)
    {
        try {
            $db = Conexion::getConnection();
            $stmt = $db->prepare("SELECT id, correo, nombre, apellido, clave, foto, perfil FROM usuarios WHERE correo = :correo");
            $stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && $usuario['clave'] === $clave) {
                unset($usuario['clave']); // Eliminar la clave antes de devolver el usuario
                return $usuario;
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
        return null;
    }

    // Método para agregar un nuevo usuario
    public static function alta($correo, $clave, $nombre, $apellido, $perfil, $fotoPath)
    {
        try {
            $db = Conexion::getConnection();
            $stmt = $db->prepare("INSERT INTO usuarios (correo, clave, nombre, apellido, perfil, foto) 
                                VALUES (:correo, :clave, :nombre, :apellido, :perfil, :foto)");

            $stmt->bindValue(':correo', $correo, PDO::PARAM_STR);
            $stmt->bindValue(':clave', $clave, PDO::PARAM_STR);
            $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindValue(':apellido', $apellido, PDO::PARAM_STR);
            $stmt->bindValue(':perfil', $perfil, PDO::PARAM_STR);
            $stmt->bindValue(':foto', $fotoPath, PDO::PARAM_STR); // Guardamos el path de la foto

            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
        return false;
    }
}
