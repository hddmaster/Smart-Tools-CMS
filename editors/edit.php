<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (    isset($_GET['id']) &&
        isset($_POST['page_name']) &&
        isset($_POST['tpl_id']) &&
        isset($_POST['page_url']) &&
        isset($_POST['page_title']) &&
        isset($_POST['page_meta_keyw']) &&
        isset($_POST['page_meta_descr'])) {
    if ($user->check_user_rules('edit')) {
        $page_id = (int)$_GET['id'];
        if (trim($_GET['id'])=='' || 
        trim($_POST['page_name'])=='' || 
        trim($_POST['tpl_id'])=='' || 
        trim($_POST['page_title'])== '' ||
        trim($_POST['parent_id'])== '') {Header("Location: ".$_SERVER['PHP_SELF']."?id=$page_id&message=formvalues"); exit();}

        $page_name = trim($_POST['page_name']);
        $page_menu_name = trim($_POST['page_menu_name']);
        $page_url = trim($_POST['page_url']);
        $page_url_old = $_POST['page_url_old'];
        $parent_id = $_POST['parent_id'];
        $parent_id_old = $_POST['parent_id_old'];
        if (preg_match('/[А-я\.\\/]+/iu', $page_url)) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$page_id&message=formvalues"); exit();}
        $tpl_id = $_POST['tpl_id'];
        $page_title = trim($_POST['page_title']);
        $page_meta_keyw = ''; if (isset($_POST['page_meta_keyw'])) $page_meta_keyw = trim($_POST['page_meta_keyw']);
        $page_meta_descr = ''; if (isset($_POST['page_meta_descr'])) $page_meta_descr = trim($_POST['page_meta_descr']);

        $result = mysql_query("
                                update
                                pages
                                set
                                page_name = '$page_name', 
                                page_menu_name = '$page_menu_name', 
                                page_url = '$page_url', 
                                tpl_id = $tpl_id,
                                page_title = '$page_title', 
                                page_meta_keyw = '$page_meta_keyw', 
                                page_meta_descr = '$page_meta_descr'
                                where
                                page_id = $page_id") or die(mysql_error());
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$page_id&message=db"); exit();}
    
        if ($parent_id !== $parent_id_old) {
            $page = new Site_generate;
            $page->get_pages($page_id);
            $page->pages = array_reverse($page->pages);
        
            foreach ($page->pages as $id)
                $page->page_delete($id, false);
            $page->page_delete($page_id, false);
        
            $result = mysql_query("update pages set parent_id='$parent_id' where page_id=$page_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$page_id&message=db"); exit();}
        
            $page->page_create($page_id);
            $page->pages = array_reverse($page->pages);
            foreach ($page->pages as $id)
                $page->page_create($id, false);
        }

        $cache = new Cache;
        $cache->clear_all_cache();

        $_SESSION['smart_tools_refresh'] = 'enable';
    
        header("Location: ".$_SERVER['PHP_SELF']."?id=$page_id");
        exit();
    } else $user->no_rules('edit');
}

//-----------------------------------------------------------------------------
// AJAX
define('PAGE_ID',$_GET['id']);

