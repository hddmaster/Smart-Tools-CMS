<?php
/**
 * Example script showing usage of phpExifRW class
 *
 * Vinay Yadav (vinayRas) < vinay@sanisoft.com >
 * http://www.sanisoft.com/phpexifrw/
 *
 * For more details on constants and methods look at the
 * documentation provided in doc/ folder
 *
 */

 $filename = "02280003.jpg";

 require("exif.inc");

 $er = new phpExifRW($filename);

 /*
  * Process the JPEG image
  */
 $er->processFile();
 
 /**
  * Generate a Link to view thumbnail. 
  * showThumbnail.php files need to be in the same directory.
  */
 if($er->ThumbnailSize > 0) {
        echo "<br><img src='".$er->showThumbnail()."'>";
 }
 /**
  * Show the image details along with Exif information.
  */
 $er->showImageInfo();

?>
