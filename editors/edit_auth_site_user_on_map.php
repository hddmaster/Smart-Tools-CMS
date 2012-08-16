<?
session_start();
include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
$user = new Auth;
if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

if (isset($_GET['delete_coodrinates']) && isset($_GET['id']))
 {
 if ($user->check_user_rules('delete'))
  {
    
  $user_id = (int)$_GET['id'];

  mysql_query("update auth_site set google_maps_latitude = '', google_maps_longitude = '' where user_id = $user_id") or die(mysql_error());
  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();

   $_SESSION['smart_tools_refresh'] = 'enable';
   Header("Location: ".$_SERVER['PHP_SELF']."?id=$user_id"); exit();
  } else $user->no_rules('delete');
 }

//------------------------------------------------------------------------------
// AJAX
function save_position($user_id, $value)
{
  $objResponse = new xajaxResponse();
  
  $value = preg_replace('/[\(|\)]/','',$value);
  list($google_maps_latitude, $google_maps_longitude) = explode (',',$value);
  $google_maps_latitude = trim($google_maps_latitude);
  $google_maps_longitude = trim($google_maps_longitude);

  mysql_query("update auth_site set google_maps_latitude = $google_maps_latitude, google_maps_longitude = $google_maps_longitude where user_id = $user_id");

  //Обновление кэша связанных модулей на сайте
  $cache = new Cache; $cache->clear_cache_by_module();
  
  $text = '<table cellspacing="0" cellpadding="2">
            <tr>
              <td><span class="grey">Текущие координаты: '.$google_maps_latitude.', '.$google_maps_longitude.'</span></td>
              <td><a href="javascript:if(confirm(\'Вы действительно хотите удалить?\')){location.href=\'edit_shop_cat_group_on_map.php?delete_coodrinates=true&id='.$user_id.'\';}"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td>
            </tr>
           </table>';

  $objResponse->assign('coordinates','innerHTML', $text);
  $objResponse->alert("Координаты сохранены: ".$google_maps_latitude.", ".$google_maps_longitude);
  return $objResponse;
}

$xajax->registerFunction("save_position");
// -----------------------------------------------------------------------------

require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

if(isset($_GET['id']))
 {
 if ($user->check_user_rules('view'))
  {

 $user_id = (int)$_GET['id'];
 $result = mysql_query("select * from auth_site where user_id=$user_id");

   if (!$result) exit();
   $row = mysql_fetch_array($result);
   $username = $row['username'];
   $google_maps_latitude = $row['google_maps_latitude'];
   $google_maps_longitude = $row['google_maps_longitude'];

 if ($row['user_image']) echo '<p><img align="absmiddle" src="/admin/images/img_resize.php?size=100&image='.rawurlencode($row['user_image']).'" border="0"></p>';

$tabs = new Tabs;
if ($user->check_user_rules('view','/admin/editors/edit_auth_site_user.php')) $tabs->add_tab('/admin/editors/edit_auth_site_user.php?id='.$user_id, 'Свойства');
if ($user->check_user_rules('view','/admin/editors/edit_auth_site_user_on_map.php')) $tabs->add_tab('/admin/editors/edit_auth_site_user_on_map.php?id='.$user_id, 'Расположение на карте');
$tabs->show_tabs();

if (isset($_GET['message']))
 {
   $message = new Message;
   $message->get_message($_GET['message']);
 }
 
$key = ''; if ($user->get_cms_option('google_maps_key')) $key = $user->get_cms_option('google_maps_key');
?>
<script type="text/javascript">
function addLoadEvent(func)
{	
	var oldonload = window.onload;
	window.onload = function(){
		if (typeof oldonload == 'function') oldonload();
		func();
	}
}

</script>

       <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;gl=RU&amp;hl=ru&amp;key=<?=$key?>"
      type="text/javascript"></script>
    <script type="text/javascript">
    //<![CDATA[
		addLoadEvent(load);
		window.onunload = GUnload;


    function load() {
      if (GBrowserIsCompatible()) {
        var map = new GMap2(document.getElementById("map"));
        map.addControl(new GLargeMapControl());
        map.addControl(new GMapTypeControl());
        map.setCenter(new GLatLng(55.753781489660035, 37.622337341308594), 4);

        GEvent.addListener(map,"click", function(overlay,point) {xajax_save_position(<?=$user_id?>, point.toString());} );

        // Create our "tiny" marker iconvar
        tinyIcon = new GIcon();
        tinyIcon.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";
        tinyIcon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
        tinyIcon.iconSize = new GSize(12, 20);
        tinyIcon.shadowSize = new GSize(22, 20);
        tinyIcon.iconAnchor = new GPoint(6, 20);
        tinyIcon.infoWindowAnchor = new GPoint(5, 1);
        
        // Set up our GMarkerOptions object literal
        markerOptions = { icon:tinyIcon };
        // Add 10 markers to the map at random locations
        var bounds = map.getBounds();
        var southWest = bounds.getSouthWest();
        var northEast = bounds.getNorthEast();
        var lngSpan = northEast.lng() - southWest.lng();
        var latSpan = northEast.lat() - southWest.lat();
<?
$result = mysql_query("select * from auth_site 
                       where status = 1 and 
                       type = 1 and 
                       google_maps_latitude != '' and
                       google_maps_longitude != ''");
if (mysql_num_rows($result) > 0)
 {
   while ($row = mysql_fetch_array($result))
    {
      $username = str_replace("'",'`',htmlspecialchars($row['username']));
      echo 'var point = new GLatLng('.$row['google_maps_latitude'].','.$row['google_maps_longitude'].');'."\n";
      echo 'map.addOverlay(new GMarker(point, markerOptions));'."\n\n";
    } 
 }
?>     
      }
    }

    //]]>
    </script>



<?
  echo '<div id="coordinates"><table cellspacing="0" cellpadding="2"><tr><td><span class="grey">';
  if ($google_maps_latitude && $google_maps_longitude) echo 'Текущие координаты: '.$google_maps_latitude.', '.$google_maps_longitude;
  echo '</span></td>';
  if ($google_maps_latitude && $google_maps_longitude)
   {
     echo '<td><a href="';
     echo "javascript:if(confirm('Вы действительно хотите удалить?')){location.href='?delete_coodrinates=true&id=$user_id';}";
     echo '"><img src="/admin/images/icons/cross.png" alt="Удалить" border="0"></a></td>';
   }
  echo '</tr></table></div>';
  echo '<div id="map" style="width: 100%; height: 400px; border: 1px solid #cccccc;"></div>';
  
  } else $user->no_rules('view');
 }
else echo '<span class="red">Ошибка запуска функции!</span>';
require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>