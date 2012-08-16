<?
session_start();
$_SESSION['code'] = rand(1000, 9999);

header('Content-type: image/png');
$image = imagecreatetruecolor(88, 31);
imageantialias($image, true);

//цвета
    $bg = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image,180,180,180);
    $grid_color = imagecolorallocate($image,230,230,230);

//рамка
imagefilledrectangle($image, 0, 0, imageSX($image), imageSY($image), $bg);

//сетка
for($i = 0; $i <= 88; $i += 2) imageline($image, 0, $i, 88, $i, $grid_color);
for($i = 0; $i <= 88; $i += 2) imageline($image, $i, 0, $i, 31, $grid_color);

//цифры
    imagettftext($image, rand(12, 19), rand(-45, 45), 8, 23, $text_color, $_SERVER['DOCUMENT_ROOT'].'/admin/images/cour.ttf', substr($_SESSION['code'], 0, 1));
    imagettftext($image, rand(12, 19), rand(-45, 45), 28, 23, $text_color, $_SERVER['DOCUMENT_ROOT'].'/admin/images/cour.ttf', substr($_SESSION['code'], 1, 1));
    imagettftext($image, rand(12, 19), rand(-45, 45), 48, 23, $text_color, $_SERVER['DOCUMENT_ROOT'].'/admin/images/cour.ttf', substr($_SESSION['code'], 2, 1));
    imagettftext($image, rand(12, 19), rand(-45, 45), 68, 23, $text_color, $_SERVER['DOCUMENT_ROOT'].'/admin/images/cour.ttf', substr( $_SESSION['code'], 3, 1));

//вывод
    imagepng($image);
?>
 