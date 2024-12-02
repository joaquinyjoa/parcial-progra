<?php

namespace Src\poo;

use Psr\Http\Message\ResponseInterface; // Interfaz para las respuestas HTTP.
use Psr\Http\Server\RequestHandlerInterface; // Interfaz para manejar la solicitud.
use Psr\Http\Message\ServerRequestInterface as Request; // Interfaz para las solicitudes HTTP.
use Src\poo\Usuario; // Clase que maneja las operaciones relacionadas con usuarios.
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class MW
{
    /**
     * Middleware 1: Verificar si correo o clave están vacíos
     * Este middleware se asegura de que los campos `correo` y `clave` sean enviados y no estén vacíos.
     */
    public static function verificarCamposVacios(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Obtiene los datos enviados en el cuerpo de la solicitud (formato x-www-form-urlencoded o JSON).
        $uploadedData = $request->getParsedBody()['user'] ?? null;

        if (!$uploadedData) {
            // Si no se envió información, se retorna un mensaje de error con código 400 (Bad Request).
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'jwt' => null,
                'mensaje' => 'No se recibió información del usuario.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Decodifica los datos enviados como JSON.
        $parsedBody = json_decode($uploadedData, true);
        $correo = trim($parsedBody['correo'] ?? ''); // Elimina espacios en blanco del inicio y final del correo.
        $clave = trim($parsedBody['clave'] ?? '');  // Elimina espacios en blanco del inicio y final de la clave.

        if (empty($correo) || empty($clave)) {
            // Si `correo` o `clave` están vacíos, se retorna un mensaje de error con código 409 (Conflict).
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'jwt' => null,
                'mensaje' => 'Correo y clave son obligatorios.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        // Si los campos son válidos, continúa al siguiente middleware o controlador.
        return $handler->handle($request);
    }

    /**
     * Middleware 2: Verificar si correo y clave existen en la base de datos
     * Este middleware valida si las credenciales del usuario son correctas.
     */
    public static function verificarExistenciaUsuario(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Obtiene los datos enviados en el cuerpo de la solicitud.
        $uploadedData = $request->getParsedBody()['user'] ?? null;

        // Decodifica los datos enviados como JSON.
        $parsedBody = json_decode($uploadedData, true);
        $correo = trim($parsedBody['correo'] ?? ''); // Elimina espacios en blanco del inicio y final del correo.
        $clave = trim($parsedBody['clave'] ?? '');  // Elimina espacios en blanco del inicio y final de la clave.

        // Consulta a la base de datos utilizando el método estático `getByEmailAndPassword` de la clase `Usuario`.
        $usuario = Usuario::getByEmailAndPassword($correo, $clave);

        if (!$usuario) {
            // Si no se encuentra el usuario, se retorna un mensaje de error con código 403 (Forbidden).
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'jwt' => null,
                'mensaje' => 'Correo o clave incorrectos.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Si las credenciales son válidas, continúa al siguiente middleware o controlador.
        return $handler->handle($request);
    }

    // Método de instancia para verificar el token
    public function verificarToken(Request $request, ResponseInterface $response, $next): ResponseInterface
    {
        // Obtener el encabezado Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'No se recibió token de autenticación.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // El token debe estar en el formato 'Bearer <token>'
        $arr = explode(" ", $authHeader);
        if (count($arr) !== 2 || $arr[0] !== 'Bearer') {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Formato de token incorrecto.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $jwt = $arr[1]; // Extraer el token

        try {
            // Decodificar el token
            $secretKey = 'Greco.Joaquin'; // Clave secreta
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));

            // Token válido, pasar al siguiente middleware o controlador
            return $next($request, $response);

        } catch (Exception $e) {
            // Token inválido o expirado
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Token inválido o expirado.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
    }

    public static function filtrarJuguetesIDsImpares(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Obtiene la respuesta del controlador.
        // Obtiene la respuesta del controlador.
        $response = $handler->handle($request);
        $body = (string)$response->getBody();

        // Decodifica el contenido JSON en un array asociativo.
        $contenido = json_decode($body, true);

        // Verifica que los datos sean válidos.
        if (!is_array($contenido) || !isset($contenido['dato'])) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write('Error: No se pudo procesar la lista de juguetes.');
            return $response->withHeader('Content-Type', 'text/plain')->withStatus(500);
        }

        // Extrae los juguetes del campo "dato".
        $juguetes = $contenido['dato'];

        // Filtra los juguetes con IDs impares.
        $juguetesFiltrados = array_filter($juguetes, function ($juguete) {
            return isset($juguete['id']) && $juguete['id'] % 2 !== 0;
        });

        // Genera la tabla HTML.
        $tablaHTML = '<table border="1"><tr><th>ID</th><th>Marca</th><th>Precio</th></tr>';
        foreach ($juguetesFiltrados as $juguete) {
            $tablaHTML .= sprintf(
                '<tr><td>%d</td><td>%s</td><td>%.2f</td></tr>',
                $juguete['id'],
                htmlspecialchars($juguete['marca']),
                $juguete['precio']
            );
        }
        $tablaHTML .= '</table>';

        // Crea una nueva respuesta con el contenido HTML.
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write($tablaHTML);
        return $response->withHeader('Content-Type', 'text/html');
    }
}
