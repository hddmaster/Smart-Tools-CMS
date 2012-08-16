<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

 if ($user->check_user_rules('view'))
  {

if (isset($_GET['ip']) && $_GET['ip'] != '')
 {
   $ip = $_GET['ip'];
   echo '<h2>Информация об ip адресе '.$ip.'</h2>';
   $sock = fsockopen ("whois.ripe.net", 43, $errno, $errstr);
   //соединение с сокетом TCP, ожидающим на сервере "whois.ripe.net" на 43 порту. Возвращает дескриптор соединения

   if (!$sock)
    {
      echo "$errno ($errstr)";
    }
   else
    {
      fputs ($sock, $ip."\r\n");
      //записываем строку из переменной $ip в дескриптор сокета

      while (!feof($sock)) {
        echo (str_replace(":",":      ",fgets ($sock,128))."<br/>");
        //осуществляем чтение из дескриптора сокета
      }
    }
    fclose ($sock);
 }
  } else $user->no_rules('view');

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>