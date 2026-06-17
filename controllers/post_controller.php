<?php
    session_start();
    if(!$_SESSION){
        echo "<script> window.history.back();</script>";
    }
    require_once "../configs.php";
    require_once "../functions/post_functions.php";
    $conn = openConn();
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      if(isset($_POST['create_post'])){
       // print_r($_POST);
       print_r($_FILES);

        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $content = $conn->real_escape_string($_POST['content']);
        $user = $_SESSION['userid'];

        // upload attachement if is there file
        $attachmentUrl = null;
        $uploadDir = '../uploads/posts/attachments/';
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc');
        $fileName = $_FILES['file']['name'];
         $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
         $fileTmpName = $_FILES['file']['tmp_name'];
          if (in_array($fileExtension, $allowedExtensions)) {
              $newFileName = uniqid() . '.' . $fileExtension;
              $uploadPath = $uploadDir . $newFileName;

              if (move_uploaded_file($fileTmpName, $uploadPath)) {
                  echo 'File uploaded successfully!';
                  $attachmentUrl = $newFileName;
              } else {
                  echo 'Error uploading file!';
                      }
                  } else {
                      echo 'Invalid file type!';
                  }
          $newPost = createPost($conn, $title,$description,$content,$user,null,$attachmentUrl);
          if($newPost){
            echo "<script> alert('Post created successfully'); window.history.back();</script>";
          }else{
            echo "<script> alert('Failed to create post'); window.history.back();</script>";
          }
        
      }
    }
?>