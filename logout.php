<?
session_start();
unset($_SESSION['valid_cms_user']);
setcookie('valid_cms_user',
          '',
          time()-1,
          '/admin/',
          '.'.str_replace('www.', '', $_SERVER['HTTP_HOST']));
header("Location: /admin/"); exit();
?>
