<?php /** @noinspection PhpMissingStrictTypesDeclarationInspection */

/**
 * Configuration manager class
 */
class ConfigManager
{
    /**
     * @param string $key
     * @param $default
     *
     * @return false|mixed
     */
    public function getOption(string $key, $default): mixed
    {
        return get_option($key) ?? $default;
    }
}
