<?php
    
     include("../configs.php");

    $id = $_GET['id'];
    $sql = "SELECT * FROM loan_form WHERE id='$id'";
    $result= $conn->query($sql);
    if($result->num_rows>0){
         $row = $result->fetch_assoc();
        $pdf = $row['url'];
        ?>
        <div>
            <iframe src="../<?php echo $pdf ?>" frameborder="0" scrolling='auto' height="100%"
    width="100%" ></iframe>
        </div>
        <?php
        
    }

?>