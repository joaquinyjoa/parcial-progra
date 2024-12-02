<?php
namespace Src\poo;

use Src\poo\Usuario;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class UsuarioController
{
    public static function login(Request $request, Response $response): Response
    {
        // Los middlewares ya se encargaron de validar campos y verificar el usuario en la base de datos.

        // Obtener los datos del usuario que ya pasaron la validación del MW.
        $uploadedData = $request->getParsedBody()['user'] ?? null;
        $parsedBody = json_decode($uploadedData, true);
        $correo = $parsedBody['correo'];
        $clave = $parsedBody['clave'];

        // Recuperar el usuario de la base de datos.
        $usuario = Usuario::getByEmailAndPassword($correo, $clave);

        // Crear el JWT solo si el usuario es válido.
        unset($usuario['clave']); // Eliminar la clave antes de crear el JWT.

        $payload = [
            'id' => $usuario['id'],
            'correo' => $usuario['correo'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],  // Datos del usuario sin la clave.
            'foto' => $usuario['foto'],
            'role' => $usuario['perfil'],
            'alumno' => 'Joaquin Alfredo Greco',  // Nombre y apellido del alumno.
            'dni_alumno' => '46013501',  // DNI del alumno.
            'iat' => time(),  // Timestamp de creación.
            'exp' => time() + 120  // Expiración en 120 segundos.
        ];

        // Firmar el token con el apellido.nombre del alumno.
        $secretKey = 'Greco.Joaquin';  // Firma: apellido.nombre.
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        // Responder con el JWT y mensaje de éxito.
        $response->getBody()->write(json_encode([
            'éxito' => true,
            'jwt' => $jwt,
            'mensaje' => 'Login exitoso.'
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // Ruta para verificar el JWT enviado en el encabezado Authorization (Bearer token)
    public static function verificarJWT(Request $request, Response $response): Response
    {
        // Obtener el encabezado Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader) {
            // Retornar JSON con el error de autenticación
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'No se recibió token de autenticación.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // El token debe estar en el formato 'Bearer <token>'
        $arr = explode(" ", $authHeader);
        if (count($arr) != 2 || $arr[0] != 'Bearer') {
            // Retornar JSON con el error de formato de token
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Formato de token incorrecto.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $jwt = $arr[1]; // Obtener el token (el segundo elemento)

        try {
            // Clave secreta usada para firmar el token
            $secretKey = 'Greco.Joaquin';  // Usa la misma clave que usaste al crear el token

            // Decodificar el JWT
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));

            // Si el token es válido, respondemos con éxito
            $response->getBody()->write(json_encode([
                'éxito' => true,
                'mensaje' => 'Token válido.',
                'status' => 200
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (Exception $e) {
            // Si el token no es válido o ha expirado
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Token inválido o expirado.',
                'status' => 403
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
    }

    public static function getAll(Request $request, Response $response): Response
    {
        $usuarios = Usuario::getAll();
        $status = is_array($usuarios) ? 200 : 424;

        $response->getBody()->write(json_encode([
            'éxito' => $status === 200,
            'mensaje' => $status === 200 ? 'Listado obtenido correctamente.' : 'Error al obtener el listado.',
            'dato' => $status === 200 ? $usuarios : null
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    // Método para alta de usuario
    public static function altaUsuario(Request $request, Response $response): Response
    {
        $uploadedData = $request->getParsedBody()['usuario_json'] ?? null;
        $parsedBody = json_decode($uploadedData, true);
    
        // Extraemos los datos del usuario
        $correo = $parsedBody['correo'];
        $clave = $parsedBody['clave'];
        $nombre = $parsedBody['nombre'];
        $apellido = $parsedBody['apellido'];
        $perfil = $parsedBody['perfil']; // Puede ser 'propietario', 'supervisor', 'empleado'
        
        // Validar que los campos no estén vacíos
        if (empty($correo) || empty($clave) || empty($nombre) || empty($apellido) || empty($perfil)) {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Faltan campos obligatorios.',
                'status' => 418
            ]));
            return $response->withStatus(418)->withHeader('Content-Type', 'application/json');
        }
    
        // Guardar la foto
        $uploadedFile = $request->getUploadedFiles()['foto'] ?? null;
    
        if ($uploadedFile && $uploadedFile->getError() === UPLOAD_ERR_OK) {
            // Guardamos la foto con el nombre 'correo.extension'
            $ext = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            $filePath = __DIR__ . '/../fotos' . "/" .$correo . '.' . $ext;
            $uploadedFile->moveTo($filePath);
        } else {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'No se cargó ninguna foto.',
                'status' => 418
            ]));
            return $response->withStatus(418)->withHeader('Content-Type', 'application/json');
        }
    
        // Insertar en la base de datos
        $usuarioCreado = Usuario::alta($correo, $clave, $nombre, $apellido, $perfil, $filePath);
    
        if ($usuarioCreado) {
            $response->getBody()->write(json_encode([
                'éxito' => true,
                'mensaje' => 'Usuario creado exitosamente.',
                'status' => 200
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'éxito' => false,
                'mensaje' => 'Error al crear el usuario.',
                'status' => 418
            ]));
            return $response->withStatus(418)->withHeader('Content-Type', 'application/json');
        }
    }
    

    
}
