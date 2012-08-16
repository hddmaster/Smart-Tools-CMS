<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['option_name']) && isset($_POST['option_descr']))
 {
 if ($user->check_user_rules('add'))
  {
   if (trim($_POST['option_name'])=='' || trim($_POST['option_type'])=='') {Header("Location: ".$_SERVER['PHP_SELF']."?message=formvalues"); exit();}

   $option_name = mysql_real_escape_string(trim($_POST['option_name']));
   $option_descr = mysql_real_escape_string(trim($_POST['option_descr']));
   $option_type = ((isset($_POST['option_type']) && (int)$_POST['option_type'] > 0) ? (int)$_POST['option_type'] : 0);
   $unit_id = ((isset($_POST['unit_id']) && (int)$_POST['unit_id'] > 0) ? (int)$_POST['unit_id'] : 0);

   // проверка а повторное название
   if (use_field($option_name,'shop_cat_options','option_name')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=duplicate");exit();}

   //Добавляем...
   $result = mysql_query("insert into shop_cat_options
			  (option_name, option_descr, option_type, unit_id)
			  values
			  ('$option_name', '$option_descr', $option_type, $unit_id)");
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
   $option_id = (int)$_GET['id'];

   if ($action == 'del')
    {
     if ($user->check_user_rules('delete'))
      {
        if (use_field($option_id, 'shop_cat_card_options', 'option_id')) {Header("Location: ".$_SERVER['PHP_SELF']."?message=use"); exit();}

        $result = mysql_query("delete from shop_cat_card_options where option_id=$option_id");
        $result = mysql_query("delete from shop_cat_option_values where option_id=$option_id");
        $result = mysql_query("delete from shop_cat_options where option_id=$option_id");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?message=db"); exit();}
	
	//Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

      } else $user->no_rules('delete');
    }
 }

require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_header.php');

echo '<h1>Магазин</h1>';
$tabs = new Tabs;
$tabs->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs->add_tab('/admin/shop_catalog.php', 'Каталог', 1);
if ($user->check_user_rules('view','/admin/shop_incoming.php')) $tabs->add_tab('/admin/shop_incoming.php', 'Склад');
if ($user->check_user_rules('view','/admin/shop_orders.php')) $tabs->add_tab('/admin/shop_orders.php', 'Заказы');
if ($user->check_user_rules('view','/admin/shop_ym.php')) $tabs->add_tab('/admin/shop_ym.php', 'Интеграция');
$tabs->show_tabs();

$tabs2 = new Tabs;
$tabs2->level = 1;
$tabs2->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_catalog.php')) $tabs2->add_tab('/admin/shop_catalog.php', 'Товары', 1);
if ($user->check_user_rules('view','/admin/shop_cat_groups.php')) $tabs2->add_tab('/admin/shop_cat_groups.php', 'Группы');
$tabs2->show_tabs();

$tabs3 = new Tabs;
$tabs3->level = 2;
$tabs3->auto_detect_page = false;
if ($user->check_user_rules('view','/admin/shop_cat_structure_elements.php')) $tabs3->add_tab('/admin/shop_cat_structure_elements.php', 'Структура');
if ($user->check_user_rules('view','/admin/shop_cat_grids.php')) $tabs3->add_tab('/admin/shop_cat_grids.php', 'Свойства');
if ($user->check_user_rules('view','/admin/shop_cat_cards.php')) $tabs3->add_tab('/admin/shop_cat_cards.php', 'Карточки описаний', 1);
if ($user->check_user_rules('view','/admin/shop_cat_producers.php')) $tabs3->add_tab('/admin/shop_cat_producers.php', 'Производители');
if ($user->check_user_rules('view','/admin/shop_cat_sites.php')) $tabs3->add_tab('/admin/shop_cat_sites.php', 'Сайты');
if ($user->check_user_rules('view','/admin/shop_cat_actions.php')) $tabs3->add_tab('/admin/shop_cat_actions.php', 'Акции');
if ($user->check_user_rules('view','/admin/shop_cat_spec.php')) $tabs3->add_tab('/admin/shop_cat_spec.php', 'Спецпредложения');
if ($user->check_user_rules('view','/admin/shop_cat_comments.php')) $tabs3->add_tab('/admin/shop_cat_comments.php', 'Комментарии');
if ($user->check_user_rules('view','/admin/shop_cat_publications.php')) $tabs3->add_tab('/admin/shop_cat_publications.php', 'Публикации');
$tabs3->show_tabs();

$tabs4 = new Tabs;
$tabs4->level = 3;
if ($user->check_user_rules('view','/admin/shop_cat_options.php')) $tabs4->add_tab('/admin/shop_cat_options.php', 'Характеристики');
$tabs4->show_tabs();

if ($user->check_user_rules('view'))
 {

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h2 class="nomargins">Добавить характеристику</h2></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form action="" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название <sup class="red">*</sup></td>
      <td>
       <input style="width:280px" type="text" name="option_name" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Описание</td>
      <td>
       <input style="width:280px" type="text" name="option_descr" maxlength="255">
      </td>
    </tr>
    <tr>
      <td>Тип <sup class="red">*</sup></td>
      <td>
        <select style="width:280px;" name="option_type">
          <option value="">Выберите тип параметра...</option>
          <option value="1">INT (целое число)</option>
          <option value="2">DOUBLE (число с плавающей точкой)</option>
          <option value="3">BOOLEAN (да/нет)</option>
          <option value="4">CHAR (строка)</option>
          <option value="5">TEXT (текст)</option>
          <option value="6">ARRAY (спаравочник)</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>Единица измерения</td>
      <td><select name="unit_id" style="width:280px;">
              <option value="0">---НЕТ---</option>';
      $res = mysql_query("select * from shop_units_of_measure order by unit_name asc");
      if (mysql_num_rows($res) > 0)
        while ($r = mysql_fetch_array($res))
          echo '<option value="'.$r['unit_id'].'">'.htmlspecialchars($r['unit_name']).(($r['unit_descr']) ? ' &nbsp; ('.htmlspecialchars($r['unit_descr']).')' : '').'</option>';
    echo '</td>
    </tr> 
   </table><br>
   <button type="SUBMIT">Добавить</button>
  </form><br /></div></div>';

echo '<form action="" method="GET">
  <table cellspacing="0" cellpadding="0"  width="100%">
   <tr>

   <td width="50%" style="padding-right: 10px;">
   </td>

   <td width="50%" style="padding-left: 10px;">

   <table cellspacing="0" cellpadding="4" align="right">
    <tr>
      <td><img src="/admin/images/icons/magnifier.png" alt=""></td><td>
      <td><input style="width:280px" type="text" name="query_str" value="'; if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '') echo htmlspecialchars(stripcslashes($_GET['query_str'])); echo '"></input></td>
      <td><button type="SUBMIT">Найти</button></td>
    </tr>
  </table>
  
  </td></tr></table></form>';

 $units = array();
 $res = mysql_query("select * from shop_units_of_measure");
 if(mysql_num_rows($res) > 0)
  {
    while ($r = mysql_fetch_object($res))
      $units[$r->unit_id] = array($r->unit_name, $r->unit_descr);
  }
  
// постраничный вывод
 if (isset($_GET['page'])) $page=($_GET['page']-1); else $page=0;
 if (isset($_GET['per_page'])) $per_page=($_GET['per_page']); else $per_page=20;
 $start=abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'option_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();

if (isset($_GET['query_str']) && trim($_GET['query_str']) !== '')
 {
   $params['query_str'] = mb_strtolower(trim($_GET['query_str']),'UTF-8');
   $query_str = '%'.mb_strtolower(trim($_GET['query_str']),'UTF-8').'%';
   $add .= " and (option_name like '$query_str' or
                  option_descr like '$query_str')";
 }
 
 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

 $query = "select * from shop_cat_options where 1 $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");

 if (mysql_num_rows($result) > 0)
 {
 navigation($page, $per_page, $total_rows, $params);
 echo '<div class="databox"><table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'option_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'option_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Название&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_name&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'option_name' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_name&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'option_name' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Описание&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_descr&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'option_descr' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=option_descr&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'option_descr' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Карточки описаний</td>
	 <td>Тип</td>
	 <td nowrap>Единица измерения</td>
         <td width="120">&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
   echo '<tr valign="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
           <td align="center">'.$row['option_id'].'</td>
           <td><strong><a href="#" onclick="sw(\'/admin/editors/edit_cat_option.php?id='.$row['option_id'].'\'); return false;">'.htmlspecialchars($row['option_name']).'</a></strong></td>
           <td align="center">'; if(!$row['option_descr']) echo '&nbsp;'; else echo htmlspecialchars($row['option_descr']); echo '</td>
           <td class="grey">';
	   
	   $res = mysql_query("select * from shop_cat_cards as C, shop_cat_card_options as CO where C.card_id = CO.card_id and CO.option_id = ".$row['option_id']." order by C.card_name asc") or die(mysql_error());
	   if (mysql_num_rows($res) > 0)
	    {
	      $i = 1;
	      while ($r = mysql_fetch_object($res))
	       {
		 echo '<a class="grey" href="javascript:sw(\'/admin/editors/edit_shop_cat_card.php?id='.$r->card_id.'\');">'.htmlspecialchars($r->card_name).'</a>';
	         if($i < mysql_num_rows($res)) echo ', ';
		 $i++;
	       }
	    } else echo '&nbsp;';
	   
	   echo '</td>
	   <td align="center">';
	   
	   switch ($row['option_type'])
	    {
	      case 1: echo 'INT (целое число)'; break;
	      case 2: echo 'DOUBLE (число с плавающей точкой)'; break;
	      case 3: echo 'BOOLEAN (да/нет)'; break;
	      case 4: echo 'CHAR (строка)'; break;
	      case 5: echo 'TEXT (текст)'; break;
	      case 6: echo 'ARRAY (справочник)'; break;
	      default: echo '&nbsp;'; break;
	    }
	   
	   echo '</td>
	   <td align="center">';
	   
	   if(array_key_exists($row['unit_id'], $units))
	    {
	      list($unit_name, $unit_descr) = $units[$row['unit_id']];
	      echo htmlspecialchars($unit_name);
	      if($unit_descr) echo '<br /><span class="small">'.htmlspecialchars($unit_descr).'</span>';
	    }
	   else echo '&nbsp;';
	   
	   echo '</td><td nowrap align="center"><a href="#" onclick="if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'?action=del&id='.$row['option_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params.'\';} return false;"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
  echo '</table></div>';
 navigation($page, $per_page, $total_rows, $params);
  }

 } else $user->no_rules('view');
require_once($_SERVER['DOCUMENT_ROOT'].SMART_TOOLS_PATH.'/tpl/admin_footer.php');
?>