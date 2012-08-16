<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['values']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('edit'))
  {
   $poll_id = (int)$_GET['id'];
   $values = $_POST['values'];

//проверка на наличие элементов: вариантов ответов
   if(count($values) == 0)
    {
      Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");
      exit();
    }
//проверка на пустые поля вариантов ответов
   foreach ($values as $key => $value)
    {
     if($value == ''){Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");exit();}
    }

  foreach($values as $variant_id => $value)
   {
     $query = "update polls_results set value=$value where variant_id=$variant_id";
     mysql_query($query);
   }

  $_SESSION['smart_tools_refresh'] = 'enable';
  Header("Location: ".$_SERVER['PHP_SELF']."?id=$poll_id"); exit();
  } else $user->no_rules('edit');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {
   $poll_id = (int)$_GET['id'];
   $query = "select * from polls_names where poll_id=$poll_id";
   $result = mysql_query($query);
   if (!$result) exit();
   $row = mysql_fetch_array($result);

   $question = $row['question'];

 echo '<h2>'.htmlspecialchars($question).'</h2>';

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$poll_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">';

   $query = "select * from polls_results where poll_id=$poll_id order by order_id asc";
   $result = mysql_query($query);
   if (!$result) exit();
   while ($row = mysql_fetch_array($result))
    {
     echo '<tr>
             <td>'.htmlspecialchars($row['variant_name']).'</td>
             <td>
               <input type="text" name="values['.$row['variant_id'].']" value="'.$row['value'].'" size="10" maxlength="255" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             </td>
           </tr>';
    }
echo'   </table><br>
   <button type="SUBMIT">Сохранить</button>
  </form>';
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>