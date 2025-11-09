<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('ceteralabs.recaptcha', [
    'Ceteralabs\\Recaptcha\\ReCaptcha' => 'lib/recaptcha.php',
    'Ceteralabs\\Recaptcha\\EventHandlers\\Main' => 'lib/eventhandlers/main.php',
]);
