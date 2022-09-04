<?php

    $img_src = $module->getUrl("img/1.png");

?>
<h4>Plugin Test Page</h4>
<p>The image source is: <b><?=htmlspecialchars($img_src)?></b></p>
<img src="<?=$img_src?>" alt="1">