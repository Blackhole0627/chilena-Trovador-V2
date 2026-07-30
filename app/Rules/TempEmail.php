<?php

namespace App\Rules;

use Cache;
use Illuminate\Contracts\Validation\Rule;

class TempEmail implements Rule
{
    protected $blacklistedDomains;

    public function __construct()
    {
        $this->blacklistedDomains = Cache::remember('TempEmailBlackList', 60 * 10, function () {
            $data = @file_get_contents('https://raw.githubusercontent.com/disposable/disposable-email-domains/master/domains.txt');
            if ($data) {
                return array_filter(array_map('trim', explode("\n", $data)));
            }
            return [];
        });
    }

    public function passes($attribute, $value)
    {
        $emailDomain = substr(strrchr($value, "@"), 1);
        return !in_array($emailDomain, $this->blacklistedDomains);
    }

    public function message()
    {
        return __('general.email_valid');
    }
}