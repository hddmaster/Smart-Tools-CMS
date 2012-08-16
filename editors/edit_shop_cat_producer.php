<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_POST['producer_name']) &&
    isset($_POST['producer_descr']) &&
    isset($_GET['id'])) {
    if ($user->check_user_rules('edit')) {
        if (trim($_POST['producer_name'])=='') {
            header("Location: ".$_SERVER['PHP_SELF']."?id={$_GET['id']}&message=formvalues");
            exit();
        }

        $producer_id = (int)$_GET['id'];
        $producer_name = trim($_POST['producer_name']);
        $producer_descr = trim($_POST['producer_descr']);
        $producer_title = trim($_POST['producer_title']);
        $producer_meta_keywords = trim($_POST['producer_meta_keywords']);
        $producer_meta_description = trim($_POST['producer_meta_description']);
        $producer_url = trim($_POST['producer_url']);
        $c_store_name = trim($_POST['c_store_name']);

        $result = mysql_query("select * from shop_cat_producers where producer_name = '".stripslashes($producer_name)."' and producer_id!=$producer_id");
        if (mysql_num_rows($result) > 0) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id&message=duplicate"); exit();}

        $result = mysql_query("select * from shop_cat_producers where producer_id=$producer_id");
        $row = mysql_fetch_array($result);
        $producer_picture = $row['producer_picture'];

        if (isset($_FILES['producer_picture']['name']) &&
        is_uploaded_file($_FILES['producer_picture']['tmp_name']))
        {
            $user_file_name1 = mb_strtolower($_FILES['producer_picture']['name'],'UTF-8');
            $type1 = basename($_FILES['producer_picture']['type']);
        
        switch ($type1)
        {
            case 'jpeg': break;
            case 'pjpeg': break;
            case 'png': break;
            case 'x-png': break;
            case 'gif': break;
            case 'bmp': break;
            case 'wbmp': break;
            default: Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id&message=incorrectfiletype"); exit(); break;
        }
        
        //удаляем старый,если не используется
        if ($producer_picture !== '')
        {
            if (!use_file($producer_picture,'shop_cat_producers','producer_picture'))
            @unlink($_SERVER['DOCUMENT_ROOT'].$producer_picture);
        }
        
        //Проверка на наличие файла, замена имени, пока такого файла не будет
        $file = pathinfo($user_file_name1);
        $ext = $file['extension'];
        $name_clear = str_replace(".$ext",'',$user_file_name1);
        $name = $name_clear;
        $i = 1;
        while (file_exists($_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_producers/$name.$ext"))
        {
            $name = $name_clear." ($i)";
            $i ++;
        }
        $user_file_name1 =  $name.'.jpg';
        
        }

        if (isset($_FILES['producer_picture']['name']) &&
            is_uploaded_file($_FILES['producer_picture']['tmp_name'])) {
            $result = mysql_query("update shop_cat_producers set producer_picture='/userfiles/shop_cat_producers/$user_file_name1' where producer_id=$producer_id");
            if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id&message=db"); exit();}
            $cache = new Cache;
            $cache->clear_all_image_cache();
        }
   
        $result = mysql_query(" update shop_cat_producers set
                                producer_name='$producer_name',
                                producer_descr='$producer_descr',
                                producer_title = '$producer_title',
                                producer_meta_keywords = '$producer_meta_keywords',
                                producer_meta_description = '$producer_meta_description',
                                producer_url='$producer_url',
                                c_store_name='$c_store_name'
                                where producer_id=$producer_id");
        if (!$result) {Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id&message=db"); exit();}

        if (isset($_FILES['producer_picture']['name']) &&
            is_uploaded_file($_FILES['producer_picture']['tmp_name'])) {
            $filename = $_SERVER['DOCUMENT_ROOT']."/userfiles/shop_cat_producers/$user_file_name1";
            copy($_FILES['producer_picture']['tmp_name'], $filename);
            resize($filename, basename($_FILES['producer_picture']['type']));
            chmod($filename,0666);
        }

        //Обновление кэша связанных модулей на сайте
        $cache = new Cache; $cache->clear_cache_by_module();

        $_SESSION['smart_tools_refresh'] = 'enable';
        Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id");
        exit();
    } else $user->no_rules('edit');
}

if (isset($_GET['delete_img']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
  $producer_id = (int)$_GET['id'];
  $delete_img = $_GET['delete_img'];

  if ($delete_img == '1')
   {
     $result = mysql_query("select producer_picture from shop_cat_producers where producer_id=$producer_id");
     $row = mysql_fetch_array($result);
     if (!use_file($row['producer_picture'],'shop_cat_producers','producer_picture')) @unlink($_SERVER['DOCUMENT_ROOT'].$row['producer_picture']);
     $result = mysql_query("update shop_cat_producers set producer_picture = '' where producer_id=$producer_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
   }

  $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$producer_id"); exit();
  } else $user->no_rules('delete');
 }

//------------------------------------------------------------------------------
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id'])) {
    if ($user->check_user_rules('view')) {
        $producer_id = (int)$_GET['id'];
        $result = mysql_query("select * from shop_cat_producers where producer_id=$producer_id");
        $row = mysql_fetch_object($result);

        if ($row->producer_picture) echo '<p><img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row->producer_picture).'" border="0"></p>';

        if (isset($_GET['message'])) {
            $message = new Message;
            $message->get_message($_GET['message']);
        }

        echo '<form enctype="multipart/form-data" action="?id='.$producer_id.'" method="post">
        <table cellpadding="4" cellspacing="1" border="0" class="form">
            <tr>
                <td>Название <sup class="red">*</sup></td>
                <td><input style="width:280px" type="text" name="producer_name" value="'.htmlspecialchars($row->producer_name).'" maxlength="255"></td>
            </tr>
            <tr>
                <td>Описание</td>
                <td><input style="width:280px" type="text" name="producer_descr" value="'.htmlspecialchars($row->producer_descr).'" maxlength="255"></td>
            </tr>
            <tr>
                <td>Заголовок страницы сайта<br /><span class="grey">TITLE</span></td>
                <td><input style="width:280px" type="text" name="producer_title" value="'.htmlspecialchars($row->producer_title).'" maxlength="255"></td>
            </tr>
            <tr>
                <td>Ключевые слова<br /><span class="grey">meta keyrords</span></td>
                <td><input style="width:280px" type="text" name="producer_meta_keywords" value="'.htmlspecialchars($row->producer_meta_keywords).'" maxlength="255"></td>
            </tr>
            <tr>
                <td>Описание<br /><span class="grey">meta description</span></td>
                <td><input style="width:280px" type="text" name="producer_meta_description" value="'.htmlspecialchars($row->producer_meta_description).'" maxlength="255"></td>
            </tr>
            <tr>
                <td>URL</td>
                <td><input style="width:280px" type="text" name="producer_url" value="'.htmlspecialchars($row->producer_url).'" maxlength="255"/></td>
            </tr>
            <tr>
                <td>Артикул 1С<br/><span class="grey">Уникальный идентификатор</span></td>
                <td><input style="width:280px" type="text" name="c_store_name" value="'.htmlspecialchars($row->c_store_name).'" maxlength="255"></td>
            </tr>
            <tr>
                <td>Фотография</td>
                <td>
                <table cellspacing="0" cellpadding="0">
                    <tr>
                        <td><input style="width:280px" type="file" name="producer_picture"/></td>
                        <td>';
                        if ($row->producer_picture) {
                            echo '<a href="';
                            echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_img=1&id=$producer_id';}";
                            echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a><br/>';
                        }
                    echo '</td></tr></table></td></tr>
            </tr>
        </table><br>
        <button type="SUBMIT">Сохранить</button>
        </form>';
    } else $user->no_rules('view');
} else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>