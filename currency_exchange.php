<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['exchange_value']) && isset($_POST['exchange_type'])&& isset($_POST['currency_id']))
 {

 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['exchange_value'])=='' || trim($_POST['currency_id'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}
   $exchange_value = $_POST['exchange_value'];
   $exchange_type = $_POST['exchange_type'];
   $currency_id = $_POST['currency_id'];
   $date = date("YmdHis");

   $result = mysql_query("insert into currency_exchange values (null, $currency_id, $date, $exchange_value, $exchange_type)");
   if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   //Обновление кэша связанных модулей на сайте
   $cache = new Cache; $cache->clear_cache_by_module();

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
 }

if (isset($_GET['action']) && isset($_GET['id']))
 {
   $action = $_GET['action'];
   $exchange_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
        //проверка на присутствуе хотя бы одного
        $result = mysql_query("select * from currency_exchange where exchange_id = $exchange_id");
        if (mysql_num_rows($result) > 0)
         {
           $row = mysql_fetch_array($result);
           $currency_id = $row['currency_id'];
           $res = mysql_query("select * from currency_exchange where currency_id = $currency_id");
           if (mysql_num_rows($res) == 1) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use");exit();}
           else mysql_query("delete from currency_exchange where exchange_id=$exchange_id");
         }
      } else $user->no_rules('delete');
    }
 }

//-----------------------------------------------------------------------------
// AJAX

function check_exchange($exchange)
{
  $objResponse = new xajaxResponse();
  if ($exchange == 0 || $exchange == 1 || preg_match('/[\-,]/',$exchange))
   {
     $objResponse->assign("submitbutton","disabled",true);
     $objResponse->alert("Недопустимое значение курса!");
   }
  else $objResponse->assign("submitbutton","disabled",false);
  return $objResponse;
}

$xajax->registerFunction("check_exchange");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Система</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/auth.php')) $tabs->add_tab('/admin/auth.php', 'Пользователи');
if ($user->check_user_rules('view','/admin/auth_groups.php')) $tabs->add_tab('/admin/auth_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/auth_structure.php')) $tabs->add_tab('/admin/auth_structure.php', 'Структура');
if ($user->check_user_rules('view','/admin/auth_users.php')) $tabs->add_tab('/admin/auth_users.php', 'Типы пользователей');
if ($user->check_user_rules('view','/admin/auth_rules.php')) $tabs->add_tab('/admin/auth_rules.php', 'Права доступа');
if ($user->check_user_rules('view','/admin/auth_scripts.php')) $tabs->add_tab('/admin/auth_scripts.php', 'Файлы');
if ($user->check_user_rules('view','/admin/auth_script_groups.php')) $tabs->add_tab('/admin/auth_script_groups.php', 'Модули');
if ($user->check_user_rules('view','/admin/auth_history.php')) $tabs->add_tab('/admin/auth_history.php', 'История');
if ($user->check_user_rules('view','/admin/cache.php')) $tabs->add_tab('/admin/cache.php', 'Кэш');
if ($user->check_user_rules('view','/admin/languages.php')) $tabs->add_tab('/admin/languages.php', 'Языки');
if ($user->check_user_rules('view','/admin/currencies.php')) $tabs->add_tab('/admin/currencies.php', 'Валюты', 1);
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
if ($user->check_user_rules('view','/admin/currency_exchange.php')) $tabs2->add_tab('/admin/currency_exchange.php', 'Курсы');
$tabs2->show_tabs();

if ($user->check_user_rules('view'))
 {
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

  $result = mysql_query("select currencies.* from currency_exchange, currencies where currency_exchange.exchange_value = 1 and currency_exchange.currency_id = currencies.currency_id");
  if (mysql_num_rows($result) > 0)
   {
     $row = mysql_fetch_array($result);

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить курс</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Валюта <span class="red">*</span></td>
      <td>
        <select style="width:280px" name="currency_id">
        <option value="">Выберите валюту...</option>';
         $res = mysql_query("select * from currencies where status = 1 order by currency_name asc");
         if (mysql_num_rows($res) > 0)
          {
            while ($r = mysql_fetch_array($res))
             {
               if ($r['currency_id'] !== $row['currency_id'])
               echo '<option value="'.$r['currency_id'].'">'.htmlspecialchars($r['currency_name']);
               if ($r['currency_descr']) echo '&nbsp; ('.htmlspecialchars($r['currency_descr']).')';
               echo '</option>';
             }
          }
     echo '</select></td>
    </tr>
    <tr>
      <td>Значение курса относительно базовой валюты <span class="red">*</span><br/><span class="small">Базовая валюта: '.htmlspecialchars($row['currency_name']); if ($row['currency_descr']) echo ' ('.htmlspecialchars($row['currency_descr']).')'; echo '</span></td>
      <td><input style="width:280px" type="text" name="exchange_value" id="exchange_value" maxlength="10"  onkeyup="xajax_check_exchange(document.getElementById(\'exchange_value\').value);" onKeyPress ="if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 46 || event.keyCode > 46)) event.returnValue = false;"></td>
    </tr>
    <tr>
      <td>Тип курса <span class="red">*</span></td>
      <td>
       <select style="width:280px" name="exchange_type">
         <option value="0">FIXED</option>
         <option value="1">UPDATE</option>
       </select>
      </td>
    </tr></table><br /><button type="submit" id="submitbutton"><strong>Добавить</strong></button></form><br />
       <fieldset>
       <legend>Внимание!</legend>
         Базовая валюта определяется как валюта с курсом "1". Добавить или изменить курс базовой валюты через систему нельзя.<br/><br/>
         <strong>FIXED</strong>: фиксированный курс: текущим считается последний внесенный<br/>
         <strong>UPDATE</strong>: курс с автоматическим обновлением с биржи
       </fieldset></div></div>';
  } // если определена базовая валюта в базе


// постраничный вывод
 if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
 if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
 $start=abs($page*$per_page);

// сортировка
 if (isset($_GET['sort_by']) && isset($_GET['order']))
  {
    $sort_by = $_GET['sort_by'];
    $order  = $_GET['order'];
  }
 else
  {
    $sort_by = 'exchange_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
 $query = "select
           currency_exchange.*,
           date_format(currency_exchange.date, '%d.%m.%Y (%H:%i:%s)') as date_f,
           currencies.currency_name
           from
           currency_exchange, currencies
           where currencies.currency_id = currency_exchange.currency_id $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";

 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=exchange_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'exchange_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=exchange_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'exchange_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Курс&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=exchange_value&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'exchange_value' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=exchange_value&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'exchange_value' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Валюта&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=currency_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'currency_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=currency_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'currency_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Тип курса&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=exchange_type&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'exchange_type' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=exchange_type&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'exchange_type' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr onmouseover="this.style.backgroundColor='; echo "'#EEEEEE'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';"class="underline">
           <td align="center">'.$row['exchange_id'].'</td>
           <td align="center" nowrap>'.$row['date_f'].'</td>
           <td align="center">'; if ($row['exchange_value'] == 1) echo '<span class="small">базовая валюта</span>'; else echo $row['exchange_value']; echo '</td>
           <td align="center">'.htmlspecialchars($row['currency_name']).'</td>
           <td align="center">';
             switch ($row['exchange_type'])
              {
                case 0: echo 'FIXED'; break;
                case 1: echo 'UPDATE'; break;
                default: echo 'не определено'; break;
              }
           echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_currency_exchange.php?id='.$row['exchange_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать курс"></a>
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['exchange_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
  }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
  }
 
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>