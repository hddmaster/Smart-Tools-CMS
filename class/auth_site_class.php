<?
class Auth_site {
    private $timeout = 600; // 10 мин.

    public $user_id;
    public $parent_id;
    public $order_id;
    public $type;
    public $username;
    public $password;
    public $user_type;
    public $user_nick;
    public $user_fio;
    public $register_date;
    public $user_birthday;
    public $user_phone;
    public $user_site;
    public $user_facebook;
    public $user_vkontakte;
    public $user_skype;
    public $user_twitter;
    public $user_livejournal;
    public $user_myspace;
    public $user_flickr;
    public $user_youtube;
    public $user_lastfm;
    public $user_delicious;
    public $user_blogger;   
    public $user_icq;
    public $user_address;
    public $email;
    public $emails;
    public $user_image;
    public $user_extra;
    public $user_county;
    public $user_city;
    public $user_sex;
    public $text;
    public $text_full;
    public $main_in_group;
    public $is_ur;
    public $ur_company_name;
    public $ur_inn;
    public $ur_kpp;
    public $ur_address;
    public $ur_bank;
    public $ur_rs;
    public $ur_ks;
    public $ur_bik;
    public $google_maps_latitude;
    public $google_maps_longitude;
    public $lang_id;
    public $email_status;
    public $status;
    
    public $user_type_name;
    
    private $user_rules = array();
    private $actions = array(   'view',
                                'add',
                                'edit',
                                'action',
                                'delete');
    private $commands = array(  'view' => 'Просмотр',
                                'add' => 'Добавление',
                                'edit' => 'Редактирование',
                                'action' => 'Действие',
                                'delete' => 'Удаление');

    private $control_str = '';
    public $cookie = true;

    function __construct() {
        $this->control_str = md5(MySQL_HOST.MySQL_LOGIN.MySQL_PASSWORD.MySQL_DATABASE.SOLT);
        $this->clear_session();
    }

    public function create_secret_key() {
        return base64_encode(
                                serialize(
                                            array(
                                                $this->username,
                                                md5($this->username.DOMAIN.$_SERVER['HTTP_USER_AGENT'].$this->control_str.$this->password)
                                            )
                                        )
                            );
    }

    public function create_auth_pass_key()
    {
        return base64_encode(
                                serialize(
                                            array(
                                                $this->username,
                                                md5($this->username.DOMAIN.$this->control_str.$this->password)
                                            )
                                        )
                            );
    }

    private function check_valid_session() {
        return (($this->check_value($_SESSION['valid_site_user'])) ? true : false);
    }

    private function check_valid_cookie() {
        return (($this->check_value($_COOKIE['valid_site_user'])) ? true : false);
    }

