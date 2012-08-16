<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (    isset($_POST['head']) &&
        isset($_GET['id'])) {
    if ($user->check_user_rules('add')) {
        $adv_id = (int)$_GET['id'];

        if (trim($_POST['date1'])=='' ||
            trim($_POST['date2'])=='' ||
            !isset($_POST['type'])) {Header("Location: ".$_SERVER['PHP_SELF']."?id=".$adv_id."&message=formvalues"); exit();}
        $head = $_POST['head'];
        $code = ''; if (isset($_POST['code'])) $code = $_POST['code'];
        $url = ''; if (isset($_POST['url'])) $url = $_POST['url'];
        $adv_type = $_POST['type'];
        $order_id = ((int)$_POST['order_id'] > 0 ? (int)$_POST['order_id'] : 0);
        $text = trim($_POST['text']);
      
        //if ($adv_type == 0 && !is_uploaded_file($_FILES['picture']['tmp_name'])) {Header("Location: ".$_SERVER['PHP_SELF']."?id=".$adv_id."&message=formvalues"); exit();}
        //if ($adv_type == 1 && !is_uploaded_file($_FILES['flash']['tmp_name'])) {Header("Location: ".$_SERVER['PHP_SELF']."?id=".$adv_id."&message=formvalues"); exit();}
            //if ($adv_type == 2 && trim($_POST['code']) == '') {Header("Location: ".$_SERVER['PHP_SELF']."?id=".$adv_id."&message=formvalues"); exit();}

        $hour1 = intval($_POST['hour1']); if ($hour1 > 23) $hour1 = 00; if ($hour1 < 10) $hour1 = '0'.$hour1;
        $minute1 = intval($_POST['minute1']); if ($minute1 > 59) $minute1 = 00; if ($minute1 < 10) $minute1 = '0'.$minute1;
        $second1 = intval($_POST['second1']); if ($second1 > 59) $second1 = 00; if ($second1 < 10) $second1 = '0'.$second1;
        $date1 = substr($_POST['date1'],6,4).substr($_POST['date1'],3,2).substr($_POST['date1'],0,2).$hour1.$minute1.$second1;
        
        $hour2 = intval($_POST['hour2']); if ($hour2 > 23) $hour2 = 00; if ($hour2 < 10) $hour2 = '0'.$hour2;
        $minute2 = intval($_POST['minute2']); if ($minute2 > 59) $minute2 = 00; if ($minute2 < 10) $minute2 = '0'.$minute2;
        $second2 = intval($_POST['second2']); if ($second2 > 59) $second2 = 00; if ($second2 < 10) $second2 = '0'.$second2;
        $date2 = substr($_POST['date2'],6,4).substr($_POST['date2'],3,2).substr($_POST['date2'],0,2).$hour2.$minute2.$second2;

        if(isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name'])) {
            $picture_file_name = mb_strtolower($_FILES['picture']['name'],'UTF-8');
            $type = basename($_FILES['picture']['type']);

            switch ($type) {
                case 'jpeg': break;
                case 'pjpeg': break;
                case 'png': break;
                case 'x-png': break;
                case 'gif': break;
                case 'bmp': break;
                case 'wbmp': break;
                default: Header("Location: ".$_SERVER['PHP_SELF']."?id=".$adv_id."&message=incorrectfiletype"); exit(); break;
            }

            //Проверка на наличие файла, замена имени, пока такого файла не будет
            $file = pathinfo($picture_file_name);
            $ext = $file['extension'];
            $name_clear = str_replace(".$ext", '', $picture_file_name);
            $name = $name_clear;
            $i = 1;
            while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$name.$ext")) {
                $name = $name_clear." ($i)";
                $i++;
            }
            $picture_file_name =  $name.'.'.$ext;
        }

        if(isset($_FILES['icon']['name']) && is_uploaded_file($_FILES['icon']['tmp_name'])) {
            $icon_file_name = mb_strtolower($_FILES['icon']['name'],'UTF-8');
            $type = basename($_FILES['icon']['type']);

            switch ($type) {
                case 'jpeg': break;
                case 'pjpeg': break;
                case 'png': break;
                case 'x-png': break;
                case 'gif': break;
                case 'bmp': break;
                case 'wbmp': break;
                default: Header("Location: ".$_SERVER['PHP_SELF']."?id=".$adv_id."&message=incorrectfiletype"); exit(); break;
            }

            //Проверка на наличие файла, замена имени, пока такого файла не будет
            $file = pathinfo($icon_file_name);
            $ext = $file['extension'];
            $name_clear = str_replace(".$ext", '', $icon_file_name);
            $name = $name_clear;
            $i = 1;
            while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$name.$ext")) {
                $name = $name_clear." ($i)";
                $i++;
            }
            $icon_file_name =  $name.'.'.$ext;
        }

        if(isset($_FILES['flash']['name']) && is_uploaded_file($_FILES['flash']['tmp_name'])) {
            $flash_file_name = mb_strtolower($_FILES['flash']['name'],'UTF-8');
            $type = basename($_FILES['flash']['type']);

            switch ($type) {
                case 'x-shockwave-flash': break;
                default: Header("Location: ".$_SERVER['PHP_SELF']."?id=".$adv_id."&message=incorrectfiletype"); exit(); break;
            }

            //Проверка на наличие файла, замена имени, пока такого файла не будет
            $file = pathinfo($flash_file_name);
            $ext = $file['extension'];
            $name_clear = str_replace(".$ext", '', $flash_file_name);
            $name = $name_clear;
            $i = 1;
            while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$name.$ext")) {
                $name = $name_clear." ($i)";
                $i ++;
            }
            $flash_file_name =  $name.'.'.$ext;
        }

        $query = "  update
                    advertising
                    set
                    adv_type = $adv_type,
                    date1 = '$date1',
                    date2 = '$date2',
                    head = '$head',
                    url = '$url',
                    code = '$code',
                    order_id = $order_id,
                    text = '$text'";

        if(isset($_FILES['picture']['name']) && is_uploaded_file($_FILES['picture']['tmp_name'])) $query .= ", img_path = '/userfiles/banners/$picture_file_name'";
        if(isset($_FILES['icon']['name']) && is_uploaded_file($_FILES['icon']['tmp_name'])) $query .= ", icon_path = '/userfiles/banners/$icon_file_name'";    
        if(isset($_FILES['flash']['name']) && is_uploaded_file($_FILES['flash']['tmp_name'])) $query .= ", flash_path = '/userfiles/banners/$flash_file_name'";    

        $query .= " where adv_id = $adv_id";

        $result = mysql_query($query) or die($query.mysql_error());
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=".$adv_id."&message=db"); exit();}

        if (isset($_FILES['picture']['name']) &&
            is_uploaded_file($_FILES['picture']['tmp_name'])) {
            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$picture_file_name";
            copy($_FILES['picture']['tmp_name'], $filename);
            chmod($filename, 0666);
        }
    
        if (isset($_FILES['icon']['name']) &&
            is_uploaded_file($_FILES['icon']['tmp_name'])) {
            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$icon_file_name";
            copy($_FILES['icon']['tmp_name'], $filename);
            chmod($filename, 0666);
        }

        if (isset($_FILES['flash']['name']) &&
            is_uploaded_file($_FILES['flash']['tmp_name'])) {
            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/banners/$flash_file_name";
            copy($_FILES['flash']['tmp_name'], $filename);
            chmod($filename, 0666);
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();
    
        Header("Location: ".$_SERVER['PHP_SELF']."?id=$adv_id");
        exit();
    } else $user->no_rules('add');
}

