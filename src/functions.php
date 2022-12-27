<?php

function cleaner($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

function GetImageId(){

    $image = $_FILES["uploadimage"];

    $Parent = dirname(__DIR__);
    $Path_Directory = $Parent . '\\src\public\assets\img\\';

    $validation = [];

    $Extension = pathinfo($image['name'], PATHINFO_EXTENSION);

    // Allow only PNG and JPEG
    $whitelist = [IMAGETYPE_PNG, IMAGETYPE_JPEG];
    if (!in_array(exif_imagetype($image['tmp_name']), $whitelist)) {
        array_push($validation, "Only PNG and JPG , JPEG images are allowed.");
        return $validation;
    }

    $imageid = uniqid();
    $Image_with_ID = $Path_Directory . $imageid . "." . $Extension;

    if (!move_uploaded_file($image["tmp_name"], $Image_with_ID)) {
        array_push($validation, "Error.");
        return $validation;
    }

    return $imageid . "." . $Extension;
    
}

function esc($string){
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function isLoggedin()
{
    if(isset($_SESSION['userid']))
    {
        return true;
    }

    return false;
}

?>