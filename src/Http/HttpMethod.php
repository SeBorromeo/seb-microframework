<?php namespace SeBorromeo\SebMicroframework\Http;

enum HttpMethod: string {
    case ALL          = 'ALL';

    case CHECKOUT     = 'CHECKOUT';
    case COPY         = 'COPY';
    case DELETE       = 'DELETE';
    case GET          = 'GET';
    case HEAD         = 'HEAD';
    case LOCK         = 'LOCK';
    case MERGE        = 'MERGE';
    case MKACTIVITY   = 'MKACTIVITY';
    case MKCOL        = 'MKCOL';
    case MOVE         = 'MOVE';
    case MSEARCH      = 'M-SEARCH';
    case NOTIFY       = 'NOTIFY';
    case OPTIONS      = 'OPTIONS';
    case PATCH        = 'PATCH';
    case POST         = 'POST';
    case PURGE        = 'PURGE';
    case PUT          = 'PUT';
    case REPORT       = 'REPORT';
    case SEARCH       = 'SEARCH';
    case SUBSCRIBE    = 'SUBSCRIBE';
    case TRACE        = 'TRACE';
    case UNLOCK       = 'UNLOCK';
    case UNSUBSCRIBE  = 'UNSUBSCRIBE';

    public static function fromString(string $method): ?self {
        return self::tryFrom(strtoupper($method));
    }
}
