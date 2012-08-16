<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
    $history_id = intval($_GET['id']);
    $result = mysql_query("select * from auth_history where history_id = $history_id");
    if (mysql_num_rows($result) > 0)
     {
       $row = mysql_fetch_array($result);
       $data = unserialize($row['data']);
       
        if (isset($data['OTHER DATA'])) {
            echo '<h3>OTHER DATA</h3>';
            echo '<pre>';
            print_r(unserialize($data['OTHER DATA']));
            echo '</pre>';
        }

        if (isset($data['POST'])) {
            echo '<h3>POST</h3>';
            echo '<pre>';
            print_r(unserialize($data['POST']));
            echo '</pre>';
        }

        if (isset($data['GET'])) {
            echo '<h3>GET</h3>';
            echo '<pre>';
            print_r(unserialize($data['GET']));
            echo '</pre>';
        }

        if (isset($data['HTTP_USER_AGENT'])) {
            echo '<h3>HTTP_USER_AGENT</h3>';
            echo '<pre>';
            print_r(unserialize($data['HTTP_USER_AGENT']));
            echo '</pre>';
        }

       //echo '<textarea wrap="on" style="font-family:Courier New;font-size:10pt;width:100%;height:525px" name="request_data">'.$data.'</textarea>';
     }
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>