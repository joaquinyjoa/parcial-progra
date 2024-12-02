<?php
namespace Src\poo;

use PDO;

class Juguete
{
    public $id;
    public $marca;
    public $precio;
    public $path_foto;

    public static function getAll(): array
    {
        try {
            $db = Conexion::getConnection();
            $stmt = $db->query("SELECT id, marca, precio, path_foto FROM juguetes");
            return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve un array asociativo
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function save(): bool
    {
        try {
            $db = Conexion::getConnection();
            $stmt = $db->prepare("INSERT INTO juguetes (marca, precio, path_foto) VALUES (:marca, :precio, :path_foto)");
            $stmt->bindValue(':marca', $this->marca, PDO::PARAM_STR);
            $stmt->bindValue(':precio', $this->precio, PDO::PARAM_STR);
            $stmt->bindValue(':path_foto', $this->path_foto, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public static function deleteById(int $id): bool
    {
        try {
            $db = Conexion::getConnection();
            $stmt = $db->prepare("DELETE FROM juguetes WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function update(): bool
    {
        try {
            $db = Conexion::getConnection();
            $stmt = $db->prepare("UPDATE juguetes SET marca = :marca, precio = :precio, path_foto = :path_foto WHERE id = :id");
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':marca', $this->marca, PDO::PARAM_STR);
            $stmt->bindValue(':precio', $this->precio, PDO::PARAM_STR);
            $stmt->bindValue(':path_foto', $this->path_foto, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
}