function show_block_objects($block_id)
 {
   $objResponse = new xajaxResponse();
   $text = '';
   $page_objects = '';
   
   $result = mysql_query("select * from pages where page_id = ".PAGE_ID);
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $objects = unserialize($row['objects']);
      
      if (isset($objects[$block_id]))
       {
         $text .= '<table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
         $text .= '<tr align="center" class="header">
               <td nowrap>Текст / Модуль</td>
               <td nowrap>Шаблон</td>
               <td nowrap>Кэширование</td>
               <td nowrap>&nbsp;</td>
               </tr>';
         
         $i = 1;
         foreach ($objects[$block_id] as $key => $block_values)
          {
            $type = 0;
            $text .= '<tr align="center" onmouseover="this.style.backgroundColor=\'#EFEFEF\'" onmouseout="this.style.backgroundColor=\'white\'" class="underline">
                       <td>';
			$res = mysql_query("select * from content where obj_id = ".$block_values[0]);
			if (mysql_num_rows($res) > 0)
			 {
			   $r = mysql_fetch_array($res);
			   $text .= '<a class="';
			   if ($r['type'] == 1) $text .= 'green';
			   $type = $r['type'];
			   $text .= '" href="javascript:sw(\'/admin/editors/edit_content.php?id='.$r['obj_id'].'\');">'.htmlspecialchars($r['content_name']).'</a>';
			 }
			$text .= ' </td><td>'; 
     	                $res = mysql_query("select * from templates where template_id = ".$block_values[1]);
			if (mysql_num_rows($res) > 0)
			 {
			   $r = mysql_fetch_array($res);
			   $text .= htmlspecialchars($r['template_name']);
			 }
			else $text .= '&nbsp;';
                        $text .= ' </td><td>';
                        if ($type == 1)
                         {
                           $value = 0; if ($block_values[2] == 1) $value = 1;
			   $text .= '<input type="checkbox" name="cache['.$block_id.']['.$key.']" onclick="xajax_cache_object('.$key.','.$block_id.','.$value.');"';
                           if ($block_values[2] == 1) $text .= ' checked';
                           $text .= '>';
                         } else $text .= '<input type="checkbox" name="cache['.$block_id.']['.$key.']" disabled>';
                        $text .= '</td><td nowrap>';

            //если элемент первый блокируем стрелку "вверх"
            if ($i == 1) $text .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-090.png" alt="">';
            else $text .= '<img style="cursor: pointer; cursor: hand;" onclick="xajax_move_up_object('.$key.','.$block_id.');" align="absmiddle" src="/admin/images/icons/arrow-090.png" alt="Вверх" border="0">';
            if ($i == count($objects[$block_id])) $text .= '<img align="absmiddle" src="/admin/images/icons/arrow-stop-270.png" alt="">';
            else $text .= '<img style="cursor: pointer; cursor: hand;" onclick="xajax_move_down_object('.$key.','.$block_id.');" align="absmiddle" src="/admin/images/icons/arrow-270.png" alt="Вниз" border="0">';
            $text .= '<img style="cursor: pointer; cursor: hand;" onclick="javascript:if(confirm(\'Вы действительно хотите удалить?\')){xajax_delete_object('.$key.','.$block_id.')}" align="absmiddle" src="/admin/images/icons/cross.png" alt="Удалить" border="0">';
			$text .=  '</td>
					  </tr>';
		    $i++;			  
          }
         $text .= '</table><br />';
      }
    }

   $objResponse->assign("block_objects_$block_id","innerHTML",$text);
   return $objResponse;
 }

function show_templates($id, $block_id)
 {
   $objResponse = new xajaxResponse();
   $t = '';
   $text = '';
   $button = '';
   $type = 0;
   
   $result = mysql_query("select type from content where obj_id = $id");
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $type = $row['type'];
    }

   if ($type == 1)
   {
     $t = '<span class="small">Шаблон: &nbsp; </span>';
     $text .= '<select style="width:280px;" name="template"><option value="">---НЕТ---</option>';
     $res = mysql_query("select * from templates order by template_name asc");
     if (mysql_num_rows($res) > 0)
      {
        while ($r = mysql_fetch_array($res))
          $text .= '<option value="'.$r['template_id'].'">'.htmlspecialchars($r['template_name']).'</option>';
      }
     $text .= '</select>';
	 $button = '<button type="button" onclick="xajax_add_block_object(this.form.content.options[this.form.content.selectedIndex].value,this.form.template.options[this.form.template.selectedIndex].value,'.$block_id.')">OK</button>'; 
   }
   else
	 $button = '<button type="button" onclick="xajax_add_block_object(this.form.content.options[this.form.content.selectedIndex].value,0,'.$block_id.')">OK</button>'; 

   $objResponse->assign("templates_text_$block_id","innerHTML",$t);
   $objResponse->assign("templates_$block_id","innerHTML",$text);
   $objResponse->assign("block_button_$block_id","innerHTML",$button);
   return $objResponse;
 }
 
function add_block_object($content_id, $template_id, $block_id)
 {
   $objResponse = new xajaxResponse();
   $text = '';
   
   if(!$template_id) $template_id = 0;  

   $result = mysql_query("select * from pages where page_id = ".PAGE_ID);
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $objects = unserialize($row['objects']);
      $objects[$block_id][] = array($content_id, $template_id, 0);
      mysql_query("update pages set objects = '".serialize($objects)."' where page_id = ".PAGE_ID);      
    }
   
   $page = new Site_generate;
   $page->page_create(PAGE_ID);
   $objResponse->script("xajax_show_block_objects($block_id);");
   return $objResponse;
 }
 
function move_up_object($id, $block_id)
 {
   $objResponse = new xajaxResponse();
   $result = mysql_query("select * from pages where page_id = ".PAGE_ID);
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $objects = unserialize($row['objects']);
      
      $prev_id = 0; $flag = false;
      foreach($objects[$block_id] as $key => $value)
       {
	 if ($key == $id) $flag = true;
         if (!$flag) $prev_id = $key;
       } 

      $tmp_values = $objects[$block_id][$id];
      $objects[$block_id][$id] = $objects[$block_id][$prev_id];
      $objects[$block_id][$prev_id] = $tmp_values;
      mysql_query("update pages set objects = '".serialize($objects)."' where page_id = ".PAGE_ID);      
    }

   $page = new Site_generate;
   $page->page_create(PAGE_ID);
   $_SESSION['smart_tools_refresh'] = 'enable';
   $objResponse->script("xajax_show_block_objects($block_id);");
   return $objResponse;
 }

