<?php
function config_nrk(): array
{
    $file = stream_resolve_include_path('config.php');
    if (!empty($file))
        return require $file;
    else
        return [];
}