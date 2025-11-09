<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$module_id = 'ceteralabs.recaptcha';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    Option::set($module_id, 'recaptchaPublicKey', trim($_POST['recaptchaPublicKey'] ?? ''));
    Option::set($module_id, 'recaptchaSecretKey', trim($_POST['recaptchaSecretKey'] ?? ''));
    Option::set($module_id, 'recaptcha_label', trim($_POST['recaptcha_label'] ?? ''));
    Option::set($module_id, 'recaptcha_error', trim($_POST['recaptcha_error'] ?? ''));
    Option::set($module_id, 'active', (isset($_POST['active']) && $_POST['active'] === 'Y') ? 'Y' : 'N');

    echo '<div style="color: green; margin: 10px 0;">' . Loc::getMessage('CETERALABS_RECAPTCHA_SAVED') . '</div>';
}

$publicKey = Option::get($module_id, 'recaptchaPublicKey', '');
$secretKey = Option::get($module_id, 'recaptchaSecretKey', '');
$label     = Option::get($module_id, 'recaptcha_label', Loc::getMessage("CETERALABS_RECAPTCHA_LABEL") ?: 'Подтвердите, что вы не робот');
$errorMsg  = Option::get($module_id, 'recaptcha_error', 'Вы не прошли проверку капчей');
$active    = Option::get($module_id, 'active', 'N');
?>

<form method="post">
    <?= bitrix_sessid_post() ?>
    <table class="adm-detail-content-table edit-table">
        <tr>
            <td width="40%"><?= Loc::getMessage('CETERALABS_RECAPTCHA_ACTIVE') ?>:</td>
            <td width="60%">
                <input type="checkbox" name="active" value="Y" <?= ($active === 'Y' ? 'checked' : '') ?> />
            </td>
        </tr>
        <tr>
            <td width="40%"><?= Loc::getMessage('CETERALABS_RECAPTCHA_PUBLIC_KEY') ?>:</td>
            <td width="60%"><input type="text" size="50" name="recaptchaPublicKey" value="<?= htmlspecialcharsbx($publicKey) ?>"></td>
        </tr>
        <tr>
            <td width="40%"><?= Loc::getMessage('CETERALABS_RECAPTCHA_SECRET_KEY') ?>:</td>
            <td width="60%"><input type="text" size="50" name="recaptchaSecretKey" value="<?= htmlspecialcharsbx($secretKey) ?>"></td>
        </tr>
        <tr>
            <td width="40%"><?= Loc::getMessage("CETERALABS_RECAPTCHA_LABEL") ?>:</td>
            <td width="60%">
                <input type="text" size="50" name="recaptcha_label" value="<?= htmlspecialcharsbx($label) ?>">
            </td>
        </tr>
        <tr>
            <td width="40%"><?= Loc::getMessage("CETERALABS_RECAPTCHA_ERROR") ?>:</td>
            <td width="60%">
                <input type="text" size="50" name="recaptcha_error" value="<?= htmlspecialcharsbx($errorMsg) ?>">
            </td>
        </tr>
    </table>
    <input type="submit" value="<?= Loc::getMessage('CETERALABS_RECAPTCHA_SAVE') ?>" class="adm-btn-save">
</form>