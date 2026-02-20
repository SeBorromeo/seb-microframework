<?php namespace SeBorromeo\SebMicroframework\Utils;

final class ArrayHelper {
    /**
     * Recursively flattens an array.
     */
    public static function flatten(array $items): array {
        $result = [];

        array_walk_recursive($items, function ($item) use (&$result) {
            $result[] = $item;
        });

        return $result;
    }
}
