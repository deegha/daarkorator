<?php

require_once 'SimpleImage.php';
require_once 'GifFrameExtractor.php';
require_once 'GifCreator.php';

if (!empty($_FILES)) {

    if (isset($_FILES['news_image']['name']) != "") {
        $dir = '../uploads/news/';
        $unique = strtoupper(md5(uniqid(rand(), true)));
        $image = new SimpleImage();
        $image->load($_FILES['news_image']['tmp_name']);
        $image->resizeToWidth(466);
        $ext = pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION);
        $image->save($dir . $unique . '.' . $ext);
        $response = array('error' => false, 'message' => 'File transfered completed!', 'image' => $unique . '.' . $ext);
        $json = json_encode($response);
        echo $json;
    } elseif (isset($_FILES['slider_image']['name']) != '') {
        $dir = '../uploads/slider/';
        $unique = strtoupper(md5(uniqid(rand(), true)));
        $image = new SimpleImage();
        $image->load($_FILES['slider_image']['tmp_name']);
        $image->resize(1899, 570);
        $ext = pathinfo($_FILES['slider_image']['name'], PATHINFO_EXTENSION);
        $image->save($dir . $unique . '.' . $ext);
        $response = array('error' => false, 'message' => 'File transfered completed!', 'image' => $unique . '.' . $ext);
        $json = json_encode($response);
        echo $json;
    }elseif (isset($_FILES['category_image']['name']) != '') {//catimage
        $dir = '../uploads/category/';
        $unique = strtoupper(md5(uniqid(rand(), true)));
        $image = new SimpleImage();
        $image->load($_FILES['category_image']['tmp_name']);
        $ext = pathinfo($_FILES['category_image']['name'], PATHINFO_EXTENSION);
        $image->save($dir . $_FILES['category_image']['name']);
       // move_uploaded_file($_FILES["category_image"]["tmp_name"], $dir) or die("not uploaded");
        $response = array('error' => false, 'message' => 'File transfered completed!', 'image' => $_FILES['category_image']['name']);
        $json = json_encode($response);
        echo $json;
    } elseif (isset($_FILES['event_image']['name']) != '') {
        $dir = '../uploads/events/';
        $unique = strtoupper(md5(uniqid(rand(), true)));
        $image = new SimpleImage();
        $image->load($_FILES['event_image']['tmp_name']);
        $image->resize(360, 360);
        $ext = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
        $image->save($dir . $unique . '.' . $ext);
        $response = array('error' => false, 'message' => 'File transfered completed!', 'image' => $unique . '.' . $ext);
        $json = json_encode($response);
        echo $json;
    } elseif (isset($_FILES['fixedads_image']['name']) != '') {
        $gifPath = $_FILES['fixedads_image']['tmp_name'];
        $tmp = new GifFrameExtractor();
        if ($tmp::isAnimatedGif($gifPath)) {
            $gfe = new GifFrameExtractor();
            $frames = $gfe->extract($gifPath);
            $frameImages = $gfe->getFrameImages();
            $frameDurations = $gfe->getFrameDurations();

            $gc = new GifCreator();
            $gc->create($frameImages, $frameDurations, 0);
            $gifBinary = $gc->getGif();
            $unique = strtoupper(md5(uniqid(rand(), true)));
            file_put_contents('../uploads/bannerAds/' . $unique . '.gif', $gifBinary);
            $response = array('error' => false, 'message' => 'File transfered completed!', 'image' => $unique . '.gif');
            $json = json_encode($response);
            echo $json;
        } else {
            $dir = '../uploads/bannerAds/';
            $unique = strtoupper(md5(uniqid(rand(), true)));
            $image = new SimpleImage();
            $image->load($_FILES['fixedads_image']['tmp_name']);
            //$image->resizeToWidth(466);
            $ext = pathinfo($_FILES['fixedads_image']['name'], PATHINFO_EXTENSION);
            $image->save($dir . $unique . '.' . $ext);
            $response = array('error' => false, 'message' => 'File transfered completed!', 'image' => $unique . '.' . $ext);
            $json = json_encode($response);
            echo $json;
        }
    } elseif (isset($_FILES['advertismentImage']['name']) != '') {
        $dir = '../uploads/advertisement/';
        $unique = strtoupper(md5(uniqid(rand(), true)));
        $image = new SimpleImage();
        $image->load($_FILES['advertismentImage']['tmp_name']);
        $ext = pathinfo($_FILES['advertismentImage']['name'], PATHINFO_EXTENSION);
        $image->resize(555, 415);
        $image->save($dir . $unique . '.' . $ext);
        $image->resize(260, 194);
        $image->save($dir . 'thumb/' . $unique . '.' . $ext);
        $response = array('error' => false, 'message' => 'File transfered completed!', 'image' => $unique . '.' . $ext);
        $json = json_encode($response);
        echo $json;
    } elseif (isset($_FILES['video_id']['name']) != '') {
        $unique = strtoupper(md5(uniqid(rand(), true)));
        $dir = '../uploads/video/' . $unique . '.' . pathinfo($_FILES["video_id"]["name"], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES["video_id"]["tmp_name"], $dir) or die("not uploaded");
        $response = array('error' => false, 'message' => 'File transfered completed!', 'video' => '../../../api/uploads/video/' . $unique . '.' . pathinfo($_FILES["video_id"]["name"], PATHINFO_EXTENSION));
        $json = json_encode($response);
        echo $json;
    } elseif (isset($_FILES['resume']['name']) != '') {
        $unique = strtoupper(md5(uniqid(rand(), true)));
        $ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
        if ($ext == "pdf" || $ext == "docx" ) {
            $dir = '../uploads/resume/' . $unique . '.' . pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION);
            $file = $unique . '.' . pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES["resume"]["tmp_name"], $dir) or die("not uploaded");
            $response = array('error' => false, 'message' => 'File transfered completed!', 'file' => $unique . '.' . pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION));
            $json = json_encode($response);
            echo $json;
            
        } else {
            
            $response = array('error' => True, 'message' => 'File type not support', 'file' => '../../../api/uploads/resume/' . $unique . '.' . pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION));
            $json = json_encode($response);
            echo $json;
        }
    } else {
        $response = array('error' => true, 'message' => 'Bad Request');
        $json = json_encode($response);
        echo $json;
    }
} else {

    echo 'No files';
}
?>