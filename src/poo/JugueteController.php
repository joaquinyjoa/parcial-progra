<?php
namespace Src\poo;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\poo\Juguete;

class JugueteController
{
    public static function getAll(Request $request, Response $response): Response
    {
        $juguetes = Juguete::getAll(); // Método estático en la clase Juguete

        if (!empty($juguetes)) {
            $response->getBody()->write(json_encode([
                'éxito' => true,
                'mensaje' => 'Listado de juguetes obtenido correctamente.',
                'dato' => $juguetes
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'No se encontraron juguetes.',
                'dato' => null
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(424);
        }
    }

    public static function add(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        
        // Obtener datos del JSON
        $jugueteJson = $data['juguete_json'] ?? null;
        if ($jugueteJson) {
            $jugueteData = json_decode($jugueteJson, true); // Decodifica el JSON
            $marca = $jugueteData['marca'] ?? null;
            $precio = $jugueteData['precio'] ?? null;
        } else {
            $marca = null;
            $precio = null;
        }

        // Procesar el archivo de foto
        $foto = $uploadedFiles['foto'] ?? null;

        // Validaciones
        if (!$marca || !$precio || !$foto) {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Faltan datos obligatorios o la foto no fue subida correctamente.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(418);
        }

        // Validar extensión de la foto
        $extension = pathinfo($foto->getClientFilename(), PATHINFO_EXTENSION);
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array(strtolower($extension), $extensionesPermitidas)) {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'La extensión del archivo no es válida. Se permiten solo imágenes JPG, PNG o GIF.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(415);
        }

        // Directorio de destino para las fotos
        $directorioFotos = __DIR__ . '/../fotos';  // Definimos la ruta de la carpeta 'fotos'

        // Comprobamos si la carpeta no existe
        if (!file_exists($directorioFotos)) {
            // Si no existe, la creamos
            mkdir($directorioFotos, 0777, true);  // 0777 otorga permisos de lectura, escritura y ejecución para todos los usuarios
        }
        // Nombre del archivo a guardar
        $nombreArchivo = $directorioFotos . "/{$marca}.{$extension}";

        // Guardar foto
        if ($foto && $foto->getError() === UPLOAD_ERR_OK) {
            // Mueve el archivo cargado al directorio de destino
            $foto->moveTo($nombreArchivo);
        } else {
            // Manejar el error de carga del archivo
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Error al cargar la foto.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(418);
        }

        // Guardar juguete en la base de datos
        $juguete = new Juguete();
        $juguete->marca = $marca;
        $juguete->precio = (float) $precio;
        $juguete->path_foto = $nombreArchivo;

        $resultado = $juguete->save(); // Método save() en la clase Juguete

        // Respuesta final según el resultado
        if ($resultado) {
            $response->getBody()->write(json_encode([
                'éxito' => true,
                'mensaje' => 'Juguete agregado correctamente.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Error al agregar el juguete.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(418);
        }
    }


    public static function update(Request $request, Response $response): Response
    {
        // Obtener los datos del juguete (JSON) desde el form-data
        $data = $request->getParsedBody();
        $jugueteJson = $data['toy'] ?? null;
        $uploadedFiles = $request->getUploadedFiles();

        if (!$jugueteJson) {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'No se proporcionaron datos del juguete.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(418);
        }

        // Decodificar el JSON
        $jugueteData = json_decode($jugueteJson, true); 
        $idJuguete = $jugueteData['id'] ?? null;
        $marca = $jugueteData['marca'] ?? null;
        $precio = $jugueteData['precio'] ?? null;

        // Obtener la foto del juguete (si se ha subido)
        $foto = $uploadedFiles['foto'] ?? null;

        // Validaciones
        if (!$idJuguete || !$marca || !$precio || !$foto) {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Faltan datos obligatorios o la foto no fue subida correctamente.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(418);
        }

        // Guardar foto
        $extension = pathinfo($foto->getClientFilename(), PATHINFO_EXTENSION);
        $nombreArchivo = __DIR__ . "/../fotos" . "/{$marca}_modificacion.{$extension}";
        $foto->moveTo($nombreArchivo);

        // Modificar el juguete en la base de datos
        $juguete = new Juguete();
        $juguete->id = $idJuguete; // Establecer el ID del juguete a modificar
        $juguete->marca = $marca;
        $juguete->precio = (float) $precio;
        $juguete->path_foto = $nombreArchivo;

        $resultado = $juguete->update(); // Método update() en la clase Juguete

        if ($resultado) {
            $response->getBody()->write(json_encode([
                'éxito' => true,
                'mensaje' => 'Juguete modificado correctamente.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Error al modificar el juguete.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(418);
        }
    }


}
