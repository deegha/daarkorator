<?php

require_once 'SimpleImage.php';
require_once 'GifFrameExtractor.php';
require_once 'GifCreator.php';

if (!empty($_FILES)) {

    if (isset($_FILES['news_image']['name']) != "") {
        $dir = '../uploads/';
        $unique = strtoupper(md5(uniqid(rand(), true)));
        $image = new SimpleImage();
        $image->load($_FILES['news_image']['tmp_name']);
        $ext = pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION);
        $image->save($dir . $unique . '.' . $ext);

        http_response_code(200);
        $response = array('error' => false, 'message' => 'File transfered completed!', 'image' => $unique . '.' . $ext);
        $json = json_encode($response);
        echo $json;
    } 
} else {
    http_response_code(500);
    $response = array('error' => false, 'message' => 'Error in uploading file ');
}
?>