<div class=" card card-danger">
    <div class=" card-header"> <h5 class=" card-title">Process Members' data</h5></div>
    
    <?php
//print_r($_POST);
use PhpOffice\PhpSpreadsheet\IOFactory;

          if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $data = [];
           if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK){
               $inputFileName = $_FILES['file']['tmp_name'];
               $branch_id = (int) $_POST['branch_id'];
               $branchDetails = SelectBranchById($conn, $branch_id);
               if($branchDetails && is_array($branchDetails)){
                ?>
                    <div class=" card-body">
                        <h5>Branch Name:<?= $branchDetails['name']?></h5>
                        <h5>Branch Address:<?= $branchDetails['address']?></h5>
                        <h5>Branch phone:<?= $branchDetails['phone']?></h5>
                    </div>
                <?php
               }
               try{
                $spreadSheet = IOFactory::load($inputFileName);
                $workSheet  = $spreadSheet->getActiveSheet();
                $highestRow = $workSheet->getHighestRow();
                $highestColumn = $workSheet->getHighestColumn();
                 $currentRow =1;
                 for($row = 1; $row <= $highestRow; $row++){
                     $rowData = $workSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                     $data[] = $rowData[0];
                 }

               } catch(Exception $e){
                echo 'Error reading the Excel file: ' . $e->getMessage();

               }

           }
        //    print_r($data);
           ?>
           <form action="./controllers/member_controllers.php" method="post" class=" was-validated">
                <input type="hidden" name="branch_id" value="<?= $branch_id ?>">
                <input type="hidden" name="uploadmemberdetails" value="uploadmemberdetails">
            <div class="card-body table-responsive">
                <table class=" table table-bordered table-striped table-sm">
                    <tr>
                        <td>#</td>
                        <td>Name</td>
                        <td>Phone</td>
                        <td>Address</td>
                        <td>Birthdate</td>
                        <td>District</td>
                        <td>Email</td>
                        <td>Gender</td>
                        <td>NIDA</td>
                        <td>check No</td>
                        <td>Reg No</td>
                    </tr>
                    <?php
                        $counter =1;
                        $firstRow = true;
                        $lastRow = false;
                        foreach($data as $member ){
                            if(empty($member[0])){
                                break;
                            }
                            $email = $member[6] ??  str_replace(' ', '', $member[1]) . '@default.com';
                            $nida = $member[8] ?? '000000';
                            $checkNo = $member[9] ?? '000000';
                            $regNo = $member[10] ?? '000000';
                            if ($firstRow) {
                                $firstRow = false;
                                continue; // Skip the first row
                            }
                            
                            echo "<tr>";
                                echo "<td>$counter</td>";
                                echo "<td> <input type='text' class=' form-control' required name='name[]' value='$member[1]'/></td>";
                                echo "<td><input type='tell' class=' form-control'  name='phone[]' value='$member[2]'/></td>";
                                echo "<td> <input type='text' class=' form-control'  name='address[]' value='$member[3]'/></td>";
                               echo "<td><input type='text' class='form-control' required 
                                        name='birthdate[]' 
                                        value='" . (!empty($member['4']) ? date("Y-m-d", strtotime($member['4'])) : 1980-01-01) . "'/></td>";

                                echo "<td>";
                                            ?>

                                            <select class=' form-control' required  class=' form-control' name="districtId[]">
                                                <?php
                                                $wilaya = selectDistrictByName($conn,$conn->real_escape_string( $member['5']));
                                                if($wilaya &&  is_array($wilaya)){
                                                    echo "<option value='$wilaya[id]'>$wilaya[name]</option>";
                                                }
                                                $districts = selectDistricts($conn);
                                                if($districts && is_array($districts)){
                                                    foreach($districts as $district){
                                                        echo "<option value='$district[id]'>$district[name]</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                <?php
                                  echo" </td>";
                                  echo "<td> <input type='email' class=' form-control' required name='email[]' value='$email'/></td>";
                                echo "<td>". ($member['7'] ==="ME" ? "<input type='text' class=' form-control' required name='gender[]' value='male'/>" : "<input type='text' class=' form-control' required name='gender[]' value='female'/>") . "  </td>";
                                echo "<td> <input type='text' class=' form-control' required name='nida[]' value='$nida'/></td>";
                                echo "<td><input type='text' class=' form-control' required name='checkno[]' value='$checkNo'/></td>";
                                echo "<td> <input type='text' class=' form-control' required name='regno[]' value='$regNo'/></td>";
                               
                            echo "</tr>";
                            $counter++;
                        }
                    ?>
                </table>
            </div>
            <div class=" card-footer">
                <input type="hidden" name="password" value="12345"  >
                <button type="submit" name="" class=" btn btn-info btn-sm btn-block">Upload Details</button>
            </div>
           </form>
           <?php
          }
    ?>
</div>