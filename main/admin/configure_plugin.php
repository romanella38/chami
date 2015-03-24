<?php
/* For licensing terms, see /license.txt */
/**
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2012
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */
use \ChamiloSession as Session;

// name of the language file that needs to be included
$language_file = array('admin');
$cidReset = true;
require_once '../inc/global.inc.php';

// Access restrictions
api_protect_admin_script();

$pluginName = $_GET['name'];

$appPlugin = new AppPlugin();
$installedPlugins = $appPlugin->get_installed_plugins();
$pluginInfo = $appPlugin->getPluginInfo($pluginName, true);

if (!in_array($pluginName, $installedPlugins) || empty($pluginInfo)) {
    api_not_allowed(true);
}

global $_configuration;

$message = null;
$content = null;

$currentUrl = api_get_self() . "?name=$pluginName";

if (isset($pluginInfo['settings_form'])) {
    $form = $pluginInfo['settings_form'];
    if (isset($form)) {
        //We override the form attributes
        $attributes = array('action' => $currentUrl, 'method' => 'POST');
        $form->updateAttributes($attributes);
        $content = Display::page_header($pluginInfo['title']);
        $content .= $form->toHtml();
    }
} else {
    $message = Display::return_message(get_lang('NoConfigurationSettingsForThisPlugin'), 'warning');
}

if (isset($form)) {
    if ($form->validate()) {
        $values = $form->exportValues();

        //api_delete_category_settings_by_subkey($pluginName);
        $accessUrlId = api_get_current_access_url_id();

        api_delete_settings_params(
            array(
                'category = ? AND access_url = ? AND subkey = ? AND type = ? and variable <> ?' => array(
                    'Plugins',
                    $accessUrlId,
                    $pluginName,
                    'setting',
                    "status"
                )
            )
        );

        foreach ($values as $key => $value) {
            api_add_setting(
                $value, Database::escape_string($pluginName . '_' . $key),
                $pluginName,
                'setting',
                'Plugins',
                $pluginName,
                null,
                null,
                null,
                $_configuration['access_url'],
                1
            );
        }
        if (isset($values['show_main_menu_tab'])) {
            $objPlugin = $pluginInfo['plugin_class']::create();
            $objPlugin->manageTab($values['show_main_menu_tab']);
        }

        $message = Display::return_message(get_lang('Updated'), 'success');

        Session::write('message', $message);

        header("Location: $currentUrl");
        exit;
    } else {
        foreach ($form->_errors as $error) {
            $message .= Display::return_message($error, 'error');
        }
    }
}

if (Session::has('message')) {
    $message = Session::read('message');
}

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH) . 'admin/index.php',
    'name' => get_lang('PlatformAdmin')
);
$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH) . 'admin/settings.php?category=Plugins',
    'name' => get_lang('Plugins')
);

$tpl = new Template($pluginName, true, true, false, true, false);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();

Session::erase('message');
