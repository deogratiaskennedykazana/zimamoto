<?php

      function createPost(
                      mysqli $conn,
                      string $title,
                      string $description,
                      string $body,
                      int $author,
                      ?string $img_url = null,
                      ?string $attachement_url = null
                  
      ) {

        if($conn === false){
            exit();
        }
        $sql = "INSERT INTO `posts`(`title`, `description`, `details`, `img_url`, `attachment_url`, `created_by`) VALUES (?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssis", $title, $description, $body, $img_url, $attachement_url, $author);
        return ($stmt->execute()) ? $stmt->insert_id : false;

      }

      function listPosts(mysqli $conn){
        if($conn === false){
            exit();
        }
        $sql = "SELECT posts.*, users.name FROM `posts` INNER JOIN users ON users.id = posts.created_by WHERE  posts.deleted_at IS NULL  ORDER BY `created_at` DESC";
        $stmt = $conn->prepare($sql);
        return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
        
      }
?>