elseif (    isset($_POST['url']) &&
            trim($_POST['url']) !== '' &&
            isset($_GET['adv_id'])) {
    if ($user->check_user_rules('add')) {
        $url = trim($_POST['url']);
        $adv_id = $_GET['adv_id'];

        $query = "insert into adv_pages (adv_id, url, coincidence) values ($adv_id, '$url', 1)";
        $result = mysql_query($query);
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$adv_id&message=db"); exit();}

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        Header("Location: ".$_SERVER['PHP_SELF']."?id=$adv_id");
        exit();
    } else $user->no_rules('add');
}

if (    isset($_POST['urls']) &&
        isset($_GET['adv_id'])) {
    if ($user->check_user_rules('edit')) {
        $id = $_GET['adv_id']; 
    
        foreach($_POST['urls'] as $page_id => $url)
            mysql_query("update adv_pages set url = '".trim($url)."', coincidence = 0 where page_id = $page_id");
    
        foreach($_POST['coincidences'] as $page_id => $v)
            mysql_query("update adv_pages set coincidence = 1 where page_id = $page_id");
    
        header("Location: ".$_SERVER['PHP_SELF']."?id=$id");
        exit();
    } else $user->no_rules('add');
}

if (    isset($_GET['action']) &&
        isset($_GET['id']) &&
        isset($_GET['page_id'])) {
    $action = $_GET['action'];
    $adv_id = $_GET['id'];
    $page_id = (int)$_GET['page_id'];
      
    if ($action == 'del') {
        if ($user->check_user_rules('delete')) {
            $result = mysql_query("delete from adv_pages where page_id = $page_id and adv_id = $adv_id");
            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
        } else $user->no_rules('delete');
    }

    Header("Location: ".$_SERVER['PHP_SELF']."?id=$adv_id");
    exit();
}

