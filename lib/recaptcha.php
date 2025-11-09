<?php

namespace Ceteralabs\Recaptcha;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\HttpClient;

class ReCaptcha
{
    const MODULE_ID = 'ceteralabs.recaptcha';

    public static function isActiveFlag()
    {
        return Option::get(self::MODULE_ID, 'active', 'Y') === 'Y';
    }

    public static function checkRecaptchaActive()
    {
        if (!self::isActiveFlag()) {
            return false;
        }
        return (strlen(self::getPublicKey()) > 0 && strlen(self::getSecretKey()) > 0);
    }

    public static function getPublicKey()
    {
        return Option::get(self::MODULE_ID, 'recaptchaPublicKey', '');
    }

    public static function getSecretKey()
    {
        return Option::get(self::MODULE_ID, 'recaptchaSecretKey', '');
    }

    public static function verify($responseToken)
    {
        try {
            if (empty($responseToken)) {
                return false;
            }

            $secret = self::getSecretKey();
            if (empty($secret)) {
                return false;
            }

            $http = new HttpClient(['socketTimeout' => 5, 'streamTimeout' => 5]);
            try {
                $result = $http->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $responseToken,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            } catch (\Exception $e) {
                return false;
            }

            if ($result === null) return false;
            $data = json_decode($result, true);
            if (!is_array($data)) return false;
            return $data;
        } catch (\Throwable $e) {
            \CEventLog::Add([
                'SEVERITY'      => 'WARNING',
                'AUDIT_TYPE_ID' => 'CETERA.RECAPTCHA_ERROR',
                'MODULE_ID'     => self::MODULE_ID,
                'ITEM_ID'       => self::MODULE_ID,
                'DESCRIPTION'   => $e->getMessage(),
            ]);
        }
    }
}
