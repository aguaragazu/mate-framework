<?php

namespace Mate\Support;

use Traversable;

class Str
{
    /**
     * The cache of snake-cased words.
     *
     * @var array
     */
    protected static $snakeCache = [];

    /**
     * Convierte una cadena a minúsculas.
     *
     * @param string $value La cadena a convertir.
     * @return string La cadena en minúsculas.
     */
    public static function lower(string $value)
    {
        return mb_strtolower($value);
    }

    /**
     * Convierte una cadena a mayúsculas.
     *
     * @param string $value La cadena a convertir.
     * @return string La cadena en mayúsculas.
     */
    public static function upper(string $value)
    {
        return mb_strtoupper($value);
    }

    /**
     * Elimina espacios en blanco antes y después de una cadena.
     *
     * @param string $value La cadena a recortar.
     * @return string La cadena recortada.
     */
    public static function trim(string $value)
    {
        return trim($value);
    }

    /**
     * Elimina espacios en blanco adicionales dentro de una cadena.
     *
     * @param string $value La cadena a comprimir.
     * @return string La cadena comprimida.
     */
    public static function compress(string $value)
    {
        return preg_replace('/\s+/m', ' ', $value);
    }

    /**
     * Convierte la primera letra de una cadena a mayúsculas.
     *
     * @param string $value La cadena a modificar.
     * @return string La cadena con la primera letra en mayúscula.
     */
    public static function ucfirst(string $value)
    {
        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    /**
     * Convierte la primera letra de cada palabra de una cadena a mayúsculas.
     *
     * @param string $value La cadena a modificar.
     * @return string La cadena con las primeras letras en mayúsculas.
     */
    public static function ucwords(string $value)
    {
        return mb_convert_case($value, MB_CASE_TITLE);
    }

    /**
     * Reemplaza todas las ocurrencias de una cadena por otra en una cadena dada.
     *
     * @param string $value La cadena a buscar y reemplazar.
     * @param string $search La cadena a buscar.
     * @param string $replace La cadena con la que se reemplazará.
     * @return string La cadena con las sustituciones realizadas.
     */
    public static function replace(string $value, string $search, string $replace)
    {
        return str_replace($search, $replace, $value);
    }

    /**
     * Reemplaza todas las ocurrencias de una expresión regular por otra en una cadena dada.
     *
     * @param string $value La cadena a buscar y reemplazar.
     * @param string $pattern La expresión regular a buscar.
     * @param string $replace La cadena con la que se reemplazará.
     * @return string La cadena con las sustituciones realizadas.
     */
    public static function replaceFirst(string $value, string $pattern, string $replace)
    {
        return preg_replace($pattern, $replace, $value, 1);
    }

    /**
     * Elimina todos los caracteres especiales de una cadena.
     *
     * @param string $value La cadena a limpiar.
     * @return string La cadena sin caracteres especiales.
     */
    public static function slug(string $value)
    {
        $value = Str::lower($value);
        $value = Str::replaceFirst(' ', '-', $value);
        $value = Str::replaceFirst('-', '_', $value);
        $value = Str::replaceFirst('_+', '-', $value);
        $value = Str::replaceFirst('/\-+/', '-', $value);
        $value = trim($value, '-');

        return $value;
    }

    /**
     * Genera un nombre de archivo aleatorio.
     *
     * @param string $extension La extensión del archivo (por ejemplo, .jpg, .txt).
     * @return string El nombre de archivo aleatorio.
     */
    public static function randomFilename(string $extension = ''): string
    {
        $name = bin2hex(random_bytes(16));
        return $name . '.' . $extension;
    }

    /**
     * Genera una cadena aleatoria con letras minúsculas.
     *
     * @param int $length La longitud de la cadena aleatoria.
     * @return string La cadena aleatoria.
     */
    public static function random(int $length = 16): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    /**
     * Comprueba si una cadena es una URL válida.
     *
     * @param string $url La cadena a verificar.
     * @return bool True si la cadena es una URL válida, False en caso contrario.
     */
    public static function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Comprueba si una cadena es un correo electrónico válido.
     *
     * @param string $email La cadena a verificar.
     * @return bool True si la cadena es un correo electrónico válido, False en caso contrario.
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Limpia una cadena de caracteres no válidos para nombres de archivos.
     *
     * @param string $value La cadena a limpiar.
     * @return string La cadena limpia.
     */
    public static function sanitizeFilename(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $value);
    }

    /**
     * Convierte una cadena a snake_case (minúsculas con guiones bajos).
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * Convierte una cadena a camel_case (primera letra en mayúscula, siguientes en minúscula).
     *
     * @param string $value La cadena a convertir.
     * @return string La cadena en camel_case.
     */
    public static function camel(string $value): string
    {
        $value = preg_replace('/\s+/m', '', $value);
        $value = lcfirst($value);
        return ucwords($value, '_');
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|iterable<string>  $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        if (! is_iterable($needles)) {
            $needles = [$needles];
        }

        foreach ($needles as $needle) {
            if ((string) $needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a new stringable object from the given string.
     *
     * @param  string  $string
     * @return \Mate\Support\Stringable
     */
    public static function of($string)
    {
        return new Stringable($string);
    }

    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @param  string  $search
     * @param  iterable<string>  $replace
     * @param  string  $subject
     * @return string
     */
    public static function replaceArray($search, $replace, $subject)
    {
        if ($replace instanceof Traversable) {
            $replace = collect($replace)->all();
        }

        $segments = explode($search, $subject);

        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $result .= (array_shift($replace) ?? $search).$segment;
        }

        return $result;
    }
    
    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|iterable<string>  $needles
     * @param  bool  $ignoreCase
     * @return bool
     */
    public static function contains($haystack, $needles, $ignoreCase = false)
    {
        if ($ignoreCase) {
            $haystack = mb_strtolower($haystack);
        }

        if (! is_iterable($needles)) {
            $needles = (array) $needles;
        }

        foreach ($needles as $needle) {
            if ($ignoreCase) {
                $needle = mb_strtolower($needle);
            }

            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    public static function replaceLast($search, $replace, $subject)
    {
        $search = (string) $search;

        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param  string  $value
     * @param  string  $cap
     * @return string
     */
    public static function finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:'.$quoted.')+$/u', '', $value).$cap;
    }
}