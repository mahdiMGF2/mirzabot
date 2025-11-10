<?php
$dir = __DIR__; 
$parentDir = dirname($dir); 

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($it as $file) {
    if ($file->getFilename() !== 'delete_installer.php') { 
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
}

header('Location: ../index.php'); 
exit();
?>