if (isset($_GET['delete_icon']) && isset($_GET['id'])) {
    if ($user->check_user_rules('delete')) {
        $adv_id = (int)$_GET['id'];
        $delete_icon = $_GET['delete_icon'];
    
        if ($delete_icon == 'true') {
            $result = mysql_query("select icon_path from advertising where adv_id = $adv_id");
            $row = mysql_fetch_array($result);
            if (!use_file($row['icon_path'], 'advertising', 'icon_path')) unlink($_SERVER['DOCUMENT_ROOT'].$row['icon_path']);
            $result = mysql_query("update advertising set icon_path = '' where adv_id = $adv_id");
            //Обновление кэша связанных модулей на сайте
            $cache = new Cache; $cache->clear_cache_by_module();
        }
    
        $_SESSION['smart_tools_refresh'] = 'enable';
        Header("Location: ".$_SERVER['PHP_SELF']."?id=$adv_id");
        exit();
    } else $user->no_rules('delete');
}
 
//------------------------------------------------------------------------------
// AJAX
function show_b_input($adv_id, $type) {
    $objResponse = new xajaxResponse();
    $text = '';
  
    $res = mysql_query("select * from advertising where adv_id = ".$adv_id);
    $r = mysql_fetch_object($res);
  
  
    if ($type == 0) $text = '<input style="width:280px" type="text" name="url" maxlength="255" value="'.htmlspecialchars($r->url).'"><br />
                             <input style="width:280px" type="file" name="picture">';
    if ($type == 1) $text = '<img src="/admin/images/px.gif" alt="" width="1" height="10"><br /><input style="width:280px" type="file" name="flash">';
    if ($type == 2) $text = '<textarea style="width:280px; height: 38px;" name="code">'.htmlspecialchars($r->code).'</textarea>';
 
    $objResponse->assign('b_input',"innerHTML",$text);
    return $objResponse;
}

