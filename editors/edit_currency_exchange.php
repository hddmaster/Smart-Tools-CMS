<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['exchange_value']) &&
    isset($_GET['id']))
 {
  if ($user->check_user_rules('edit'))
  {
  if (trim($_POST['exchange_value'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $id = (int)$_GET['id'];
   $exchange_value = $_POST['exchange_value'];

   if ($exchange_value == 0 || $exchange_value == 1)
   {Header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues"); exit();}

   $result = mysql_query("update exchange set exchange_value=$exchange_value where exchange_id=$id");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$id&message=db"); exit();}

   Header("Location: ".$_SERVER['PHP_SELF']."?id=$id");
   exit();
  } else $user->no_rules('edit');
 }

//-----------------------------------------------------------------------------
// AJAX

function check_exchange($exchange)
{
	$objResponse = new xajaxResponse();
  if ($exchange == 0 || $exchange == 1)
   {
  	 $objResponse->assign("submitbutton","disabled",true);
     $objResponse->alert("Недопустимое значение курса!");
   }
  else
   {
  	 $objResponse->assign("submitbutton","disabled",false);
   }
	return $objResponse;
}

$xajax->registerFunction("check_exchange");
//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {

 if ($user->check_user_rules('view'))
  {

  $result_ = mysql_query("select currencies.* from currency_exchange,currencies where currency_exchange.exchange_value = 1 and currency_exchange.currency_id = currencies.currency_id");
  if (mysql_num_rows($result_) > 0)
   {
     $row_ = mysql_fetch_array($result_);
     $base_currency = $row_['currency_id'];
     $base_currency_name = $row_['currency_name'];
     $base_currency_descr = $row_['currency_descr'];

   $id = (int)$_GET['id'];
   $result = mysql_query("select * from currency_exchange,currencies where currencies.currency_id = currency_exchange.currency_id and currency_exchange.exchange_id=$id");
   if (!$result) {echo 'Ошибка базы данных!';exit();}
   $row = mysql_fetch_array($result);

   $date = strftime("%d.%m.%Y (%H:%M:%S)",$row['date']);
   $exchange_value = $row['exchange_value'];

   switch ($row['exchange_type'])
    {
      case 0: $exchange_type = 'FIXED'; break;
      case 1: $exchange_type = 'UPDATE'; break;
      default: $exchange_type = 'не определено'; break;
    }

 echo '<h2>'.htmlspecialchars($row['currency_name']).' &nbsp; '.$date.'</h2>';

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<form action="?id='.$id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td class="form">Валюта <span class="red">*</span></td>
      <td class="form">
       <select style="width:280px" name="currency_id" disabled>
         <option value="">Выберите валюту...</option>';
         $res = mysql_query("select * from currencies order by currency_name asc");
         if (mysql_num_rows($res) > 0)
          {
            while ($r = mysql_fetch_array($res))
             {
               if ($r['currency_id'] !== $base_currency)
                {
                   echo '<option value="'.$r['currency_id'].'"';
                   if ($r['currency_id'] == $row['currency_id']) echo ' selected';
                   echo '>'.htmlspecialchars($r['currency_name']);
                   if ($r['currency_descr']) echo ' &nbsp; ('.htmlspecialchars($r['currency_descr']).')';
                   echo '</option>';
                }
             }
          }
     echo '</select>
      </td>
    </tr>
    <tr>
      <td>Значение курса относительно базовой валюты <span class="red">*</span><br/><span class="small">Базовая валюта: '.htmlspecialchars($base_currency_name); if ($base_currency_descr) echo ' ('.htmlspecialchars($base_currency_descr).')'; echo '</span></td>
      <td><input style="width:280px" type="text" name="exchange_value" maxlength="10" value="'.$exchange_value.'"'; if ($exchange_value == 1) echo ' disabled'; echo ' onkeyup="xajax_check_exchange(document.getElementById(\'exchange_value\').value);" onKeyPress ="if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 46 || event.keyCode > 46)) event.returnValue = false;"></td>
    </tr>
    <tr>
      <td>Тип курса <span class="red">*</span></td>
      <td>
       <select style="width:280px" name="exchange_type" disabled>
         <option value="0"'; if ($exchange_type == 0) echo ' selected'; echo '>FIXED</option>
         <option value="1"'; if ($exchange_type == 1) echo ' selected'; echo '>UPDATE</option>
       </select>
      </td>
    </tr>
   </table><br /><button type="submit" id="submitbutton">Сохранить</button></form>';
 } else echo 'Базовая валюта не определена!';

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>