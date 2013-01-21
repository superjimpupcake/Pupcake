<?php
function getAutoLoader()
{
    $relative_dir = "/";
    $found_autoloader = false;
    $autoloader_path = "";
    for ($k=1;$k<=10;$k++ ) {
        $autoloader_path = __DIR__.$relative_dir."vendor/autoload.php";
        if (is_readable($autoloader_path)) {
            $found_autoloader = true;
            break;
        }
        else{
            $relative_dir .= "../";
        }
    }

    if ($found_autoloader) {
        require $autoloader_path;
    }
}

getAutoLoader();
