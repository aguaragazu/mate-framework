<?php
namespace Mate\Auth\Authenticators;

use Firebase\JWT\JWT;
use Mate\Auth\Authenticatable;
use Exception;
use Mate\Crypto\Bcrypt;
use Mate\Http\Request;
use Mate\Support\Facades\User;

class JwtAuthenticator implements Authenticator {
    protected $key; // La clave secreta para el JWT

    public function __construct($key = NULL) {
        $this->key = $key ?? env('JWT_SECRET');
    }

    public function login(Authenticatable $authenticatable) {
        $payload = [
            'iss' => env('APP_URL'), // Emisor
            'iat' => time(), // Tiempo en que el JWT fue emitido
            'exp' => time() + 3600, // Tiempo de expiración del token (1 hora)
            'sub' => $authenticatable->id(), // Sujeto del JWT
            'data' => $authenticatable->getJwtIdentity(), // Data del Sujeto 
        ];

        return JWT::encode($payload, $this->key, 'HS256');
    }

    public function logout(Authenticatable $authenticatable) {
        // El logout en JWT se maneja simplemente dejando que el token expire, 
        // o invalidándolo en el cliente.
    }

    public function isAuthenticated(Authenticatable $authenticatable): bool {
        // Este método puede ser complicado porque los JWT no mantienen estado. 
        // Dejarlo sin implementar o repensar su lógica.
        return false;
    }

    public function resolve(): ?Authenticatable {
        try {
            $request = app()->make(Request::class);
            $token = $this->getTokenFromHeader($request);
            $decoded = JWT::decode($token, $this->key);
            return User::find($decoded->sub);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Extracts the JWT token from the request headers.
     *
     * @param Request $request The HTTP request object.
     * @return string|null Returns the token if it exists and is formatted correctly, null otherwise.
     */
    protected function getTokenFromHeader(Request $request): ?string
    {
        $authorizationHeader = $request->headers('Authorization');

        if (is_null($authorizationHeader)) {
            return null;
        }

        // Using a regular expression to extract the token
        if (preg_match('/^Bearer\s+(\S+)/i', $authorizationHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function checkPassword($inputPassword, Authenticatable $authenticatable)
    {
        $hasher = new Bcrypt();
        return $hasher->verify($inputPassword, $authenticatable->password);
    }

}