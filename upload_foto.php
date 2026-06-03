<?php

function upload_foto($file)
{
    $folder = "uploads/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $namaFile = time() . "_" . basename($file['name']);
    $target   = $folder . $namaFile;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $namaFile;
    }

    return null;
}