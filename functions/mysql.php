<?
$tpl_top = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Smart Tools CMS</title>
<meta http-equiv="Content-Type" content="text/html; charset="utf-8">
<link href="/admin/css/style.css" rel="stylesheet" type="text/css">
</head>
<body>
 <table width="100%" height="100%" cellpadding="0" cellspacing="0">
 <tr>
   <td width="100%" valign="center" align="center">
   <table cellpadding="6" cellspacing="0">
    <tr><td align="center">
      <div><a href="http://www.smart-tools.ru"><img src="/admin/images/logo.gif" alt="" border="0"></a><br/><span class="transparent">система управления сайтом</span></div>
    </td></tr>
    <tr><td>&nbsp;</td></tr>
    <tr>
      <td align="center">
        <div class="login" style="padding: 20px;">';

$tpl_bottom = '</div>
      </td>
     </tr>
    </table>
   </td>
 </table>
</body>
</html>';

    $db = mysql_connect(MySQL_HOST, MySQL_LOGIN, MySQL_PASSWORD);
    if (!$db)
     {
       echo $tpl_top;
       echo '<h2 style="color: #FF0000">Ошибка: Невозможно соединиться с базой данных!</h2><p>'.mysql_error().'</p>';
       echo $tpl_bottom;
       exit();
     }
    if(!mysql_select_db(MySQL_DATABASE))
     {
       echo $tpl_top;
       echo '<h2 style="color: #FF0000">Ошибка: Невозможно соединиться с базой данных!</h2><p>'.mysql_error().'</p>';
       echo $tpl_bottom;
       exit();
     }
    $result = mysql_query("SET NAMES utf8");
    if(!$result)
     {
       echo $tpl_top;
       echo '<h2 style="color: #FF0000">Ошибка!</h2><p>'.mysql_error().'</p>';
       echo $tpl_bottom;
       exit();
     }
?>