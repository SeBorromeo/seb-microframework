<?php namespace SeBorromeo\SebMicroframework\Http;

final class HttpMethods {
    public const ALL = [
        'CHECKOUT', 'COPY', 'DELETE', 'GET', 'HEAD', 'LOCK', 'MERGE',
        'MKACTIVITY', 'MKCOL', 'MOVE', 'MSEARCH', 'NOTIFY',
        'OPTIONS', 'PATCH', 'POST', 'PURGE', 'PUT',
        'REPORT', 'SEARCH', 'SUBSCRIBE', 'TRACE',
        'UNLOCK', 'UNSUBSCRIBE'
    ];

    public static function all(): array {
        return array_map('strtolower', self::ALL);
    }

    public static function isValid(string $method): bool {
        return in_array(strtoupper($method), self::ALL, true);
    }
}
