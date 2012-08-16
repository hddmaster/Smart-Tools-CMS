<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['name'])) {
    if ($user->check_user_rules('add')) {

        if (trim($_POST['text'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues");exit();}
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $text = trim($_POST['text']);
   
        $parent_id = $_POST['parent_id'];
        $user_id = $_POST['user_id'];

        //Добавляем...
        $result = mysql_query(" insert into guestbook (parent_id, date, name, text, email, user_id)
			                    values
                                ($parent_id, now(), '$name', '$text', '$email', $user_id)");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}

   // перенумеровываем
   $result = mysql_query("select * from guestbook where parent_id = $parent_id order by order_id asc");
   if (@mysql_num_rows($result) > 0)
    {
      $i = 1;
      while ($row = mysql_fetch_array($result))
       {
         $id = $row['text_id'];
         mysql_query("update guestbook set order_id=$i where text_id = $id");
         $i++;
       }
    }

   //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $page = 1; $per_page = 20;
   if (isset($_GET['page'])) $page = $_GET['page'];
   if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
   Header("Location: ".$_SERVER['PHP_SELF']."?page=$page&per_page=$per_page");
   exit();
  } else $user->no_rules('add');
 }

if ((isset($_POST['action']) && isset($_POST['id'])) ||
    (isset($_GET['action']) && isset($_GET['id'])))
 {
   if (isset($_GET['action'])) $action = $_GET['action'];
   if (isset($_POST['action'])) $action = $_POST['action'];
   $elements = array();
   if (isset($_GET['id']))  $elements[] = (int)$_GET['id'];
   if (isset($_POST['id'])) $elements = $_POST['id'];

   if ($action == 'del')
    {
      if ($user->check_user_rules('delete'))
       {
         foreach($elements as $text_id)
	  {
            $result = mysql_query("delete from guestbook where text_id=$text_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
	  }
	 //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
      } else $user->no_rules('delete');
    }

   if ($action == 'activate')
    {
      if ($user->check_user_rules('action'))
       {
         foreach($elements as $text_id)
           mysql_query("update guestbook set status=1 where text_id=$text_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }
   if ($action == 'reserve')
    {
      if ($user->check_user_rules('action'))
       {
         foreach($elements as $text_id)
           mysql_query("update guestbook set status=0 where text_id=$text_id");
         //Обновление кэша связанных модулей на сайте
         $cache = new Cache; $cache->clear_cache_by_module();
       }
      else $user->no_rules('action');
    }

   unset($_POST);
 }
 
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Вопрос - ответ</h1>';
$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/guestbook.php')) $tabs->add_tab('/admin/guestbook.php', 'Сообщения');
if ($user->check_user_rules('view','/admin/guestbook_groups.php')) $tabs->add_tab('/admin/guestbook_groups.php', 'Группы');
if ($user->check_user_rules('view','/admin/guestbook_structure.php')) $tabs->add_tab('/admin/guestbook_structure.php', 'Структура');
$tabs->show_tabs();

if ($user->check_user_rules('view'))
 {

 function show_select($parent_id = 0, $prefix = '', $parent_id_added)
  {
    global $options;
    $result = mysql_query("select * from guestbook where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['text_id'].'"';
          if ($parent_id_added == $row['text_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['name']).'</option>'."\n";
          show_select($row['text_id'],$prefix.'&nbsp;&nbsp;&nbsp;', $parent_id_added);
        }
    }
    return $options;
  }

 function show_select_filter($parent_id = 0, $prefix = '', $parent_id_element = '')
  {
    global $options;
    $result = mysql_query("select * from guestbook where parent_id = $parent_id and type = 1 order by order_id asc");
    if(@mysql_num_rows($result) > 0)
    {
      while ($row = mysql_fetch_array($result))
        {
          $options .= '<option value="'.$row['text_id'].'"';
          if ($parent_id_element == $row['text_id']) $options .= ' selected';
          $options .= '>'.$prefix.htmlspecialchars($row['name']).'</option>'."\n";
          show_select_filter($row['text_id'],$prefix.'&nbsp;&nbsp;&nbsp;',$parent_id_element);
        }
    }
    return $options;
  }

 $parent_id_added = 0; if (isset($_GET['parent_id'])) $parent_id_added = $_GET['parent_id'];

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить сообщение</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Имя</td>
      <td><input style="width:280px" type="text" name="name" maxlength="255"></td></tr>
    <tr>
      <td>e-mail</td>
      <td><input style="width:280px" type="text" name="email" maxlength="255"></td></tr>
    <tr>
      <td>Текст вопроса <sup class="red">*</sup></td>
      <td><textarea style="width:280px" name="text" cols="52" rows="3"></textarea></td></tr>
    <tr>
      <td>Расположение<br><span class="grey">Выберите группу-родителя</span></td>
      <td><select name="parent_id" style="width:280px;">
            <option value="0">---Корень каталога---</option>
            '.show_select(0,'',$parent_id_added).'
          </select>'; global $options; $options = ''; echo '
      </td>
    </tr>
    <tr>
      <td>Пользователь сайта</td>
      <td><select name="user_id" style="width:280px;">
            <option value="0">---НЕТ---</option>';
      $res = mysql_query("select * from auth_site where type = 0 order by username asc");
      if(mysql_num_rows($res) > 0)
       {
         while($r = mysql_fetch_object($res))
            echo '<option value="'.$r->user_id.'">'.htmlspecialchars($r->username).'</option>';
       }
      echo '</select>
      </td>
    </tr>
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';
  
global $options; $options = '';
$parent_id = -1; if (isset($_GET['parent_id_filter']) && trim($_GET['parent_id_filter']) !== '') $parent_id = $_GET['parent_id_filter'];
echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0" border="0" width="100%">
   <tr>

   <td nowrap>
   <form action="" method="GET">

   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td nowrap>Фильтр по группе</td>
      <td><select name="parent_id" style="width:280px;">
            <option value="">---Весь каталог---</option>
            <option value="0"'; if (isset($_GET['parent_id']) && ($parent_id === 0 || $parent_id == 0)) echo ' selected'; echo'>---Корень каталога---</option>
            '.show_select_filter(0,'',$parent_id).'
          </select>'; global $options; $options = ''; echo '
      </td>
      <td><button type="SUBMIT">OK</button></td>
    </tr>
  </table>
  
   </td>

   <td width="100%">&nbsp;</td>

   <td>
   <table cellspacing="0" cellpadding="4" border="0">
   <tr><td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars($_GET['query_str']); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table></td></tr></table>
  </td></tr></table></form>';

// постраничный вывод
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'date');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();
 
if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {
   $params['query_str'] = strtolower(trim($_GET['query_str']));
   $query_str = '%'.strtolower(trim($_GET['query_str'])).'%';

   $add .= " and (text_id like '$query_str' or
           name like '$query_str' or
           email like '$query_str' or
           text like '$query_str' or
           text_answ like '$query_str')";
 }

 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

	$query = "	select
				G.*,
				date_format(G.date, '%d.%m.%Y (%H:%i:%s)') as date_f,
				A.username
				from
				guestbook as G left join auth_site as A on G.user_id = A.user_id
				where
				G.type = 0
				$add";
	$result = mysql_query($query); $total_rows = mysql_num_rows($result);          
	$result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (@mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<form id="form" method="post">';
 echo '<p align="right"><button type="submit">Сохранить</button></p>';
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">';
 echo '<tr align="center" class="header">
         <td align="left" nowrap width="80"><input id="maincheck" type="checkbox" value="0" onclick="if($(\'#maincheck\').attr(\'checked\')) $(\'.cbx\').attr(\'checked\', true); else $(\'.cbx\').attr(\'checked\', false);"> №&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=text_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'text_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=text_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'text_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Дата&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=date&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'date' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Группа</td>
         <td nowrap>Имя&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>e-mail&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=email&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'email' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=email&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'email' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Сообщение&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=text&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'text' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=text&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'text' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Ответ&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=text_answ&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'text_answ' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=text_answ&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'text_answ' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td>Пользователь сайта</td>
         <td width="120">&nbsp;</td>
       </tr>';

	while ($row = mysql_fetch_array($result)) {
		echo '	<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
					<td align="left" class="small"><input class="cbx" type="checkbox" name="id[]" value="'.$row['text_id'].'"> '.$row['text_id'].'</td>
					<td align="center">'; if (substr($row['date_f'],0,10) == date("d.m.Y")) echo '<span class="green">'.$row['date_f'].'</span>';
						else echo $row['date_f'];
						echo '</td>
					<td>';
					if ($row['parent_id'] == 0) echo '---Корень каталога---';
					else
						{
						$res = mysql_query("select * from guestbook where text_id = ".$row['parent_id']);
						if (mysql_num_rows($res) > 0)
						{
							$r = mysql_fetch_array($res);
							echo htmlspecialchars($r['name']);
						}
						else echo '&nbsp;';
						}
					echo '</td>
					<td align="center">'.(($row['name']) ? htmlspecialchars($row['name']) : '&nbsp;').'</td>
					<td align="center">'.(($row['email']) ? htmlspecialchars($row['email']) : '&nbsp;').'</td>
					<td><div class="text"><span class="small">'.$row['text'].'</span></div></td>
					<td>'; if ($row['text_answ'] !== '') echo '<div class="text"><span class="small">'.$row['text_answ'].'<span></div>'; else echo '&nbsp;'; echo '</td>
					<td align="center">'.(($row['username']) ? htmlspecialchars($row['username']) : '&nbsp;').'</td>
					<td nowrap align="center"><a href="javascript:sw(\'/admin/editors/edit_text.php?id='.$row['text_id'].'\');"><img align="absmiddle" src="/admin/images/icons/pencil.png" border="0" alt="Редактировать сообщение"></a>
					&nbsp;';
					if($row['status'] == 0) echo '<a href="guestbook.php?action=activate&id='.$row['text_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb-off.png" border="0" alt="Активность"></a>';
					else echo '<a href="?action=reserve&id='.$row['text_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img align="absmiddle" src="/admin/images/icons/light-bulb.png" border="0" alt="Активность"></a>'; echo '
					&nbsp;<a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['text_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'\';}"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
				</tr>';
	}
  
	echo '	</table>';
	echo '	<input type="hidden" name="action" id="action" value="">
			<table cellspacing="0" cellpadding="4">
				<tr>
					<td style="padding-left: 6px;"><img src="/admin/images/tree/2.gif" alt=""></td>
					<td class="small" nowrap>с отмеченными:</td>
					<td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'activate\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/light-bulb.png" alt="Включить" border="0"></a></td>
					<td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'reserve\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/light-bulb-off.png" alt="Выключить" border="0"></a></td>
					<td><a style="cursor: pointer;" onclick="document.getElementById(\'action\').value = \'del\'; document.getElementById(\'form\').submit();"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td>
				</tr>
			</table>';  
	echo '	</div>';
	echo '	<p align="right"><button type="submit">Сохранить</button></p>';
	echo '	</form>';
	navigation($page, $per_page, $total_rows, $params);
} else echo '<p align="center">Не найдено</p>';
} else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>