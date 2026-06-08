<?php
    ob_start();
                            use Dompdf\Dompdf;
                            require_once("../dompdf/autoload.inc.php");
                            include("../configs.php");
                            $id = $_GET['id'];

                            
                            $dompdf = new Dompdf();
                            $html ='';
                            $css = file_get_contents('https://www.w3schools.com/w3css/4/w3.css');
                            $css .=file_get_contents('https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css');
                            $html .='<head><style>'.$css.'</style></head> ';

                            $c_name = '';
                            $c_addres = '';

                            $company_sql = "SELECT * FROM company_details";
                             $company_result = $conn->query($company_sql);
                             if($company_result->num_rows>0){
                                 $company_row = $company_result->fetch_assoc();
                                 $c_name = $company_row['title'];
                                 $c_address = $company_row['address'];
                 
                             }
                             $html .= '<div class=" w3-margin-bottom w3-margin-top w3-padding w3-row w3-border-bottom ">
                             
                             <div class="  " > <h5 class=" w3-center">'.$c_name.' </h5> <h6 class=" w3-center">'. $c_address .'</h6></div>
       
                         </div>';
                            
                           
                            $sql = "SELECT * FROM `transaction_voucher` WHERE `id`='$id'";
                            $result = $conn->query($sql);
                            if($result->num_rows>0){
                                
                                    $rows = $result->fetch_assoc();
                                    $cr_account_id = $rows['cr_account'];
                                    $dr_account_id = $rows['dr_account'];
                                    $dr_account_name = '';
                                    $cr_account_name = '';
                                    $amount = $rows['cr_ammount'];
                                    $voucher_type = $rows['voucher_type'];
                                    $date_ = $rows['date_'];
                                    $description = $rows['description'];
                                    $category_type = $rows['category_type'];
                                    $category_name ='';
                                    $voucher_name = '';

                                    $reference = $rows['reference_no'];

                                    $sql1 = "SELECT * FROM `subsidiaries` WHERE `id`='$dr_account_id'";
                                    $result1 = $conn->query($sql1);
                                    if($result1->num_rows>0){
                                        $rows1 = $result1->fetch_assoc();
                                        $dr_account_name = $rows1['name'];
                                    }
                                    $sql2 = "SELECT * FROM `subsidiaries` WHERE `id`='$cr_account_id'";
                                    $result2 = $conn->query($sql2);
                                    if($result2->num_rows>0){
                                        $rows2 = $result2->fetch_assoc();
                                        $cr_account_name = $rows2['name'];
                                    }
                                    $sql3 = "SELECT * FROM `category` WHERE `id`='$category_type'";
                                    $result3 = $conn->query($sql3);
                                    if($result3->num_rows>0){
                                        $rows3 = $result3->fetch_assoc();
                                        $category_name = $rows3['name'];
                                    } else{
                                        $category_name = "Not defined";
                                    }
                                    
                                    $sql4 = "SELECT * FROM `voucher` WHERE `id`='$voucher_type'";
                                    $result4 = $conn->query($sql4);
                                    if($result4->num_rows>0){
                                        $rows4 = $result4->fetch_assoc();
                                        $voucher_name = $rows4['name'];

                                    } else{
                                        // $voucher_name = "Not defined";
                                    }
                                    
                                     $html .= '<div class=" w3-margin-bottom w3-margin-top w3-padding  w3-border-bottom  ">
                            
                                    <div class="  " > <h4> '.$voucher_name.'</h4>
                                        
                                         
                                    <h5>REFERENCE NO:'.$reference.''."  ".'</br>  DATE:  '. date("d-m-Y", strtotime( $date_)).'  </h5>
                                    
                                    
                                    </div>

                                    </div>';
                                    $html .= "<div class='>";
                                        
                                   
                                      
                                    $html .= "<div class='  w3-margin'>";
                                    $html .= "<table class=' w3-table-all ' border='1' > ";
                                    
                                    $html .= "<tr class=' '>";
                                    $html .= "<td>ACCOUNT DETAILS</td>";
                                    $html .= "<td>DR</td>";
                                     $html .= "<td>CR</td>";
                                     $html .="</tr>";
                                      $html .="<tr>";
                                    $html .= "<td>$dr_account_name</td>";
                                    $html .= "<td>". number_format( $amount,2)."</td>";
                                    $html .= "<td></td>";
                                    $html .="</tr>";
                                       $html .="<tr>";
                                    $html .= "<td>$cr_account_name</td>";
                                    $html .= "<td></td>";
                                    $html .= "<td>". number_format( $amount,2)."</td>";
                                    
                                    $html .="</tr>";
                                    $html .= "</table> </br>";
                                    
                                    
                                    
                                    $html .= "<div>";
                                    
                                    
                                    $html .= "<H5>DESCRIPTION: $description  </H5>";
                                    
                                    $html.="</div>";
                                    
                                    
                                    $html .= "</div>";
                               
                                    $html .= "</div>";
                                        $html .= "<h6>PREPARED BY __________________________ SIGNATURE ____________________</h6><br><h6>APPROVED BY _________________________SIGNATURE_______________________</h6>";
                            }
                            
                          



                            $dompdf->loadHtml($html);
                            $dompdf->setPaper('A4', 'potrait');
                            $dompdf->render();
                            
                            $dompdf->stream('newfile',array('Attachment'=>0));




?>