function move_down_object($id, $block_id)
 {
   $objResponse = new xajaxResponse();
   $result = mysql_query("select * from pages where page_id = ".PAGE_ID);
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $objects = unserialize($row['objects']);
      
      $next_id = 0; $flag = false;
      foreach($objects[$block_id] as $key => $value)
       {
         if ($flag) {$next_id = $key; $flag = false;}
	 if ($key == $id) $flag = true;
       } 

      $tmp_values = $objects[$block_id][$id];
      $objects[$block_id][$id] = $objects[$block_id][$next_id];
      $objects[$block_id][$next_id] = $tmp_values;
      mysql_query("update pages set objects = '".serialize($objects)."' where page_id = ".PAGE_ID);      
    }

   $page = new Site_generate;
   $page->page_create(PAGE_ID);
   $_SESSION['smart_tools_refresh'] = 'enable';
   $objResponse->script("xajax_show_block_objects($block_id);");
   return $objResponse;
 }

function delete_object($id, $block_id)
 {
   $objResponse = new xajaxResponse();
   $result = mysql_query("select * from pages where page_id = ".PAGE_ID);
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $objects = unserialize($row['objects']);
      unset($objects[$block_id][$id]);
      if (count($objects[$block_id]) == 0) unset($objects[$block_id]);
	  mysql_query("update pages set objects = '".serialize($objects)."' where page_id = ".PAGE_ID);      
    }

   $page = new Site_generate;
   $page->page_create(PAGE_ID);
   $objResponse->script("xajax_show_block_objects($block_id);");
   return $objResponse;
 }

function cache_object($id, $block_id, $value)
 {
   $objResponse = new xajaxResponse();
   $result = mysql_query("select * from pages where page_id = ".PAGE_ID);
   if (mysql_num_rows($result) > 0)
    {
      $row = mysql_fetch_array($result);
      $objects = unserialize($row['objects']);
      if ($value == 0) $objects[$block_id][$id][2] = 1;
      if ($value == 1)
       {
         $objects[$block_id][$id][2] = 0;
         $cache = new Cache;
         $cache->clear_cache_by_content($id);
       }
      mysql_query("update pages set objects = '".serialize($objects)."' where page_id = ".PAGE_ID);      
    }

   $page = new Site_generate;
   $page->page_create(PAGE_ID);
   $objResponse->script("xajax_show_block_objects($block_id);");
   return $objResponse;
 }