    public function check_value($value) {
        list ($username, $str) = unserialize(base64_decode($value));
        $result = mysql_query("select * from auth_site where username = '$username' and status = 1");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_object($result);
            $this->username = $username;
            $this->password = $row->password;
            return (($value == $this->create_secret_key()) ? true : false);
        } else return false;
    }

    public function check_auth_pass_key($value) {
        list ($username, $str) = unserialize(base64_decode($value));
        $result = mysql_query("select * from auth_site where username='$username' and status = 1");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_object($result);
            $this->username = $username;
            $this->password = $row->password;
            return (($value == $this->create_auth_pass_key()) ? true : false);
        } else return false;
    }

    public function login($username, $password) {
        if (trim($username) && trim($password)) {
            $username = mysql_real_escape_string(stripslashes($username));
            $password = md5(mysql_real_escape_string(stripslashes($password)).SOLT);
            $result = mysql_query("select * from auth_site where username='$username' and password='$password' and status = 1");
            if (!$result) return false;
            if(mysql_num_rows($result) > 0) {
                $row = mysql_fetch_object($result);
                $this->user_id = $row->user_id;
                $this->username = $row->username;
                $this->password = $row->password;
                $this->history('login');
                $this->begin();
                $this->check_valid_user();
                return true;
            } else return false;
        } else return false;
    }

    public function login_by_userid($user_id) {
        $result = mysql_query("select * from auth_site where user_id = ".(int)$user_id);
        if (!$result) return false;
        if(mysql_num_rows($result) > 0) {
            $row = mysql_fetch_object($result);
            $this->user_id = $row->user_id;
            $this->username = $row->username;
            $this->password = $row->password;
            $this->history('login');
            $this->begin();
            $this->check_valid_user();
            return true;
        } else return false;
    }

    public function begin() {
        //создаем секретный ключ
        $key = $this->create_secret_key();
        //механизм сессий
        $_SESSION['valid_site_user'] = $key;
        //механизм COOKIES
        if ($this->cookie) {
            setcookie(  'valid_site_user',
                        $key,
                        time()+3600*24*365, //1 год
                        '/',
                        '.'.DOMAIN);
        }
    }

    private function check_session() {
        $this->clear_session();
        //создаем секретный ключ
        $key = $this->create_secret_key();
        $result = mysql_query("select * from auth_site_online where user_id = $this->user_id and hash = '$key'");
        if (mysql_num_rows($result) == 0) $this->save_session();
    }

    private function save_session() {
        //создаем секретный ключ
        $key = $this->create_secret_key();
        $time = time();
        mysql_query("insert into auth_site_online values ($this->user_id, $time, '$key')");
    }

    public function count_session() {
        $result = mysql_query("select * from auth_site_online");
        if (mysql_num_rows($result) > 0) return mysql_num_rows($result);
        else return false;
    }

    private function clear_session() {
        $time = time();
        $expire_time = $time - $this->timeout;
        $result = mysql_query("delete from auth_site_online where time < $expire_time");
    }

    public function check_valid_user() {
        //механизм сессий
        if (isset($_SESSION['valid_site_user']) || isset($_COOKIE['valid_site_user'])) {
            $key = false;
            //если установлен cookie, а сессия не активна
            if(!isset($_SESSION['valid_site_user'])) {
                if ($this->check_valid_cookie()) {
                    $_SESSION['valid_site_user'] = $_COOKIE['valid_site_user'];
                    list ($username, $str) = unserialize(base64_decode($_SESSION['valid_site_user']));
                    $result = mysql_query("select * from auth_site where username = '$username' and status = 1");
                    if (mysql_num_rows($result) > 0) $key = true;
                    else return false;
                } else return false;
            }

            if ($this->check_valid_session()) {
                //устанавливаем права на страницу: берем значение из БД.
                list ($username, $str) = unserialize(base64_decode($_SESSION['valid_site_user']));
                $result = mysql_query("select * from auth_site left join auth_site_users on auth_site.user_type = auth_site_users.user_type where username='$username' and status = 1");
                if (!$result) return false;
                if(mysql_num_rows($result) > 0) {
                    $row = mysql_fetch_array($result);

                    $this->user_id = $row['user_id'];
                    $this->username = $username;
                    $this->parent_id = $row['parent_id'];
                    $this->user_type = $row['user_type'];
                    $this->user_type_name = $row['user_type_name'];
                    $this->main_in_group = $row['main_in_group'];
                    $this->lang_id = $row['lang_id'];
        
                    $this->user_nick = $row['user_nick'];
                    $this->user_fio = $row['user_fio'];
                    $this->user_birthday = $row['user_birthday'];
                    $this->user_phone = $row['user_phone'];
                    $this->user_address = $row['user_address'];
                    $this->email = $row['email'];
                    $this->user_image = $row['user_image'];
                    $this->user_extra = $row['user_extra'];
                    $this->text = $row['text'];
                    $this->text_full = $row['text_full'];
                    $this->user_country = $row['user_country'];
                    $this->user_city = $row['user_city'];
                    $this->user_sex = $row['user_sex'];
        
                    $this->user_site = $row['user_site'];
                    $this->user_facebook = $row['user_facebook'];
                    $this->user_vkontakte = $row['user_vkontakte'];
                    $this->user_skype = $row['user_skype'];
                    $this->user_twitter = $row['user_twitter'];
                    $this->user_livejournal = $row['user_livejournal'];
                    $this->user_myspace = $row['user_myspace'];
                    $this->user_flickr = $row['user_flickr'];
                    $this->user_youtube = $row['user_youtube'];
                    $this->user_lastfm = $row['user_lastfm'];
                    $this->user_delicious = $row['user_delicious'];
                    $this->user_blogger = $row['user_blogger'];
                    $this->user_icq = $row['user_icq'];
        
                    $this->is_ur = $row['is_ur'];	 	 
                    $this->ur_company_name = $row['ur_company_name']; 	 	 
                    $this->ur_inn = $row['ur_inn'];
                    $this->ur_kpp = $row['ur_kpp'];
                    $this->ur_address = $row['ur_address'];
                    $this->ur_bank = $row['ur_bank'];
                    $this->ur_rs = $row['ur_rs']; 
                    $this->ur_ks = $row['ur_ks'];
                    $this->ur_bik = $row['ur_bik'];
                    
                    $this->email_status = $row['email_status'];
                    $this->status = $row['status'];
                    
                    $this->get_user_rules();
        
                    $this->check_session();
                    if ($key) $this->history('login');
                    return true; // только здесь возвращается true!!!
                } else return false;
            } else return false;
        } else return false;
    }

    public function get_user_rules() {
        $result = mysql_query(" select
                                auth_site_rules.access,
                                auth_site_scripts.script_path
                                from auth_site_rules,auth_site_scripts
                                where
                                auth_site_scripts.script_id = auth_site_rules.script_id and
                                auth_site_rules.user_type = $this->user_type");
        if (mysql_num_rows($result) > 0) {
            while($row = mysql_fetch_array($result)) {
                $access = unserialize($row['access']);
                $this->user_rules[$row['script_path']] = $access;
            }
        }
    }

    public function check_user_rules($action, $script = false) {
        //добавляем в историю, если вызов для текущего скрипта
        $add_to_history = !$script ? true : false;
        //если параметр не указан, проверяем права для текущего файла
        if (!$script) $script = $_SERVER['PHP_SELF'];
       
        if (array_key_exists($script, $this->user_rules)) {
            //последовательно проверям все права
            for ($i = 0; $i < 5; $i++) {
                //если действие совпадает с текущим
                if (    $action == $this->actions[$i] &&
                        $this->user_rules[$script][$i] == 1) {
                    if ($add_to_history && $action !== 'view')
                        $this->history($action);
                    return true;
                }
            }
        }
        
        return false; // Нет совпадений
    }

    public function history($command = '', $other_data_array = array()) {
        $time = mktime();
        $data = array();

        if (isset($other_data_array) && count($other_data_array) > 0) $data['OTHER DATA'] = serialize($other_data_array);
        if (isset($_POST) && count($_POST) > 0) $data['POST'] = serialize($_POST);
        if (isset($_GET) && count($_GET) > 0) $data['GET'] = serialize($_GET);
        if ($command == 'login') $data['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
       
        $data = serialize($data);
       
        mysql_query("   insert into auth_site_history
                        (
                            `date`,
                            `user_id`,
                            `file`,
                            `command`,
                            `remote_addr`,
                            `data`
                        )
                        values
                        (
                            $time,
                            $this->user_id,
                            '".$_SERVER['PHP_SELF']."',
                            '$command',
                            '".$_SERVER['REMOTE_ADDR']."',
                            '".addslashes($data)."')");
    }

    public function no_rules($command, $place = 'alert') {
        if ($place == 'screen') {
            echo '  <div id="alert_shadow" class="auth_shadow">&nbsp;</div>
                    <div id="alert_message" class="auth">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr valign="top">
                            <td width="100%">
                                <h2 style="color:#FF0000">Нет прав доступа для выполнения операции.</h2>
                                Комманда: '.$command.' ('.$this->commands[$command].')<br/>
                                Файл: '.$_SERVER['PHP_SELF'].'<br/><br/>
                                <p>Обратитесь к администратору.</p>
                            </td>
                            <td><a href="#" onclick="close_alert();"><img src="/admin/images/icons/cross.png" alt="Закрыть окно" border="0"></a></td>
                        </tr>
                    </table></div>'; exit();
        } else {
            echo '<script language="javascript">alert("Нет прав доступа для выполнения операции!\n\nКомманда: '.
            $command.' ('.$this->commands[$command].')\nФайл: '.$_SERVER['PHP_SELF'].
            '\n\nОбратитесь к администратору.");</script>';
        }
    }

    public function get_cms_option($option_sname) {
        $result = mysql_query("select * from cms_options where option_sname = '$option_sname'");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $option_type = $row['option_type'];
            switch ($option_type) {
                case 1: return $row['setting_int_value']; break;
                case 2: return $row['setting_float_value']; break;
                case 3: return $row['setting_boolean_value']; break;
                case 4: return $row['setting_char_value']; break;
                case 5: return $row['setting_text_value']; break;
                case 6: return $row['setting_text_value']; break;
                default: return false; break;
            }
        } else return false;
    }

    public function get_cms_option_name($option_sname) {
        $result = mysql_query("select * from cms_options where option_sname = '$option_sname'");
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            return $row['option_name'];
        } else return false;
    }
 }
?>