$xajax->registerFunction("show_b_input");
//------------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id'])) {
    if ($user->check_user_rules('view')) {

        $adv_id = (int)$_GET['id'];
        $result = mysql_query("select
                *,
                date_format(date1, '%d.%m.%Y (%H:%i:%s)') as date1,
                date_format(date2, '%d.%m.%Y (%H:%i:%s)') as date2
                from advertising
                where adv_id = $adv_id");
        if (!$result) exit();
        $row = mysql_fetch_object($result);

        $hour1 = substr($row->date1,12,2);
        $minute1 = substr($row->date1,15,2);
        $second1 = substr($row->date1,18,2);
        $date1 = substr($row->date1,0,10);
        
        $hour2 = substr($row->date2,12,2);
        $minute2 = substr($row->date2,15,2);
        $second2 = substr($row->date2,18,2);
        $date2 = substr($row->date2,0,10);

        if (isset($_GET['message'])) {
            $message = new Message;
            $message->get_message($_GET['message']);
        }

 echo '<form enctype="multipart/form-data" action="?id='.$adv_id.'" method="post">
   <table cellpadding="4" cellspacing="1" border="0" class="form">
    <tr>
      <td>Название</td>
      <td><input style="width:280px" type="text" name="head" maxlength="255" value="'.htmlspecialchars($row->head).'"></td>
    </tr>
    <tr>
      <td>Даты показа <sup class="red">*</sup></td>
      <td>
    
    <table cellspacing="0" cellpadding="0">
     <tr>    
       <td style="padding-right: 4px;">с</td>
       <td><input style="width: 65px" type="text" name="date1" class="datepicker" value="'.$date1.'"></td>
       <td style="padding-left: 8px;padding-right: 4px;">по</td>
       <td><input style="width: 65px" type="text" name="date2" class="datepicker" value="'.$date2.'"></td>
     </tr>
    </table>

      </td>
    </tr>
    <tr>
      <td>Время показа <sup class="red">*</sup><br><span class="grey">Формат: [чч-мм-сс]</span></td>
      <td>
        <table cellspacing="0" cellpadding="0">
         <tr>
           <td>с&nbsp;</td>
           <td>
             <input type="text" name="hour1" value="'.$hour1.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="minute1"  value="'.$minute1.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="second1" value="'.$second1.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
           </td>
           <td>&nbsp;&nbsp;по&nbsp;</td>
           <td>
             <input type="text" name="hour2" value="'.$hour2.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="minute2"  value="'.$minute2.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
             <input type="text" name="second2" value="'.$second2.'" style="width:20px;" maxlength="2" onKeyPress ="if (event.keyCode > 31 && (event.keyCode < 48 || event.keyCode > 57))  event.returnValue = false;">
           </td>
         </tr>
        </table> 
      </td>
    </tr>
    <tr>
      <td>Иконка</td>
      <td><table cellspacing="0" cellpadding="0">
       <tr><td><input style="width:280px" type="file" name="icon"/></td><td>';
       if ($row->icon_path)
        {
          echo '<a href="';
          echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_icon=true&id=$adv_id';}";
          echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
        }
       echo '</td></tr></table>
    </tr>
    <tr>
      <td>Тип <sup class="red">*</sup></td>
      <td>
        <table cellspacing="0" cellpadding="0">
         <tr>
           <td><input type="radio" name="type" value="0" onclick="xajax_show_b_input('.$adv_id.', 0);"'.($row->type == 0 ? ' checked' : '').'></td>
           <td><span class="grey">картинка</span></td>
         </tr>
         <tr>
           <td><input type="radio" name="type" value="1" onclick="xajax_show_b_input('.$adv_id.', 1);"'.($row->type == 1 ? ' checked' : '').'></td>
           <td><span class="grey">flash</span></td>
         </tr>
         <tr>
           <td><input type="radio" name="type" value="2" onclick="xajax_show_b_input('.$adv_id.', 2);"'.($row->type == 2 ? ' checked' : '').'></td>
           <td><span class="grey">код</span></td>
         </tr>
        </table>
      </td></tr>
    <tr>
      <td>Баннер <sup class="red">*</sup></td>
      <td><div id="b_input" style="height: 40px;"><img src="/admin/images/px.gif" alt="" width="1" height="13"><br /><span class="small">Выберите тип баннера...</span></div></td>
    </tr>
    <tr>
      <td>Порядок сортировки</td>
      <td><input style="width:280px" type="text" name="order_id" maxlength="255" value="'.htmlspecialchars($row->order_id).'"></td>
    </tr>
   </table><br>

	<div class="small">Текст баннера</div><p>';
	    $oFCKeditor = new FCKeditor('text') ;
	    $oFCKeditor->BasePath = '/admin/fckeditor/';
	    $oFCKeditor->ToolbarSet = 'Main';
	    $oFCKeditor->Value = $row->text;
	    $oFCKeditor->Width  = '100%' ;
	    $oFCKeditor->Height = '200' ;
	    $oFCKeditor->Create() ;
    echo '</p>

   <button type="SUBMIT">Сохранить</button>
  </form><br />
  
  <script>setTimeout("xajax_show_b_input('.$adv_id.', '.$row->type.')", 1000);</script>';

 echo '<div class="dhtmlgoodies_question">
        <table cellspacing="0" cellpadding="4">
		 <tr>
		   <td><img src="/admin/images/icons/plus.png" alt=""></td>
		   <td><h3 class="nomargins">Добавить ссылку</h3></td>
		 </tr>
		</table>   
	   </div>
       <div class="dhtmlgoodies_answer"><div>';

 echo '<form enctype="multipart/form-data" action="?adv_id='.$adv_id.'" method="post">
   <table cellpadding="4" cellspacing="0" border="0" class="form_light">
    <tr>
      <td><input style="width:280px" type="text" name="url" maxlength="255" value="http://'.$_SERVER['HTTP_HOST'].'/"></td>
      <td><button type="SUBMIT">Добавить</button></td>
    </tr>
   </table>
  </form><div>&nbsp;</div></div></div>';
  
// постраничный вывод
 $page = ((isset($_GET['page'])) ? $_GET['page']-1 : 0);
 $per_page = ((isset($_GET['per_page'])) ? $_GET['per_page'] : 20);
 $start = abs($page*$per_page);

// сортировка
 $sort_by = ((isset($_GET['sort_by'])) ? $_GET['sort_by'] : 'page_id');
 $order = ((isset($_GET['order'])) ? $_GET['order'] : 'desc'); 

 $add = '';
 $params = array();
 
 $params['id'] = $adv_id;
 
 $add_params = ''; if(is_array($params)) foreach($params as $key => $value) $add_params .= '&'.$key.'='.rawurlencode($value);

 $query = "select
           *
           from adv_pages
           where adv_id = $adv_id $add";
 $result = mysql_query($query); $total_rows = mysql_num_rows($result);          
 $result = mysql_query($query." order by $sort_by $order LIMIT $start,$per_page");
 if (mysql_num_rows($result) > 0)
  {
 
 navigation($page, $per_page, $total_rows, $params);
 echo '<form action="?adv_id='.$adv_id.'" method="post">
       <p align="right"><button type="submit">Сохранить</button></p>
       <table cellpadding="4" cellspacing="0" border="0" width="100%">'."\n";
 echo '<tr align="center" class="header">
         <td nowrap width="50">№&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=page_id&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'page_id' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=page_id&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'page_id' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap width="100%">URL&nbsp;&nbsp;
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=url&order=asc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'url' && $order == 'asc') echo 'sort_asc_sel.gif'; else echo 'sort_asc.gif'; echo '" border="0" alt="Сортировка по возрастанию"></a>
           <a href="'.$_SERVER['PHP_SELF'].'?sort_by=url&order=desc&page='.($page+1).'&per_page='.$per_page.$add_params.'"><img src="/admin/images/'; if ($sort_by == 'url' && $order == 'desc') echo 'sort_desc_sel.gif'; else echo 'sort_desc.gif'; echo '" border="0" alt="Сортировка по убыванию"></a></td>
         <td nowrap>Точное совпадение</td>
         <td>&nbsp;</td>
       </tr>';

 while ($row = mysql_fetch_array($result))
  {
     echo '<tr valign="center" onmouseover="this.style.backgroundColor='; echo "'#EFEFEF'"; echo';" onmouseout="this.style.backgroundColor='; echo "'white'"; echo ';" class="underline">
           <td align="center">'.$row['page_id'].'</td>
           <td><input type="text" name="urls['.$row['page_id'].']" value="'.htmlspecialchars($row['url']).'" style="width: 100%"></td>
           <td align="center"><input type="checkbox" name="coincidences['.$row['page_id'].']" value=""'.(($row['coincidence']) ? ' checked' : '').'></td>
           <td nowrap align="center"><a href="';
           echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?action=del&page_id=".$row['page_id'].'&sort_by='.$sort_by.'&order='.$order.'&page='.($page+1).'&per_page='.$per_page.$add_params."';}";
           echo '"><img align="absmiddle" src="/admin/images/icons/cross.png" border="0" alt="Удалить"></a></td>
         </tr>';
   }
  echo '</table><p align="right"><button type="submit">Сохранить</button></p></form>';
  navigation($page, $per_page, $total_rows, $params);
 }

  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>