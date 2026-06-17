
<?php
    $posts = listPosts($conn);
?>

<div class=" card card-info card-outline">
  <div class=" card-header">
    <h4 class=" card-title">Post List</h4>
  </div>
  <div class=" card-footer">
        <div class="row">
    <?php
    $posts = listPosts($conn);
    if ($posts) {
        foreach ($posts as $post) {
            ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $post['title']; ?></h5>
                        <p class="card-text"><?php echo $post['description']; ?></p>
                        <p class="card-text"><?php echo substr($post['details'], 0, 120); ?>...</p>
                        <p class="card-text">
                            <small class="text-muted">Created by <?php echo $post['name']; ?> on <?php echo date('M d, Y', strtotime($post['created_at'])); ?></small>
                        </p>
                        <a href="#" class="btn btn-primary">Read More</a>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="col-md-12">
            <div class="alert alert-info">
                No posts found.
            </div>
        </div>
        <?php
    }
    ?>
</div>

  </div>
</div>