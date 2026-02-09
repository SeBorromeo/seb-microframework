<?php namespace Sebastian\MicroFramework\Routing\Lib;






class PathUtils {
    /**
     * Escape a regular expression string.
     */
    private static function escape(string $str): string {
        return preg_replace('#[\#.+*?^${}()[\]|/\\\\]#', '\\\\$0', $str);
    }
    public static function decodeParam(string $val): string {
        $decoded = rawurldecode($val);
        if (!mb_check_encoding($decoded, 'UTF-8')) 
           throw new \InvalidArgumentException("Failed to decode param '$val'", 400);

        return $decoded;
    }

    public static function loosen(array|string $path): string {
        if ($path === '/') {
            return $path;
        }

        return is_array($path) ? array_map([self::class, 'loosen'], $path) : rtrim($path, '/');
    }

    /* ---------- Stringify ---------- */

    /**
     * Escape text for stringify to path.
     */
    private static function escapeText(string $str): string {
        return preg_replace('/[{}()\[\]+?!:*\\\\]/', '\\\\$0', $str);
    }
}