$xajax->registerFunction("show_block_objects");
$xajax->registerFunction("show_templates");
$xajax->registerFunction("add_block_object");
$xajax->registerFunction("move_up_object");
$xajax->registerFunction("move_down_object");
$xajax->registerFunction("delete_object");
$xajax->registerFunction("cache_object");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if (isset($_GET['id']) && $_GET['id'] != '')
 {
 if ($user->check_user_rules('view'))
  {

    $page_id = intval($_GET['id']);
    $result = mysql_query("select * from pages where page_id=$page_id");
    $row = mysql_fetch_array($result);

    $cache = $row['cache'];
    $tpl_id = $row['tpl_id'];
    $img_path = '';
    $template = '';
    $res = mysql_query("select * from designs where tpl_id = $tpl_id");
    if (mysql_num_rows($res) > 0)
     {
       $r = mysql_fetch_array($res);
       $img_path = $r['img_path'];
       $template = $r['data'];
     }
   
if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }

function get_tree(&$tree)
 {
   $result = mysql_query("select * from pages order by order_id asc");
   if(mysql_num_rows($result) > 0)
     while ($row = mysql_fetch_object($result))
       $tree[$row->parent_id][$row->page_id] = $row->page_name;
 }
$tree = array(); get_tree($tree);

function show_select($parent_id = 0, $prefix = '', $selected_page_id = 0, &$tree, $current_page_id = 0)
 {
   global $options;
   foreach($tree[$parent_id] as $page_id => $page_name)
    {
      if ($page_id !== $current_page_id)
       {
         $options .= '<option value="'.$page_id.'"'.($selected_page_id == $page_id ? ' selected' : '').'>'.
                     $prefix.htmlspecialchars($page_name).'</option>';
         show_select($page_id, $prefix.'&nbsp;&nbsp;&nbsp;', $selected_page_id, $tree, $current_page_id);
       }
    }
   return $options;
 }

    echo '<form action="?id='.$page_id.'" method="post">
   <input type="hidden" name="page_url_old" value="'.htmlspecialchars($row['page_url']).'">
   <input type="hidden" name="parent_id_old" value="'.$row['parent_id'].'">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название страницы <sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="page_name" maxlength="255" value="'.htmlspecialchars($row['page_name']).'"></td></tr>
    <tr>
      <td>Название страницы в меню<sup class="red">*</sup></td>
      <td><input style="width:280px" type="text" name="page_menu_name" maxlength="255" value="'.htmlspecialchars($row['page_menu_name']).'"></td></tr>
    <tr>
      <td>Заголовок страницы<sup class="red">*</sup><br /><span class="grey">TITLE</span></td>
      <td><input style="width:280px" type="text" name="page_title" maxlength="255" value="'.htmlspecialchars($row['page_title']).'"></td></tr>
    <tr>
      <td>Ссылка<sup class="red">*</sup><br /><span class="grey">URL</span></td>
      <td><input style="width:280px" type="text" name="page_url" maxlength="255" value="'.htmlspecialchars($row['page_url']).'"></td></tr>
    <tr>
      <td>Ключевае слова<br /><span class="grey">meta keywords</span></td>
      <td><input style="width:280px" type="text" name="page_meta_keyw" maxlength="255" value="'.htmlspecialchars($row['page_meta_keyw']).'"></td></tr>
    <tr>
      <td>Описание<br /><span class="grey">meta description</span></td>
      <td><input style="width:280px" type="text" name="page_meta_descr" maxlength="255" value="'.htmlspecialchars($row['page_meta_descr']).'"></td></tr>
    <tr>
      <td>Шаблон страницы <sup class="red">*</sup></td>
      <td>';
      $res = mysql_query("select * from designs order by tpl_name asc");
      if (mysql_num_rows($res) > 0)
       {
	 echo '<select style="width:280px" name="tpl_id">';
         while ($r = mysql_fetch_array($res))
           echo '<option value="'.$r['tpl_id'].'" '.(($tpl_id == $r['tpl_id']) ? 'selected' : '').'>'.htmlspecialchars($r['tpl_name']).'</option>'."\n";
         echo '</select>';
       }
    echo '</td></tr>
    <tr>
      <td>Расположение <sup class="red">*</sup><br /><span class="grey">Выберите страницу-родителя</span></td>
      <td><select style="width:280px" name="parent_id"><option value="0">---Корень сайта---</option>'.show_select(0, '', $row['parent_id'], $tree, $page_id).'</select></td>
    </tr>
   </table><br>
   <button type="submit">Сохранить</button>
  </form><br />';
  

  if (preg_match_all('/\{BLOCK_(\d{1,3})\s*?(,\s*?\'([\s.,;!?"\wа-яА-Я()-:]+)?\')?\}/ius', $template, $matches))
   {
     echo '<fieldset><legend>Верстка страницы</legend>';
     $i = 0;
     $keys = array();
     foreach ($matches[0] as $match)
	  {
           $key = $matches[1][$i]; $keys[] = $key;
           if (preg_match_all('/\{BLOCK_'.$key.'\}.*<\!DOCTYPE/ius', $template, $matches_block)) $nocache = true; else $nocache = false;

	   echo '<fieldset><legend>Поле №'.$key;
           if ($nocache) echo ' &nbsp; <span class="red">ПРОЦЕССОР</span>';
           if (isset($matches[3][$i]) && $matches[3][$i] !== '') echo ' | <span class="grey">'.$matches[3][$i].'</span>';
           echo '</legend>';    
	   
	   echo '<div id="block_objects_'.$key.'"><p align="center"><img src="/admin/images/loading.gif" alt=""><br/><span class="small">Загрузка...</span></p></div>';
	    
       echo '<form action="?id='.$page_id.'&block='.$key.'" method="post">
	         <table cellspacing="0" cellpadding="0">
			  <tr>
			    <td class="green" align="right"><strong>Добавить:</strong> &nbsp; </td>
				<td><select style="width:280px;" name="content" onchange="xajax_show_templates(this.form.content.options[this.form.content.selectedIndex].value, '.$key.');">
             <option value="">Выберите текст/модуль...</option>';
              $res = mysql_query("select obj_id,content_name,type from content order by type,content_name asc");
              if (@mysql_num_rows($res) > 0)
               {
                 while ($r = mysql_fetch_array($res))
                  {
                    echo '<option ';  if ($r['type'] == 1) echo 'style="color:#090" ';
                    echo 'value="'.$r['obj_id'].'">'.htmlspecialchars($r['content_name']).'</option>'."\n";
                  }
               }
       echo '</select></td><td><div id="block_button_'.$key.'">&nbsp;</div></td></tr>
	         <tr><td align="right"><span id="templates_text_'.$key.'">&nbsp;<span></td><td><div id="templates_'.$key.'">&nbsp;</div></td><td>&nbsp;</td></tr></table></form></fieldset>';
       $i++;      
	  }
	 echo '</fieldset>';

     echo '<script language="javascript">';
     foreach ($keys as $key) echo 'xajax_show_block_objects('.$key.');';
     echo '</script>';
	 
   }
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>