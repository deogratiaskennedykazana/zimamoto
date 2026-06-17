<?php
include("../../configs.php");
?>
<script src="../../dist/js/functions.js"></script>
<?php
$conn  = openConn();
    $id = $_GET['id'];
    $sql = "SELECT currency.value FROM currency WHERE id='$id'";
    $query = mysqli_query($conn, $sql);
    if(mysqli_num_rows($query)>0){
        $row = mysqli_fetch_assoc($query);
        $value = $row['value'];
        ?>
        <!--<input  name="curr" id="curr_value" class=" w3-input w3-border"   value="<?php echo $value?>">-->
        <input type="text" name="curr" id="curr_value" class="form-control format-number" step="0.0001" required 
               value="<?php echo $value?>" 
               >
        <?php
    }
?>