<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Помощь</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/help.php')) $tabs->add_tab('/admin/help.php', 'Документация');
if ($user->check_user_rules('view','/admin/help_options.php')) $tabs->add_tab('/admin/help_options.php', 'Настройки системы');
if ($user->check_user_rules('view','/admin/help_support.php')) $tabs->add_tab('/admin/help_support.php', 'Техническая поддержка');
$tabs->show_tabs();

 if ($user->check_user_rules('view'))
  {
?>
<fieldset>
 ICQ: <strong>116-752-622</strong><br/>
 Телефон: <strong>+7 (910) 483-5804</strong><br/>
 e-mail: <a class="grey" href="mailto:hdd-master@yandex.ru"><strong>hdd-master@yandex.ru</strong></a>
</fieldset>
<?
  } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>