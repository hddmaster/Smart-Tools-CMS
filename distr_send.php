<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Рассылка сообщений</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/distribution.php')) $tabs->add_tab('/admin/distribution.php', 'Рассылки', 1);
if ($user->check_user_rules('view','/admin/distr_msg.php')) $tabs->add_tab('/admin/distr_msg.php', 'Сообщения');
if ($user->check_user_rules('view','/admin/distr_templates.php')) $tabs->add_tab('/admin/distr_templates.php', 'Шаблоны');
$tabs->show_tabs();

if(isset($_GET['id']))
 {

 if ($user->check_user_rules('view'))
  {
  $distr_id = (int)$_GET['id'];
  $result = mysql_query("select * from distr where distr_id=$distr_id");
  $row = mysql_fetch_array($result);

  $distr_name = $row['distr_name'];
  $msg_id = $row['msg_id'];
  
  if ($msg_id == 0) echo '<p align="center">Нет сообщений в рассылке</p>';
  else {

  $result = mysql_query("select file_path from distr_msg where msg_id=$msg_id");
  $row = mysql_fetch_array($result);

  $distribution_files_path = $user->get_cms_option('distribution_files_path');
  $filename = $distribution_files_path.$msg_id.'/'.$row['file_path'];

  $result = mysql_query("select *,date_format(date, '%d.%m.%Y (%H:%i:%s)') as date2 from distr_msg where msg_id=$msg_id");
  $row = mysql_fetch_array($result);

  $result = mysql_query("select count(*) from distr_list where distr_id=$distr_id");
  $r = mysql_fetch_array($result);

  echo '<h2>Текущее сообщение:</h2>
        <p>Заголовок: <strong>'.htmlspecialchars($row['head']).'</strong>
        <br>Дата создания: <strong>'.$row['date2'].'</strong>
        <br>Количество подписчиков рассылки: <strong>'.$r[0].'</strong>';
        if ($row['file_path'])
         {
           echo '<br><br>В сообщение вложен файл: <strong>'.basename($filename).'</strong>';
           echo '<br>Полный путь к файлу на сервере: <strong>'.$filename.'</strong>';
         }
        echo '</p>
        <div style="width:100%;border:#DDDDDD 1px solid;padding:10px;">'.$row['text'];
 if ($row['file_path'])
  {
   echo '<p>В сообщение вложен файл. В целях уменьшения размера сообщения, вы можете просмотреть файл перейдя по cсылке: <a href="http://'.$_SERVER['HTTP_HOST'].$filename.'">'.basename($filename).'</a></p>';
  }

  echo '</div>
         <form method="POST" target="distr_send" onsubmit="sw(\'/admin/distr_send_action.php?id='.$distr_id.'&msg_id='.$msg_id.'\'); return false;">
           <table cellpadding="4" cellspacing="0" border="0">
             <tr>
               <td><input type="checkbox" name="signature"></td>
               <td>Использовать подпись сообщений</td>
             </tr>
           </table><br>
           <button type="submit">Отправить</button></form>';
  }//else msg_id == 0

  } else $user->no_rules('view');
 }

else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>