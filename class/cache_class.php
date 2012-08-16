<?
class Cache {
    public $module_id;
    public $url = '';
    public $session = '';
    public $hash;
    public $file;
      
    function __construct($module_id = 0, $url = '') {
		if ($module_id > 0 &&
			$url !== '' &&
			(!isset($_SERVER['REDIRECT_STATUS']) ||
			(isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == '200')
			)) {
			$this->module_id = $module_id;
			$this->url = $url;
			//if(isset($_SESSION) && count($_SESSION) > 0) $this->session = serialize($_SESSION);
			$this->hash = md5($this->module_id.$this->url.$this->session);
			$this->file = $_SERVER['DOCUMENT_ROOT'].'/cache/'.$this->module_id.'/'.$this->hash;

			if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/cache/'.$this->module_id)) {
				mkdir($_SERVER['DOCUMENT_ROOT'].'/cache/'.$this->module_id);
				chmod($_SERVER['DOCUMENT_ROOT'].'/cache/'.$this->module_id, 0777);
			}
		}
    }

	public function create_cache($data) {
		$data_hash = md5($data);
		$parent_id = 0;
		$result = mysql_query("select * from cache where data_hash = '$data_hash' and parent_id = 0");
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
			$parent_id = $row['cache_id'];
			symlink($row['file'], $this->file);
		} else {  
			$f = fopen($this->file, "w");
			flock($f, LOCK_EX);
			fwrite($f, $data);
			flock($f, LOCK_UN);
			fclose($f);
		}
     
		mysql_query("	insert into cache
						(	`parent_id`,
		                     `module_id`,
							`url`,
							`data_hash`,
							`file`,
							`date`)
						values
						(	$parent_id,
							$this->module_id,
							'$this->url',
							'$data_hash',
							'$this->hash',
							NOW())");
	}

	public function check_cache() {
		return ((file_exists($this->file)) ? true : false);
	} 

	public function get_cache() {
        //require_once лучше будет наверное, но тогда в index.php не echo
		return ((file_exists($this->file)) ? file_get_contents($this->file) : false);
        //после проверки наличия файла надо взять из базы статус 404, в большинстве случает будет false
	}

	public function clear_cache_by_content($module_id) {
        //костыль
        $this->clear_all_cache();

		$result = mysql_query("select * from cache where module_id = $module_id");
		if (mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_array($result)) unlink($_SERVER['DOCUMENT_ROOT'].'/cache/'.$module_id.'/'.$row['file']);
			mysql_query("delete from cache where module_id = $module_id");
		}
	}

	public function clear_cache_by_module() {
        //костыль
        $this->clear_all_cache();

		$group_id = 0;
		$result = mysql_query("select * from auth_scripts where script_path = '".$_SERVER['PHP_SELF']."'");
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
			$group_id = $row['group_id'];
		}

		if ($group_id > 0) {
			$result = mysql_query("select * from auth_script_group_content where group_id = $group_id");
			if (mysql_num_rows($result) > 0) {
				while ($row = mysql_fetch_array($result)) {
					$res = mysql_query("select * from cache where module_id = ".$row['module_id']);
					if (mysql_num_rows($res) > 0) {
						while($r = mysql_fetch_array($res)) unlink($_SERVER['DOCUMENT_ROOT'].'/cache/'.$row['module_id'].'/'.$r['file']);
						mysql_query("delete from cache where module_id = ".$row['module_id']);
					}
				}
			}
		}
	}

	public function clear_cache_by_content_associated($module_id) {
        //костыль
        $this->clear_all_cache();
        
		$result = mysql_query("select * from auth_script_group_content where module_id = $module_id");
		if (mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_array($result)) {
				$res = mysql_query("select * from auth_script_group_content where group_id = ".$row['group_id']);
				while ($r = mysql_fetch_array($res)) {
					$res2 = mysql_query("select * from cache where module_id = ".$r['module_id']);
					if (mysql_num_rows($res2) > 0) {
						while($r2 = mysql_fetch_array($res2)) unlink($_SERVER['DOCUMENT_ROOT'].'/cache/'.$r['module_id'].'/'.$r2['file']);
						mysql_query("delete from cache where module_id = ".$r['module_id']);
					} 
				}
			}
		}
	}
   
	public function clear_all_cache() {
		$path = $_SERVER['DOCUMENT_ROOT'].'/cache';
		if ($handle_dirs = opendir($path)) {
			while (false !== ($dir = readdir($handle_dirs))) {
				if( $dir !== '.' &&
					$dir !== '..' &&
					$dir !== '.htaccess') {
					if($handle_files = opendir($path.'/'.$dir)) {
						while (false !== ($file = readdir($handle_files)))
							if ($file !== '.' &&
								$file !== '..' &&
								$file !== '.htaccess') unlink($path.'/'.$dir.'/'.$file);
						closedir($handle_files);
					}
				
					rmdir($path.'/'.$dir); 
				}
			}
        
			closedir($handle_dirs);
			mysql_query("truncate cache");
		}
	}
 
	public function clear_all_image_cache() {
		$path = $_SERVER['DOCUMENT_ROOT'].'/cache_image';
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle)))
				if ($file != "." && $file != ".." && $file != ".htaccess" && !is_dir($file)) unlink($path.'/'.$file);
			closedir($handle);
		}
    }
   
	public function is_expired_by_date_content_associated($module_id) {
		$yesterday = time() - (date("H")*3600 + date("i")*60 + date("s"));
		$result = mysql_query("select * from auth_script_group_content where module_id = $module_id");
		if (mysql_num_rows($result) > 0) {
			while($row = mysql_fetch_array($result)) {
				$res = mysql_query("select * from auth_script_group_content where group_id = ".$row['group_id']);
				while ($r = mysql_fetch_array($res)) {
					$res2 = mysql_query("select * from cache where module_id = ".$r['module_id']." and date < $yesterday");
					if (mysql_num_rows($res2) > 0) return true;
				}
          
			}
		}
		return false;
	}
   
}
?>