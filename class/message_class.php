<?
class Message {
    public $base_url = '';
    public $messages = array(
                'error' => array('exclamation', 'Ошибка запроса'),
				'formvalues' => array('exclamation', 'Ошибка при заполнении формы'),
				'duplicate' => array('exclamation', 'Конфликт уникальных записей'),
				'fileexist' => array('exclamation', 'Файл уже существует на сервере'),
				'filenotexists' => array('exclamation', 'Файла не существует на сервере'),
				'imagesize' => array('exclamation', 'Размер изображения превышает допустимые'),
				'incorrectfile' => array('exclamation', 'Неверный файл'),
				'incorrectfiletype' => array('exclamation', 'Неверный тип файла'),
				'use' => array('exclamation', 'Запись связана с другими данными'),
				'db' => array('exclamation', 'Ошибка базы данных'),
				'notvalidemail' => array('exclamation', 'Неверный e-mail'),
				'emailnotfound' => array('exclamation', 'Указанный e-mail не найден'),
				'passwords' => array('exclamation', 'Пароли не совпадают'),
				'password_length' => array('exclamation', 'Длина пароля должна быть не менне 6<sup>ти</sup> символов'),
				'notnull' => array('exclamation', 'Только натуральные числа'),
				'norules' => array('exclamation', 'Нет прав доступа'),
				'incorrectpassword' => array('exclamation', 'Неверная связка логин - пароль'),
				'notauthorized' => array('exclamation', 'Нет прав для просмотра этой страницы'),
				'notvalidcode' => array('exclamation', 'Неверный код подтверждения'),
				'usedemail' => array('exclamation', 'Указанный e-mail уже используется'),
			    
				'added' => array('tick', 'Данные успешно добавлены'),
				'changed' => array('tick', 'Данные успешно изменены'),
				'imported' => array('tick', 'Данные успешно импортированы'),
				'uploaded' => array('tick', 'Файлы успешно загружены'),
				'cleared' => array('tick', 'Данные удалены'),
				'send' => array('tick', 'Сообщение успешно отправлено'),
				'new_password_send' => array('tick', 'Новый пароль выслан на контактный email. Обратите внимание, Вы уже авторизованы на сайте.'),
				'registration_ok' => array('tick', 'Регистрация прошла успешно. На Ваш e-mail выслана регистрационная информация.'),
				'emailconfirmed' => array('tick', 'Ваш e-mail успешно подтвержден'),
				'activation_ok' =>  array('tick', 'Активация учетной записи прошла успешно')
			    );
                
    function __construct($base_url = '') {
		$this->base_url = ((trim($base_url)) ? $base_url : 'http://'.$_SERVER['HTTP_HOST']);
    }
   
    public function set_message($code, $icon, $message) {
		$this->messages[$code] = array($icon, $message);
    }

    public function add_message($code, $icon, $message) {
		if (!array_key_exists($code, $this->messages)) $this->messages[$code] = array($icon, $message);
		else return false;
    }

    public function copy_message($code, $code_new) {
		$this->messages[$code_new] = $this->messages[$code];
    }

    public function get_message($code, $return = false) {
		if (array_key_exists($code, $this->messages)) {
            if($return) return $this->show_message($this->messages[$code]);
            else echo $this->show_message($this->messages[$code]);
		} else
			return false;
    }
    
    public function show_message($message) {
		return '	<div id="st_message">
						<div id="st_message_image"><img src="'.$this->base_url.'/admin/images/icons/'.$message[0].'.png" alt=""></div>
						<div id="st_message_text">'.$message[1].'</div>
					</div>';
    } 
}
?>