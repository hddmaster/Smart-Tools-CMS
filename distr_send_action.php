<?
    session_start();
    include ($_SERVER['DOCUMENT_ROOT']."/admin/functions/config.php");
    include ($_SERVER['DOCUMENT_ROOT']."/admin/class/auth_class.php");
    $user = new Auth;
    if(!$user->check_valid_user()) {Header("Location: /admin/?message=notauthorized&referrer=".urlencode($_SERVER['REQUEST_URI']));exit();}

    require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_header.php");

    if (isset($_GET['id']) && $_GET['id'] !== '' && isset($_GET['msg_id']) && $_GET['msg_id'] !== '') {
        if ($user->check_user_rules('action')) {

            $distr_id = (int)$_GET['id'];
            $msg_id = (int)$_GET['msg_id'];
            echo '<h2>Отправка сообщений...</h2>';

            //получение письма
            $row = mysql_fetch_array(mysql_query("select * from distr_msg where msg_id=$msg_id"));
            $head = $row['head'];
            $text = $row['text'];
            $distribution_files_path = $user->get_cms_option('distribution_files_path');
            $filename = $distribution_files_path.$msg_id.'/'.$row['file_path'];

            if ($row['file_path']) $text .= '<br><br>В сообщение вложен файл. В целях уменьшения размера сообщения, вы можете просмотреть файл перейдя по cсылке: <a href="http://'.$_SERVER['HTTP_HOST'].$filename.'">'.basename($filename).'</a>';
            if (isset($_POST['signature'])) $text .= $user->get_cms_option('distribution_signature_name');

            $from_email = $user->get_cms_option('distribution_email');
            $from_name = $user->get_cms_option('distribution_signature_name');
            $delay = $user->get_cms_option('distribution_delay');
            $transport = Swift_MailTransport::newInstance();
            //$transport = Swift_SmtpTransport::newInstance('localhost', 25);
            $mailer = Swift_Mailer::newInstance($transport);

            if ($from_email && $from_name && $delay) {

                //получение списка подписчиков, отправка сообщений в одном цикле
                $result = mysql_query("select auth_site.* from distr_list, auth_site where distr_list.distr_id = $distr_id and distr_list.user_id = auth_site.user_id order by auth_site.user_id asc");
                if (mysql_num_rows($result) > 0) {
                    $errors = 0;
                    while ($row = mysql_fetch_array($result)) {
                        $emails = array();

                        if (valid_email($row['username']) && !in_array($row['username'], $emails))
                            $emails[] = $row['username'];

                        if (valid_email($row['email']) && !in_array($row['email'], $emails))
                            $emails[] = $row['email'];

                        if (count(unserialize($row['emails']))) {
                            $add_emails = unserialize($row['emails']);
                            foreach($add_emails as $email)
                                if (valid_email($email) && !in_array($email, $emails))
                                    $emails[] = $email;
                        }
                        
                        //тут нужно устранять спецсимволы в e-mail, если есть
     
                        $username = htmlspecialchars($row['username']);
                        $user_fio = (($row['user_fio']) ? htmlspecialchars($row['user_fio']) : $username);
                        $user_nick = (($row['user_nick']) ? htmlspecialchars($row['user_nick']) : $username);
                        $user_address = htmlspecialchars($row['user_address']);
        
                        //формирование ключа
                        $u = new Auth_site;
                        $u->username = $username;
                        $res = mysql_query("select password from auth_site where username = '$username'");
                        if (mysql_num_rows($res) > 0) {
                            $r = mysql_fetch_object($res);
                            $u->password = $r->password;          
                        }  
                        $key = rawurlencode($u->create_auth_pass_key());
                
                        $html = str_replace(array(  '{username}',
                                                    '{user_fio}',
                                                    '{user_nick}',
                                                    '{user_address}',
                                                    '{email}',
                                                    '{key}'),
                                            array(  $username,
                                                    $user_fio,
                                                    $user_nick,
                                                    $user_address,
                                                    $email,
                                                    $key),
                                            $text);
                        
                        //тут нужно делать embed images в теле письма для полной совместимости с почтовиками
                        
                        if (count($emails) > 0) {
                            foreach($emails as $email) {
                                $res = true;
                                try {
                                    $message = Swift_Message::newInstance()
                                                ->setSubject($head)
                                                ->setFrom(array($from_email => $from_name))
                                                ->setTo($email)
                                                ->setBody($html, 'text/html');
                                    $res = $mailer->send($message);
                                } catch (Exception $e) {
                                    //echo 'Caught exception: ',  $e->getMessage(), "\n";
                                    $res = false;
                                }

                                echo '<div>'.$email.' - ';
                                echo $res ? '<span class="green">ОК</span>' : '<span class="red">Ошибка!</span>';
                                echo '</div>';
                                if (!$res) $errors++;
    
                                echo str_pad('', 4096);
                                usleep($delay);
                                flush();
                            }

                        }
                    }

                    echo '<p><span class="green">Все сообщения отправлены</span></p>';
                    if ($errors) echo '<p><span class="red">Ошибок: '.$errors.'</span></p>';
                }
                else echo '<p><span class="red">Не выбрано пользователей для рассылки</span></p>';
            }
            else echo '<p><span class="red">Не установлены обратный адрес и имя отправителя</span></p>';

        } else $user->no_rules('action');
    }

    else echo '<span class="red">Ошибка запуска функции!</span>';
    require_once($_SERVER['DOCUMENT_ROOT']."/admin/tpl/edit_footer.php");
?>