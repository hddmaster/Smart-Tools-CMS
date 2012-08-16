<?php

$_GET['plot_x'] = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
$_GET['plot_y'] = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
$color = 'FF0000';

if (isset($_GET['plot_x']) && $_GET['plot_x'] != '' &&
    isset($_GET['plot_y']) && $_GET['plot_y'] != '' &&
    isset($_GET['color']) && $_GET['color'] != '')
 {

   $plot_x = $_GET['plot_x'];
   $plot_y = $_GET['plot_y'];
   $color = $_GET['color'];
   $width = 250;
   $height = 100;
   $bg = imagecolorallocate($image, 255, 255, 255);

   if (count($plot_x) == 0 || count($plot_y) == 0 || strlen($color) > 6)
    {
      $image = imagecreatetruecolor(1, 1);
      imagefilledrectangle($image, 0, 0, imageSX($image), imageSY($image), $bg);
      header("Content-type: image/png");
      imagepng($image);
    }
   else
    {
      $image = imagecreatetruecolor($width, $height);
      $color = imagecolorallocate($image, hexdec(substr($color,0,2)), hexdec(substr($color,2,2)), hexdec(substr($color,4,2)));
      imagefilledrectangle($image, 0, 0, imageSX($image), imageSY($image), $bg);

      imageline($image);
      header("Content-type: image/png");
      imagepng($image);
    }

 }
else
 {
   $image = imagecreatetruecolor(1, 1);
   $bg = imagecolorallocate($image, 255, 255, 255);
   imagefilledrectangle($image, 0, 0, imageSX($image), imageSY($image), $bg);
   header("Content-type: image/png");
   imagepng($image);
 }
?>
