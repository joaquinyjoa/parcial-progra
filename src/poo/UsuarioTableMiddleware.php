<?php
namespace Src\poo;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\poo\Usuario;

class UsuarioTableMiddleware
{
    public static function generateUserTable(Request $request, Response $response): Response
    {
        // Obtener el encabezado de autorización
        $authorizationHeader = $request->getHeader('Authorization');
        if (empty($authorizationHeader)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'No Authorization header provided',
                'status' => 403
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        // Obtener el token del encabezado
        $token = str_replace('Bearer ', '', $authorizationHeader[0]);

        try {
            // Clave secreta para decodificar el JWT
            $secretKey = 'Greco.Joaquin';

            // Decodificar el JWT
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

            // Verificar que el token contenga el rol esperado
            if (!isset($decoded->role) || $decoded->role !== 'propietario') {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No tiene permisos de propietario',
                    'status' => 403
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            // Obtener todos los usuarios de la base de datos
            $usuarios = Usuario::getAll(); // Asegúrate de que este método esté implementado en la clase Usuario

            // Si no hay usuarios, retornar error
            if (empty($usuarios)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'No hay usuarios disponibles',
                    'status' => 404
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Generar la tabla HTML con los usuarios
            $html = '<table border="1">';
            $html .= '<thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Foto</th><th>Rol</th></tr></thead><tbody>';

            foreach ($usuarios as $usuario) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($usuario['id']) . '</td>';
                $html .= '<td>' . htmlspecialchars($usuario['nombre']) . ' ' . htmlspecialchars($usuario['apellido']) . '</td>';
                $html .= '<td>' . htmlspecialchars($usuario['correo']) . '</td>';
                $html .= '<td><img src="' . htmlspecialchars($usuario['foto']) . '" alt="Foto" width="50" height="50"></td>';
                $html .= '<td>' . htmlspecialchars($usuario['perfil']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';

            // Escribir la tabla HTML en la respuesta
            $response->getBody()->write($html);
            return $response->withHeader('Content-Type', 'text/html')->withStatus(200);

        } catch (\Exception $e) {
            // Error al decodificar el token
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Token inválido o expirado',
                'status' => 403
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
    }
}
