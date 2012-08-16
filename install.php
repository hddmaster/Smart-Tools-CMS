<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php"); $user = new Auth;

//проверка на существование пользователей в системе
$res = mysql_query("select * from auth"); if ($res && mysql_num_rows($res) > 0) {header("Location: ".SMART_TOOLS_PATH."/"); exit();}

if (isset($_POST['username']) &&
    isset($_POST['password1']) &&
    isset($_POST['password2']))
 {
   foreach ($_POST as $key => $value) $_SESSION['create_new_admin'][$key] = trim(stripslashes($value));
   if (trim($_POST['username'])=='' || trim($_POST['password1'])=='' || trim($_POST['password2'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   if ($_POST['password1'] != $_POST['password2']) {Header("Location: ".$_SERVER['PHP_SELF']."?message=passwords"); exit();}

   $username = trim($_POST['username']);
   $email = trim($_POST['email']);
   //проверка на корректный e-mail
   if (trim($email) !== '' && !valid_email($email)) {Header("Location: ".$_SERVER['PHP_SELF']."?message=notvalidemail");exit();}
   
   $password = md5(trim($_POST['password1']).SOLT);

   $result = mysql_query("insert into auth (username, password, user_type, email, status) values ('$username', '$password', 1, '$email', 1)");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   $user->login(stripcslashes(trim($_POST['username'])), stripcslashes(trim($_POST['password1'])));
   header("Location: ".SMART_TOOLS_PATH."/admin.php"); exit();
 }
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Smart Tools CMS - Инсталляция системы</title>
<meta http-equiv="Content-Type" content="text/html; charset="utf-8">
<link href="/admin/css/style.css" rel="stylesheet" type="text/css">
</head>

<body onLoad="document.register.username.focus();">
 <table width="100%" height="100%" cellpadding="0" cellspacing="0">
 <tr>
   <td width="100%" valign="center" align="center">
   <table cellpadding="6" cellspacing="0">
    <tr><td align="center">
      <div><a href="http://www.smart-tools.ru"><img src="/admin/images/logo.gif" alt="" border="0"></a><br/><span class="transparent">система управления сайтом</span></div>
    </td></tr>
    <tr><td>&nbsp;</td></tr>
    <tr><td><span class="h3">В системе нет ни одного пользователя,<br />необходимо создать администратора системы</span></td></tr>
    <tr>
      <td align="center">
<?
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }
?>
        <form method="post" name="register" action="">
        <table cellpadding="20" cellspacing="0" class="login">
         <tr>
           <td align="center">
             <table cellpadding="2" cellspacing="0">
              <tr>
                <td align="right">Имя</td>
                <td><input type="text" name="username" value="<?=(($_SESSION['create_new_admin']['username']) ? htmlspecialchars($_SESSION['create_new_admin']['username']) : '')?>"></td>
              </tr>
              <tr>
                <td align="right">E-mail</td>
                <td><input type="text" name="email" value="<?=(($_SESSION['create_new_admin']['email']) ? htmlspecialchars($_SESSION['create_new_admin']['email']) : '')?>"></td>
              </tr>
              <tr>
                <td align="right">Пароль</td>
                <td><input type="password" name="password1" value="<?=(($_SESSION['create_new_admin']['password1']) ? htmlspecialchars($_SESSION['create_new_admin']['password1']) : '')?>"></td>
              </tr>
              <tr>
                <td align="right">Пароль<br /><span class="small">для проверки</span></td>
                <td><input type="password" name="password2" value="<?=(($_SESSION['create_new_admin']['password2']) ? htmlspecialchars($_SESSION['create_new_admin']['password2']) : '')?>"></td>
              </tr>
              <tr><td colspan="2" align="right"><button type="submit">Создать</button></td></tr>
             </table>
           </td>
         </tr>
        </table>
        </form>
      
        <p class="transparent">ver. <?=CMS_VERSION?> &nbsp; (build: <?=CMS_BUILD?>)</p>
      </td>
     </tr>
    </table>
   </td>
 </table>
</body>
</html>