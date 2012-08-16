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

<h2>Общие настройки</h2>
<table cellpadding="4" cellspacing="0" border="0" width="100%">
 <tr align="center" class="header">
   <td>Название</td>
   <td nowrap>Короткое название</td>
   <td>Описание</td>
   <td>Тип</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Режим экономии трафика</td>
   <td>eco_mode</td>
   <td>При включенном режиме окна системы управления не обновляются после редактирования данных</td>
   <td>BOOLEAN</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>E-mail администратора системы</td>
   <td>admin_email</td>
   <td>На этот адрес электронный почты будут приходить различные оповещения системы, сообщения с сайта и пр.</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Сохранение сессии пользователя в cookies при входе</td>
   <td>save_user_session</td>
   <td>&nbsp;.</td>
   <td>BOOLEAN</td>
 </tr>
</table>


<br/><br/>
<h2>Модуль "Магазин"</h2>
<table cellpadding="4" cellspacing="0" border="0" width="100%">
 <tr align="center" class="header">
   <td>Название</td>
   <td nowrap>Короткое название</td>
   <td>Описание</td>
   <td>Тип</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Обозначение базовой валюты</td>
   <td>shop_currency</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Обозначение 2-ой части базовой валюты</td>
   <td>shop_currency_ext</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Текст под товарным чеком</td>
   <td>shop_cheque_text</td>
   <td>&nbsp;</td>
   <td>TEXT</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Реквизиты для чека</td>
   <td>shop_cheque</td>
   <td>&nbsp;</td>
   <td>TEXT</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Яндекс.Маркет - Название магазина</td>
   <td>yml_name</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Яндекс.Маркет - URL магазина</td>
   <td>yml_url</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Яндекс.Маркет - Юридическое название магазина</td>
   <td>yml_company</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Яндекс.Маркет - Показывать ли скрытые в каталоге позиции?</td>
   <td>yml_hidden_goods</td>
   <td>&nbsp;</td>
   <td>BOOLEAN</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Яндекс.Маркет - Категориии для экспорта</td>
   <td>yml_shop_categories</td>
   <td>&nbsp;</td>
   <td>ARRAY</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Яндекс.Маркет - Отсутствующие товары</td>
   <td>yml_shop_na_elements</td>
   <td>&nbsp;</td>
   <td>ARRAY</td>
 </tr>
</table>


<br/><br/>
<h2>Модуль "Рассылка сообщений"</h2>
<table cellpadding="4" cellspacing="0" border="0" width="100%">
 <tr align="center" class="header">
   <td>Название</td>
   <td nowrap>Короткое название</td>
   <td>Описание</td>
   <td>Тип</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Задержка при отправке сообщений</td>
   <td>distribution_delay</td>
   <td>1 000 000 = 1 сек</td>
   <td>INT</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Папка для хранения вложенных файлов</td>
   <td>distribution_files_path</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Скрипт - обработчик активаций на рассылку</td>
   <td>distribution_script</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Подпись сообщений</td>
   <td>distribution_signature_text</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Заголовок сообщения об активации</td>
   <td>distribution_activation_header</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Имя отправителя сообщений</td>
   <td>distribution_signature_name</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td>Адрес отправителя сообщений в рассылке</td>
   <td>distribution_email</td>
   <td>&nbsp;</td>
   <td>CHAR</td>
 </tr>
 <tr align="center" onmouseover="this.style.backgroundColor='#EFEFEF'" onmouseout="this.style.backgroundColor='white'" class="underline">
   <td> Шаблон сообщения на подписку</td>
   <td>distribution_activation_template</td>
   <td>{LINK} - обязательный параметр</td>
   <td>TEXT</td>
 </tr>
</table>
<?

  } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>
