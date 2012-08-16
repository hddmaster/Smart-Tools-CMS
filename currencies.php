<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['currency_name']))
 {
  if ($user->check_user_rules('add'))
   {

  if (trim($_POST['currency_name'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

  $currency_name = $_POST['currency_name'];
  $currency_descr = $_POST['currency_descr'];

  $result = mysql_query("select * from currencies where currency_name = '".stripslashes($currency_name)."'");
  if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate"); exit();}

 //уникальная запись! Добавляем в каталог...
  $query = "insert into currencies values (null,
                                           '$currency_name',
                                           '$currency_descr',
                                           0)";
  $result = mysql_query($query);
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
   $currency_id = (int)$_GET['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
    }//delete

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update currencies set status=1 where currency_id=$currency_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         mysql_query("update currencies set status=0 where currency_id=$currency_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
 }
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Система</h1>';
$tabs = new Tabs;
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
if ($user->check_user_rules('view','/admin/currencies.php')) $tabs->add_tab('/admin/currencies.php', 'Валюты');
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
     echo '<span class="grey">* Базовая валюта: '.htmlspecialchars($row['currency_name']); if ($row['currency_descr']) echo ' ('.htmlspecialchars($row['currency_descr']).')'; echo '</span>';
   }
  else echo '<span class="red">Базовая валюта не определена!</span>'; 
 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить валюту</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Международное обозначение <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="currency_name" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="currency_descr" maxlength="255">
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td width="100%">&nbsp;</td>

   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></td></tr></table>
  </td></tr></table></form>';

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
    $sort_by = 'currency_id';
    $order = 'desc';
  }

 $add = '';
 $params = array();
 
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {

   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';

   $add .= " and (currency_id like '$query_str' or
           currency_name like '$query_str' or
           currency_descr like '$query_str')";
 }

 $query = "select * from currencies where 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=currency_id&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'currency_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=currency_id&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'currency_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Международное обозначение&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=currency_name&order=asc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'currency_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=currency_name&order=desc&page='.($page+1).'&per_page='.$per_page.'"><img src="/admin/images/'; if ($sort_by == 'currency_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=currency_descr&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'currency_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=currency_descr&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'currency_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td>Курс</td>
         <td width="120">&nbsp;</td>
       </tr>'."\n";

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['currency_id'].'</td>
           <td align="center">'.htmlspecialchars($row['currency_name']).'</td>
           <td align="center">'; if ($row['currency_descr']) echo htmlspecialchars($row['currency_descr']); else echo '&nbsp;'; echo '</td>
           <td align="center">';
           $res = mysql_query("select * from currency_exchange where currency_id = ".$row['currency_id']." order by exchange_id desc limit 1");
           if (mysql_num_rows($res) > 0)
            {
              $r = mysql_fetch_array($res);
              if ($r['exchange_value'] == '1') echo '<span class="grey">базовая валюта</span';
              else echo $r['exchange_value'];
            }
           else echo '<span class="grey">не задан</span>'; 
           echo '</td>
           <td nowrap align="center">
           <a href="javascript:sw(\'/admin/editors/edit_currency.php?id='.$row['currency_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать"></a>
           &nbsp;';
           if($row['status'] == 0) echo '<a href="?action=activate&id='.$row['currency_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
           else echo '<a href="?action=reserve&id='.$row['currency_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
           &nbsp;<a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&id=".$row['currency_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>'."\n";
   }
  echo '</table>'."\n";
 navigation($page, $per_page, $total_rows, $params);
}
else echo '<p align="center">Не найдено</p>';
 
 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>