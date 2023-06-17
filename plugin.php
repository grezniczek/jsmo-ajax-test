<?php

    $img_src = $module->getUrl("img/1.png");

    $module->initializeJavascriptModuleObject();
    $jsmo_name = $module->getJavascriptModuleObjectName();

?>
<h4>Plugin Test Page</h4>
<p>The image source is: <b><?=htmlspecialchars($img_src)?></b></p>
<img src="<?=$img_src?>" alt="1">
<hr>
<button class="btn btn-sm btn-primary" data-action="ajax">Make AJAX request</button>
<pre data-output="data"></pre>
<pre data-output="error"></pre>


<script>
    const JSMO = <?=$jsmo_name?>;
    const $dataOut = $('[data-output="data"]');
    const $errorOut = $('[data-output="error"]');
    function clear() {
        $dataOut.text('').hide();
        $errorOut.text('').hide();
    }
    clear();
    $('button[data-action="ajax"]').on('click', function() {
        clear();
        JSMO.ajax('test').then(function(response) {
            $dataOut.text(JSON.stringify(response, null, 2)).show();
        }).catch(function(err) {
            $errorOut.text(err).show();
            JSMO.log('An ajax error occured', err).catch(function(err) {
                console.error('Logging failed:', err);
            });
        });
    });


</script>

<style>
    [data-output] {
        max-width: 800px;
        margin-top: 1em;
    }
    [data-output="data"] {
        padding: 1em;
        border: 1px solid green;
    }
    [data-output="error"] {
        padding: 1em;
        border: 1px solid red;
    }
</style>