<?php

if (!function_exists('iso8601_duration')) {
    /**
     * Convert a string to iso8601 duration without seconds.
     *
     * @param string $duration format must be like 'days-hours-minutes', ex: '5-3-40'
     *
     * @param string
     */
    function iso8601_duration($duration)
    {
        $duration = explode('-', $duration);

        return sprintf('P%dDT%dH%dM', $duration[0], $duration[1], $duration[2]);
    }
}

if (!function_exists('trans_db')) {
    /**
     * Replace the lumen default trans() to get translation from database.
     *
     * @param string $group
     * @param string $key
     *
     * @return string|array
     */
    function trans_db(string $group, string $key = null)
    {
        $local = app()->getLocale();
        $prefix = 'bmoov_express.';

        return app('cache')->rememberForever(tenant()->id . '.' . $local . '.' . $prefix . $group . ($key ? '.' . $key : ''), function () use ($local, $group, $key, $prefix) {
            $key ? $result = app('db')->select('select text from language_lines where `group` = ? and `key` = ? limit 1', [
                $prefix . $group, $key,
            ]) : $result = app('db')->select('select text, `key` from language_lines where `group` = ?', [
                $prefix . $group,
            ]);
            if (!$result) {
                return trans($group . '.' . $key);
            }

            return $key ? json_decode($result[0]->text)->{$local} ?? trans($group . '.' . $key) : array_merge(...array_values(array_map(function ($item) use ($local) {
                $ff[$item->key] = json_decode($item->text)->{$local};

                return $ff;
            }, $result)));
        });
    }
}

if (!function_exists('salt')) {
    /**
     * Generate a random 5 char string.
     *
     * @return string
     */
    function salt()
    {
        return substr(str_shuffle(str_repeat(
            $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            ceil(5 / strlen($x))
        )), 1, 5);
    }
}

if (!function_exists('locale_datetime_format')) {
    /**
     * Get the datetime format according to the current locale.
     */
    function locale_datetime_format($locale = null)
    {
        $dateTimeFormat = [
            'en' => 'd.m.Y \a\t H:i',
            'fr' => 'd.m.Y à H:i',
            'ar' => 'd.m.Y الساعة H:i',
        ];

        try {
            return $dateTimeFormat[$locale ?? app()->getLocale()];
        } catch (\Throwable $th) {
            app('log')->error($th->getMessage());

            return $dateTimeFormat['en'];
        }
    }
}

if (!function_exists('express_config')) {
    /**
     * Get an express setting from express_settings table.
     *
     * @param string $key the setting key
     * @param mixed $default the default value
     *
     * @return mixed
     */
    function express_config(string $key, $default = null)
    {
        $value = app('db')->select('select value from express_configurations where `key` = ?', [$key]);

        return $value ? $value[0]->value : $default;
    }
}

if (!function_exists('tenant')) {
    /**
     * Get tenant by id.
     *
     * @return stdClass|null
     */
    function tenant()
    {
        // Get the subdomain part to be used finding the right tenant
        $subdomain = explode('.', request()->getHost())[env('SUBDOMAIN_POSITION', 0)] ?? null;
        $value = app('db')->connection('mysql')->select('select data from tenants where tenants.id = ?', [$subdomain]);

        if ($value) {
            $value = json_decode($value[0]->data);
            $value->id = $subdomain;
        }

        return $value ? $value : null;
    }
}

if (!function_exists('backend_url')) {
    /**
     * Build an url to the backend of the current tenant.
     *
     * @return string $path
     */
    function backend_url($path = null)
    {
        $sections = explode('.', request()->getSchemeAndHttpHost());
        unset($sections[1]);
        $sections = implode('.', $sections);
        $sections = $sections . (startsWith($path, '/') || $path == '' ? '' : '/') . $path;

        return $sections;
    }
}

if (!function_exists('startsWith')) {
    function startsWith($haystack, $needle)
    {
        return 0 === substr_compare($haystack, $needle, 0, strlen($needle));
    }
}


 function generateDailyPassword()
{

    $pass = rand(1000, 9999);
    $dailyPass = 2024;
    return $dailyPass;
}