<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

//------------------------------------------------------------------------------
// AJAX
define('USER_ID',$user->user_id);

function add_message($to_user_id, $text)
 {
  $objResponse = new xajaxResponse();
  if (trim($to_user_id) == '' || trim($text) == '')
   {
     $objResponse->alert("Не указано одно из обязательных полей!");
     return $objResponse;
   }
  else
    mysql_query("insert into messages values (null, ".USER_ID.", $to_user_id, now(), '$text', 0)");
	  
  $objResponse->script("xajax_check_new_messages();");
  return $objResponse;
 }

function read_message($message_id)
 {
  $objResponse = new xajaxResponse();
  mysql_query("update messages set status = 1 where message_id = $message_id");
  $objResponse->script("xajax_check_new_messages();");
  return $objResponse;
 }

function check_new_messages()
{
 $text = '';

 if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
 if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
 $start=abs($page*$per_page);

 if (isset($_GET['sort_by']) && isset($_GET['order']))
  {
    $sort_by = $_GET['sort_by'];
    $order  = $_GET['order'];
  }
 else
  {
    $sort_by = 'message_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select *, date_format(date, '%d.%m.%Y (%H:%i:%s)') as date from messages where user_id = ".USER_ID."  or to_user_id = ".USER_ID." and status < 2 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 $text .= navigation_to_string($page, $per_page, $total_rows, $params);
 $text .= '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">
      <tr align="center" class="header">
        <td>Дата</td>
        <td>Статус</td>
        <td>От</td>
        <td>Кому</td>
        <td>Сообщение</td>
      </tr>';
  while($row = mysql_fetch_array($result))
   {
        $text .=  '<tr align="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
	           <td nowrap>'.$row['date'].'</td>';
  		   $text .= '<td nowrap>'.(($row['status'] == 1) ? '<span class="green">прочитано</span>' : '<span class="grey">не прочитано</span>').'</td>';
		   $text .= '</td><td nowrap>';
                   if (USER_ID == $row['user_id']) $text .= '<img src="/admin/images/icons/status.png" alt="">'; else {
                   $res = mysql_query("select * from auth where user_id = ".$row['user_id']);
                   if (mysql_num_rows($res) > 0)
                    {
                      $r = mysql_fetch_array($res);
                      $text .= htmlspecialchars($r['username']);
                    }
                   else $text .= '<span class="small">пользователя не существует</span>';
		   }
		   $text .= '<td nowrap>';
                   if (USER_ID == $row['to_user_id']) $text .= '<img src="/admin/images/icons/status.png" alt="">'; else {
                   $res = mysql_query("select * from auth where user_id = ".$row['to_user_id']);
                   if (mysql_num_rows($res) > 0)
                    {
                      $r = mysql_fetch_array($res);
                      $text .= htmlspecialchars($r['username']);
                    }
                   else $text .= '<span class="small">пользователя не существует</span>';
		   }
		   $text .= '</td>
                   <td align="left" width="100%"><div class="text"><span class="grey">';

        $text_ = str_replace("\n","<br>",htmlspecialchars($row['text']));
        $pattern = '/(https?:\/\/)\S+/i';
        if (preg_match_all($pattern, $text_, $matches))
         {
           foreach($matches[0] as $match)
           $text_ = str_replace($match, '<a href="'.$match.'" target="_blanck">'.$match.'</a>', $text_).'<br/>';
         }
        if (USER_ID == $row['to_user_id'] && $row['status'] == 0) $text .= '<div id="message_'.$row[message_id].'"><p align="center"><button onclick="xajax_read_message('.$row[message_id].');">Прочитать</button></p></div>';
	else $text .= $text_;
	$text .= '</span></div></td></tr>';
   }
 $text .=  '</table></div>';
 $text .= navigation_to_string($page, $per_page, $total_rows, $params);
}
else $text .= '<p align="center">Не найдено</p>';
  $objResponse = new xajaxResponse();
  $objResponse->assign("content","innerHTML",$text);
  return $objResponse;
}

$xajax->registerFunction("add_message");
$xajax->registerFunction("read_message");
$xajax->registerFunction("check_new_messages");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Сообщения</h1>';

 if ($user->check_user_rules('view'))
  {

?>
<script>
function process_requests()
 {
   delay = 10000; // 10 cек.
   xajax_check_new_messages();
   setTimeout("process_requests()", delay);
 }
process_requests();
</script>
<?

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Написать сообщение</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Кому <sup class="red">*</sup></td>
      <td><select style="width:280px" name="user_id">
           <option value="" selected>Выберите пользователя...</option>';

 $result = mysql_query("select * from auth where user_id != $user->user_id order by username asc");
 if(mysql_num_rows($result) > 0)
  {
    while ($row = mysql_fetch_array($result))
      echo '<option value="'.$row['user_id'].'">'.htmlspecialchars($row['username']).'</option>'."\n";
  }

 echo'</select></td></tr>
    <tr>
      <td>Текст <sup class="red">*</sup></td>
      <td><textarea style="width:280px" name="text" cols="52" rows="10"></textarea></td>
    </tr>  
	</table><br>
   <button type="button" onclick="xajax_add_message(this.form.user_id.options[this.form.user_id.selectedIndex].value, this.form.text.value);">Добавить</button>
  </form><br /></div></div>';

 echo '<div id="content" align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></div>';

} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>