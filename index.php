<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
define ('VALID_CMS_USER', (($user->check_valid_user()) ? true : false));

//проверка на существование пользователей в системе
$res = mysql_query("select * from auth"); if ($res && mysql_num_rows($res) == 0) {header("Location: ".SMART_TOOLS_PATH."/install.php"); exit();}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Smart Tools CMS - Авторизация</title>
<?if(VALID_CMS_USER):?><meta http-equiv="refresh" content="1; URL=/admin/admin.php"><?endif;?>
<meta http-equiv="Content-Type" content="text/html; charset="utf-8">
<link href="/admin/css/style.css" rel="stylesheet" type="text/css">
</head>

<body <?if(!VALID_CMS_USER):?> onLoad="document.auth.username.focus();"<?endif?>>
 <table width="100%" height="100%" cellpadding="0" cellspacing="0">
 <tr>
   <td width="100%" valign="center" align="center">
   <table cellpadding="6" cellspacing="0">
    <tr><td align="center">
      <div><a href="http://www.smart-tools.ru"><img src="/admin/images/logo.gif" alt="" border="0"></a><br/><span class="transparent">система управления сайтом</span></div>
    </td></tr>
    <tr><td>&nbsp;</td></tr>
    <tr><td><span class="h3">Авторизация</span></td></tr>
    <tr>
      <td align="center">
<?
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }
?>
        <?if(!VALID_CMS_USER):?>

        <form method="post" name="auth" action="/admin/admin.php<? if (isset($_GET['referrer']) && trim($_GET['referrer']) !== '') echo '?referrer='.urlencode($_GET['referrer']); ?>">
        <table cellpadding="20" cellspacing="0" class="login">
         <tr>
           <td align="center">
             <table cellpadding="2" cellspacing="0">
              <tr>
                <td align="right">Имя</td>
                <td><input type="text" name="username" autocomplete="off"></td>
              </tr>
              <tr>
                <td align="right">Пароль</td>
                <td><input type="password" name="password" autocomplete="off"></td>
              </tr>
              <tr><td colspan="2" align="right">
                <table cellpadding="1" cellspacing="0" align="right">
                 <tr>
                   <td><input type="checkbox" name="cookie"<? if ($user->get_cms_option('save_user_session') == 1) echo ' checked'; ?>></td>
                   <td>запомнить меня</td>
                 </tr>
                </table>
              </td></tr>
              <tr><td colspan="2" align="right"><button type="submit">Вход</button></td></tr>
             </table>
           </td>
         </tr>
        </table>
        </form>
        
        <? else: ?>
        
        <div class="login" style="padding: 20px;">
        <p>Система распознала Вас как <strong><?=$user->username?></strong></p>
        <p class="grey">Автоматический вход в систему управления...</p>
        </div>
        
        <?endif?>
      
        <p class="transparent">ver. <?=CMS_VERSION?> &nbsp; (build: <?=CMS_BUILD?>)</p>
      </td>
     </tr>
    </table>
   </td>
 </table>
</body>
</html>