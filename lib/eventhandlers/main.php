<?php

namespace Ceteralabs\Recaptcha\EventHandlers;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\HttpRequest;
use Ceteralabs\Recaptcha\ReCaptcha;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Main
{
    const MODULE_ID = 'ceteralabs.recaptcha';

    public static function OnPageStart()
    {
        if (defined('ADMIN_SECTION') || !ReCaptcha::checkRecaptchaActive()) {
            return;
        }

        try {
            $request = Application::getInstance()->getContext()->getRequest();
            self::checkReCaptcha($request);
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

    protected static function checkReCaptcha(HttpRequest $request): bool
    {
        global $APPLICATION;

        $source = $request->isPost() ? 'getPost' : 'getQuery';

        $captchaSid = $request->$source("captcha_sid") ?: $request->$source("captcha_code");
        $reCaptchaCode = $request->$source("g-recaptcha-response");

        if (!$captchaSid || !$reCaptchaCode) {
            return true;
        }

        $result = ReCaptcha::verify($reCaptchaCode);
        if ($result === false) {
            $errorMessage = Option::get(self::MODULE_ID, 'recaptcha_error', '');
            if (trim($errorMessage) === '') {
                $errorMessage = Loc::getMessage('CETERALABS_RECAPTCHA_ERROR');
            }

            $APPLICATION->ResetException();
            $APPLICATION->ThrowException($errorMessage);

            return false;
        }

        $connection = Application::getConnection();
        $sqlHelper  = $connection->getSqlHelper();

        $connection->queryExecute(sprintf(
            'UPDATE b_captcha SET CODE=%s WHERE ID=%s',
            $sqlHelper->convertToDbString('OK'),
            $sqlHelper->convertToDbString($captchaSid)
        ));

        return true;
    }


    public static function OnEndBufferContent(&$content)
    {
        if (defined('ADMIN_SECTION') || !ReCaptcha::checkRecaptchaActive()) {
            return;
        }

        $label = Option::get(self::MODULE_ID, 'recaptcha_label', '');
        if (trim($label) === '') {
            $label = Loc::getMessage('CETERALABS_RECAPTCHA_LABEL');
        }

        $replaced = 0;
        $content = preg_replace_callback(
            '/<input[^>]+?name\s*=\s*["\']captcha_word["\'][^>]*>/i',
            function () use (&$replaced) {
                $replaced++;
                $uid = 'recaptcha-' . substr(md5(uniqid('', true)), 0, 6);
                return '<input type="hidden" name="captcha_word" value="OK">'
                    . '<div id="' . $uid . '" class="g-recaptcha"></div>';
            },
            $content
        );

        if ($replaced === 0) {
            return;
        }

        $content = preg_replace('/<img[^>]+captcha\.php[^>]+>/i', '',  $content);
        $content = preg_replace('/Введите[^<]*(картинке|символы)[^<]*/iu', $label, $content);

        $defaultErrors = @unserialize(Loc::getMessage('CETERALABS_RECAPTCHA_DEFAULT_ERRORS')) ?: [];
        $customError = Option::get(self::MODULE_ID, 'recaptcha_error', '');
        if (trim($customError) === '') {
            $customError = Loc::getMessage("CETERALABS_RECAPTCHA_ERROR");
        }

        $content = str_replace($defaultErrors, $customError, $content);
        $siteKey = ReCaptcha::getPublicKey();

        if (!$siteKey) {
            return;
        }

        $script = '<script data-skip-moving="true">' . 'window.recaptchaOptions=' . \CUtil::PhpToJSObject(['key' => $siteKey]) . ';' . '</script>';
        $script .= '<script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>';
        $script .= '<script src="/bitrix/js/' . self::MODULE_ID . '/script.js" data-skip-moving="true"></script>';

        if (stripos($content, '</head>') !== false) {
            $content = preg_replace('/<\/head>/i', $script . '</head>', $content, 1);
        } else {
            $content .= $script;
        }
    }
}
