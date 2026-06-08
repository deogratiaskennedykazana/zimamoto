<?php
    session_start();
    if(!$_SESSION){
      //print_r("hello");
       echo "<script> window.location.href='./login.php'</script>";
    }
   // print_r($_SESSION);
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title> SBI - SACCOSS </title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="./dist/css/custom.css">
  <link rel="stylesheet" href="./dist/datatable2/datatables.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css">
  <link rel="stylesheet" href="./plugins/select2/css/select2.min.css">

  <link rel="stylesheet" href="./plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="./plugins/select2/js/select2.full.min.js"></script>
<script scr="./dist/js/graphs_function.js"></script>

<!-- <script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script> -->
<!-- <script src="../../dist/js/adminlte.min.js"></script> -->
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <!-- <img class="animation__shake" src="dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60"> -->
  </div>
<!-- Requires -->
    <?php
        require_once "./vendor/autoload.php";
        require_once "./configs.php";
        require_once "./functions/member_functions.php";
        require_once "./functions/min_sub_functions.php";
        require_once "./functions/subsidiary_functions.php";
        require_once "./functions/utilities_functions.php";
        require_once "./functions/branch_functions.php";
        require_once "./functions/ledger_functions.php";
        require_once "./functions/opening_balance_functions.php";
        require_once "./functions/transaction_functions.php";
        require_once "./functions/min_transaction_functions.php";
        require_once "./functions/user_function.php";
        require_once "./functions/loan_functions.php";
        require_once "./functions/master_functions.php";
        require_once "./functions/submain_functions.php";
        require_once "./functions/notification_functions.php";
        require_once "./functions/budget_functions.php";
        require_once "./functions/meeting_functions.php";
        require_once "./functions/role_functions.php";
        require_once "./functions/grantor_functions.php";
        $conn = openConn();
        include './toast_notification.php';
      
    ?>

   <!-- end of requires -->
<!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="./" class="nav-link">Home</a>
      </li>
      <li class="nav-item  d-sm-inline-block">
        <a href="./?page=logout" class="nav-link">Logout</a>
       
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>

      <!-- Messages Dropdown Menu -->


      <!-- Notifications Dropdown Menu -->
      <li class="nav-item">
        <a class="nav-link" href="./?page=notifications">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">
            <?= countUnreadNotifications($conn, $_SESSION['userid']) + countPendingGrantorRequests($conn, $_SESSION['userid']) ?>
          </span>
        </a>
      </li>


    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index3.html" class="brand-link">
      <!-- <img src="dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8"> -->
      <span class="brand-text font-weight-light">Saccos System</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <!-- <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image"> -->
        </div>
        <div class="info">
          <a href="./?page=user_profile"  class="d-block"><?php echo  $_SESSION['username'] ?? 'Default'?></a>
        </div>
      </div>


     <?php
            include("./sidebar.php");
     ?>
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
           
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">

              <li class="breadcrumb-item active">Saccos V2.1 </li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <?php
          $page = $_GET['page'] ?? $_POST['page'] ?? 'default';
          switch ($page) {
               default:
            ?>
              <div class="">
                  <div class=" row">
                      <div class=" col-md-12 col-sm-12">
                        <?php
                          include("./views/dashboard/member.php");
                        ?>
                      </div>
                      <div class="col-md-2 d-none d-sm-block"> 
                          <?php
                            include("./views/dashboard/notification.php");
                          ?>

                      </div>
                  </div>
              </div>
            <?php
        break;
        
        case 'create_subsidiary':
            include "./views/subsidiaries/create_subsidiary.php";
            break;
        case 'register_asset':
            include "./views/subsidiaries/register_asset.php";
            break;
        case 'register_staff':
            include "./views/subsidiaries/register_staff_sub.php";
            break;
        case 'register_stock':
            include "./views/subsidiaries/register_stock.php";
            break;
        case 'add_other_subsidiary':
            include "./views/subsidiaries/add_other_subsidiary.php";
            break;
        case 'edit_subsidiary':
            $id = (int) $_GET['id'];
            include "./views/subsidiaries/edit_subsidiary.php";
            break;
        case 'delete_subsidiary':
            $deleted_by = (int) $_SESSION['userid'];
            $id = (int) $_GET['id'];
            $conn->begin_transaction();
            $softDeleteSub = softDeleteSubsidiary($conn, $id, $deleted_by);
            if (!is_numeric($softDeleteSub)) {
                $conn->rollback();
                echo $softDeleteSub;
                exit;
            }
            $deleteTransactions = softDeleteTransactionBySubId($conn, $id, $deleted_by);
            if ($deleteTransactions !== true) {
                $conn->rollback();
                echo $deleteTransactions;
                exit;
            }
            $conn->commit();
            setNotification('success', 'Deleted');
            echo "<script>window.location.href='./?page=subsidiary_list';</script>";
            break;
        case 'restore_subsidiary':
            $restored_by = (int) $_SESSION['userid'];
            $id = (int) $_GET['id'];
            $conn->begin_transaction();
            $restoreDeletedSub = restoreDeletedSubsidiary($conn, $id, $restored_by);
            if (!is_numeric($restoreDeletedSub)) {
                $conn->rollback();
                echo $restoreDeletedSub;
                exit;
            }
            $restoreTransactions = restoreDeletedTransactionBySubId($conn, $id, $restored_by);
            if ($restoreTransactions !== true) {
                $conn->rollback();
                echo $restoreTransactions;
                exit;
            }
            $conn->commit();
            setNotification('success', 'Restored');
            echo "<script>window.location.href='./?page=subsidiary_list';</script>";
            break;
        
        
        
  case"subsidiary_report":
            ?>
                <div class=' container'>
                  <div class=' card card-primary'>

                  <div class=' card-header'>Select Subsidiary</div>
                        <form action='./reports/view_sub_report.php' class=" was-validated">
                            <div class=' card-body'>
                            <h3>View Subsidiary report </h3>
                            <select class=' form-control select2 selectbs4' name='sub_id' required>
                               <option value=''>Select Below</option>
                               <?php
                                  $subsidiaries =getAllSubsidiaries($conn, "deleted");
                                  if($subsidiaries && is_array($subsidiaries)){
                                    foreach($subsidiaries as $subsidiary){
                                      echo "<option value='$subsidiary[id]'>$subsidiary[name]</option>";
                                    }
                                  }
                               ?>

                            </select>
                            </div>
                            <div class=' card-footer'>
                                <button type="submit"  class=" m-3 btn btn-sm btn-outline-primary">POST</button>
                           </div>
                        </form>
                    </div>
                  </div>
                </div>
            <?php
          break;

          case"subsidiary_report_date_range_form":
            ?>
                <div class=' container'>
                  <div class=' card card-primary'>
                  <div class=' card-header'>Select Subsidiary</div>
                        <form action='./?page=subsidiary_report_by_date_range' method='post' class=" was-validated">
                            <div class=' card-body'>
                             <div class=" form-label">
                                
                                  <select class=' form-control select2-form selectbs4-form' name='sub_id' required>
                                    <option value=''>Select Below</option>
                                    <?php
                                        $subsidiaries = selectAllSubsidiaries ($conn);
                                        if($subsidiaries && is_array($subsidiaries)){
                                          foreach($subsidiaries as $subsidiary){
                                            echo "<option value='$subsidiary[id]'>$subsidiary[name]</option>";
                                          }
                                        }
                                    ?>

                                  </select>
                                  </div>
                                  <div class=" form-group"> 
                                        <label for="">Start Date</label>
                                        <input type="date" class=" form-control" required name="date1" max="<?= date("Y-m-d") ?>" id="">
                                  </div>
                                  <div class=" form-group">
                                        <label for="">End Date</label>
                                        <input type="date" class=" form-control" required name="date2" max="<?= date("Y-m-d") ?>" id="">
                                  </div>
                                
                            
                         </div>
                            <div class=' card-footer'>
                                <button type="submit"  class=" m-3 btn btn-sm btn-outline-primary btn-block">REQUEST REPORT</button>
                           </div>
                    
                        </form>
                  </div>
                </div>
            <?php
            break;
            
            case"add_min_opening_balance":
                  include("./views/opening_balance/add_min_sub_opening_balance.php");
                break;
                  
                case"min_sub_opening_balance_list":
                  include("./views/opening_balance/min_sub_opening_balance_list.php");
                break; 
                
            case"income_statement_sub_by_cost_center":
              include("./views/financial_report/income_statement_sub_by_cost_center.php");
            break;
            case"income_statement_by_ledger_cost_center":
              include("./views/financial_report/income_statement_by_ledger_cost_center.php");
            break;
            
             case"delete_min_sub_opening_balance":
                    $id = (int) $conn->real_escape_string($_GET['id']);
                    $deleteOpeningBalance = deleteMinSubOpeningBalance($conn, $id);
                    if (!$deleteOpeningBalance) {
                        $deleteOpeningBalance;
                        return;
                    }
                    echo "<script> alert('SUCCESS'); window.history.back(); </script>";
                break; 
                
            case"subsidiary_report_by_date_range":
              print_r($_POST);
              $sub_id = (int) $_POST['sub_id'];
              $date1 = $conn->real_escape_string($_POST['date1']) ;
              $date2 = $conn->real_escape_string($_POST['date2']) ;
            include("./views/financial_report/subsidiary_report_by_date_range.php");
            break;
             case"ledger_report_form":
                include("./views/financial_report/ledger_report_form.php");
              break;
              case"ledger_report":
                include("./views/financial_report/ledger_report.php");
              break;
              case"Income_statement_form":
                include("./views/financial_report/income_statement_form.php");
              break;
              case"income_statement_subsidiary":
                include("./views/financial_report/income_statement_subsidiary.php");
              break;
              case"income_statement_ledger":
                include("./views/financial_report/income_statement_ledger.php");
              break;
               case"coa":
              include("./views/core/chart_of_account.php");
              break;
               
              
              
               case"purchase_voucher":
            include("./views/voucher/purchase_voucher.php");
            
          break;
           case"trial_balances":
            ?>
                <div class=' container'>
                  <div class=' row'>
                        <div class=' col-md-5 col-sm-10'>
                          <div class=' card card-secondary '>
                            <div class=' card-header'>Trial Balance Subsidiary</div>
                            <form action="./reports/trial_balance.php" method="post">
                              <div class=' card-body'>
                                <H3 class=" form-label">Select Accounting Period</H3>
                                <div class="m-2">
                                    <label for="" class=" form-label">Starting date:</label>
                                    <input type="date" name="date1" id="" class=" form-control">

                                </div>
                                <div class=" m-2">
                                    <label for="" class=" form-label">Select End date</label>
                                    <input type="date" name="date2" id="" class=" form-control">
                                </div>
                                <br>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="report" class=" btn btn-secondary">Get Report</button>
                              </div>

                            </form>
                          </div>
                  </div>
                        <div class=' col-md-5 col-sm-10'>
                          <div class=' card card-warning '>
                            <div class=' card-header'>View Trial Balance  By Ledger</div>
                            <form action="./reports/trial_balance_leger.php" method="post">
                              <div class=' card-body'>
                                <H3 class=" form-label">Select Accounting Period</H3>
                                <div class="m-2">
                                    <label for="" class=" form-label">Starting date:</label>
                                    <input type="date" name="date1" id="" class=" form-control">

                                </div>
                                <div class=" m-2">
                                    <label for="" class=" form-label">Select End date</label>
                                    <input type="date" name="date2" id="" class=" form-control">
                                </div>
                                <br>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="report" class=" btn btn-warning">Get Report</button>
                              </div>

                            </form>
                          </div>
                  </div>
                </div>
          </div>
            <?php
          break;
          case"sales_voucher":
            include("./views/voucher/sales_voucher.php");
          break;
            ?>
                <div class=' container'>
                  <div class=' row'>
                        <div class=' col-md-5 col-sm-10'>
                          <div class=' card card-secondary '>
                            <div class=' card-header'>Trial Balance Subsidiary</div>
                            <form action="./reports/trial_balance.php" method="post">
                              <div class=' card-body'>
                                <H3 class=" form-label">Select Accounting Period</H3>
                                <div class="m-2">
                                    <label for="" class=" form-label">Starting date:</label>
                                    <input type="date" name="date1" id="" class=" form-control">

                                </div>
                                <div class=" m-2">
                                    <label for="" class=" form-label">Select End date</label>
                                    <input type="date" name="date2" id="" class=" form-control">
                                </div>
                                <br>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="report" class=" btn btn-secondary">Get Report</button>
                              </div>

                            </form>
                          </div>
                  </div>
                        <div class=' col-md-5 col-sm-10'>
                          <div class=' card card-warning '>
                            <div class=' card-header'>View Trial Balance  By Ledger</div>
                            <form action="./reports/trial_balance_leger.php" method="post">
                              <div class=' card-body'>
                                <H3 class=" form-label">Select Accounting Period</H3>
                                <div class="m-2">
                                    <label for="" class=" form-label">Starting date:</label>
                                    <input type="date" name="date1" id="" class=" form-control">

                                </div>
                                <div class=" m-2">
                                    <label for="" class=" form-label">Select End date</label>
                                    <input type="date" name="date2" id="" class=" form-control">
                                </div>
                                <br>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="report" class=" btn btn-warning">Get Report</button>
                              </div>

                            </form>
                          </div>
                  </div>
                </div>
          </div>
            <?php
          break;
           case"balance_sheets":
            ?>
                <div class=' container'>
                  <div class=' row'>
                        <div class=' col-md-5 col-sm-10'>
                          <div class=' card card-success '>
                            <div class=' card-header'>View Balance Sheet By Subsidiary</div>
                            <form action="./reports/balance_sheet.php" method="post">
                              <div class=' card-body'>
                                <H3 class=" form-label">Select Accounting Period</H3>
                                <div class="m-2">
                                    <label for="" class=" form-label">Starting date:</label>
                                    <input type="date" name="date1" id="" class=" form-control">

                                </div>
                                <div class=" m-2">
                                    <label for="" class=" form-label">Select End date</label>
                                    <input type="date" name="date2" id="" class=" form-control">
                                </div>
                                <br>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="report" class=" btn btn-success">Get Report</button>
                              </div>

                            </form>
                          </div>
                  </div>
                        <div class=' col-md-5 col-sm-10'>
                          <div class=' card card-warning '>
                            <div class=' card-header'>View Balance Sheet By Ledger</div>
                            <form action="./reports/balance_sheet_by_ledger.php" method="post">
                              <div class=' card-body'>
                                <H3 class=" form-label">Select Accounting Period</H3>
                                <div class="m-2">
                                    <label for="" class=" form-label">Starting date:</label>
                                    <input type="date" name="date1" id="" class=" form-control">

                                </div>
                                <div class=" m-2">
                                    <label for="" class=" form-label">Select End date</label>
                                    <input type="date" name="date2" id="" class=" form-control">
                                </div>
                                <br>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" name="report" class=" btn btn-warning">Get Report</button>
                              </div>

                            </form>
                          </div>
                  </div>
                </div>
          </div>
            <?php
          break;
             //member based items/cases
                case"my_loan":
                  include("./views/loan/my_loan.php");
                  break;
                case "apply_user_loan":
                  include("./views/loan/user_apply_loan.php");
                  break;
                  case"preview_loan":
                    include("./views/loan/preview_loan.php");
                  break;
              //end of member based items/cases
                //branch based case
                  case"branch_pending_loan":
                    include("./views/loan/branch_pending_loan.php");
                  break;
                  case"branch_process_loan":
                    include("./views/loan/branch_process_loan.php");
                  break;
              //end of branch based case
            // case"logout":
            //     session_destroy();
            //     session_unset();
            //     echo "<script>window.history.back();</script>";
            //   break;
             // loan issues
            case"approved_loan_list_form":
              include("./views/loan/approved_loan_list_form.php");
             break;
           case"approved_loan_list":
                    include("./views/loan/approved_loan_list.php"); 
                    break;
            case"view_schedule":
              include("./views/loan/loan_schedule.php");
            break;
            case"Pending_loan_list_form":
              include("./views/loan/pending_loan_list_form.php");
              break;
            case"pending_loan_list":
              include("./views/loan/pending_loan_list.php");
            break;
            case"apply_loan":
              include("./views/loan/apply_loan.php");
            break;
            case"process_loan":
              include("./views/loan/process_loan.php");
              break;
               case"download_loan_form":
              include("./views/loan/download_loan_form.php");
              break;
            // end of loan issue
            case "Add_masters";
                include("./views/master/master_list.php");
            break;
            case "submaster":
                include("./views/submaster/submaster_list.php");
            break;
            case"create_ledger":
              include("./views/ledger/create_ledger.php");
            break;
            case "ledger":
              include("./views/ledger/ledger_list.php");
            break;
            // subsidiary
            case"Register_supplier":
              include("./views/subsidiary/register_supplier.php");
            break;
            case"register_customer":
              include("./views/subsidiary/register_customer.php");
            break;
            case "register_other_subs":
              include("./views/subsidiary/register_other_subs.php");
            break;
            case "subsidiary_list":
              include("./views/subsidiary/subsidiary_list.php");
            break;
            case"delete_sub":
            echo "good";
            $id = (int) $_GET['sub'];
            $softDeleteSub = softDeleteSubsidiary($conn, $id);
            if(!$softDeleteSub){
              echo $softDeleteSub;
              return;
            }
            echo "<script>alert('success'); window.location.href='./?page=subsidiary_list'</script>";
            break;
            // endo fo subsidiary
            // begin of min subs
            case"register_min_sub":
              include("./views/min_subsidiary/create_min_subsidiary.php");
            break;
            case"min_sub_list":
              include("./views/min_subsidiary/min_sub_list.php");

            break;
            // end of min subs
            // begine of branch
            case"register_branch":
              include("./views/branch/register_branch.php");
             break;
             case"branch_list":
              include("./views/branch/branch_list.php");
            break;
              case"view_branch_details":
              include("./views/branch/view_branch_details.php");
              break;
              // member management
            case "register_member":
              include("./views/member/register_member.php");
            break;  

            case "upload_member":
              ?>
                  <div class=" card card-info">
                    <div class=" card-header"> <h4 class=" card-title">Member Upload Form</h4> </div>
                    <form action="./?page=processes_member_data" method="post" enctype="multipart/form-data" class=" was-validated">
                      <div class=" card-body">
                      <div class=" form-group">
                          <label for="" class=" form-label">branch</label>
                          <select name="branch_id" class=" form-control select2-form select2bs4-form" required  id="">
                               
                               <?php
                                                $branchId = null;
                                                if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                    $branchId = $_SESSION['branchid'];  
                                                }
                                    
                                                $branches = selectAllBranches($conn, $branchId);
                                    
                                                if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                    echo '<option value="">--Select Below--</option>';
                                                     
                                                    
                                                }
                                    
                                                if ($branches && is_array($branches)) {
                                                    foreach ($branches as $result) {
                                                        $selected = ($branchId == $result['id']) ? 'selected' : '';
                                                        echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                                                    }
                                                }
                                            ?>
                          </select>
                      </div>
                      <div class=" form-group">
                                  <label for="">Select File</label>
                                  <input type="file" name="file" id="" class=" form-control" required accept=".csv, .xls, .xlsx">
                      </div>
                      </div>
                      <div>
                        <div class=" card-footer">
                                  <button type="submit" class=" btn btn-sm btn-info btn-block">Process Data</button>
                        </div>
                      </div>
                    </form>
                  </div>
              <?php
              break;
              case"processes_member_data":

                include("./views/member/processes_member_data.php");
              break;
              
             
              
              
              case"user_profile":
?>
<div class="card card-info">
  <div class="card-header">
    <h4 class="card-title">User Profile</h4>
  </div>
  <div class="card-body">
    <?php
      $myid = $_SESSION['userid'];
      $user = $conn->query("SELECT * FROM users WHERE id = $myid")->fetch_assoc();
        if (isset($_POST['restp'])) {
                $uid = $_POST['userid'];
                $newpass = password_hash($_POST['newpass'], PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password='$newpass' WHERE id=$uid");
                echo "<script>alert('SUCCESS'); window.location.href='./?page=logout'</script>"; 
              }
        
        
              if (isset($_POST['update_info'])) {
                $id = $_POST['id'];
                $name = $_POST['names'];
                //$phone = $_POST['phone'];
                $email = $_POST['email'];
               
                $conn->query("UPDATE users SET 
                  name='$name',
                  
                  email='$email'
                  WHERE id=$id
                ");
               echo "<script>alert('SUCCESS'); window.location.href='./?page=user_profile'</script>"; 
                $user = $conn->query("SELECT * FROM users WHERE id = $myid")->fetch_assoc(); // refresh
         }
    ?>

    <!-- Display User Info -->
    <div class="row">
      <div class="form-group col-md-6">
        <label>Full Name</label>
        <input type="text" class="form-control" value="<?= $user['name'] ?>" readonly>
      </div>
     
      <div class="form-group col-md-6">
        <label>Email</label>
        <input type="text" class="form-control" value="<?= $user['email'] ?>" readonly>
      </div>
      <div class="form-group col-md-6">
        <label>User Type</label>
        <input type="text" class="form-control" value="<?= $user['type'] ?>" readonly>
      </div>
     
      <div class="form-group col-md-4">
        <label>Status</label>
        <input type="text" class="form-control" value="<?= $user['status'] ?>" readonly>
      </div>
      
    </div>

    <!-- Edit Button -->
    <button class="btn btn-warning mt-3" onclick="openModal()">Edit Info</button>

    <hr class="my-4">

    <!-- Reset Password Section -->
    <h5 class="mt-4">Reset Password</h5>
    <form method="post" onsubmit="return confirm('Are you sure you want to reset your password to: ' + document.getElementsByName('newpass')[0].value + ' ?')">
      <input type="hidden" name="userid" value="<?= $user['id'] ?>">
      <div class="row">
        <div class="form-group col-md-8">
          <input type="password" name="newpass" class="form-control" placeholder="New Password" required>
        </div>
        <div class="form-group col-md-4">
          <button type="submit" name="restp" class="btn btn-info w-100">Reset Password</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal for Editing -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit User Info</h5>
          <button type="button" class="close" onclick="closeModal()" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $user['id'] ?>">

          <div class="row">
            <div class="form-group col-md-6">
              <label>Full Name</label>
              <input type="text" name="names" class="form-control" value="<?= $user['name'] ?>" required>
            </div>
           
            <div class="form-group col-md-6">
              <label>Email</label>
              <input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" required>
            </div>
            <div class="form-group col-md-6">
              <label>User Type</label>
              <select name="usertype" class="form-control" disabled>
                <?php
                  $types = ['staff', 'Manager', 'accountant', 'Admin'];
                  foreach ($types as $type) {
                    $selected = ($user['usertype'] == $type) ? 'selected' : '';
                    echo "<option value='$type' $selected>$type</option>";
                  }
                ?>
              </select>
            </div>
            
            <div class="form-group col-md-4">
              <label>Status</label>
              <select name="status" class="form-control" disabled>
                <?php
                  $statuses = ['active', 'suspended', 'deleted'];
                  foreach ($statuses as $status) {
                    $selected = ($user['status'] == $status) ? 'selected' : '';
                    echo "<option value='$status' $selected>$status</option>";
                  }
                ?>
              </select>
            </div>
            
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_info" class="btn btn-primary">Update Info</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openModal() { $('#editModal').modal('show'); }
function closeModal() { $('#editModal').modal('hide'); }
</script>

<?php
break;
              
     case "logout":
     // make sure session is active
    session_unset(); // unset all session variables
    session_destroy(); // destroy the session
    ?>
    <script>
        window.location.href = './login.php';
    </script>
    <?php
    break;
              
              
            case"upload_contributions":
                ?>
                <div class="row">
                    <!-- Branch-Specific Upload Card -->
                    <div class="col-md-6">
                        <div class="card card-info">
                            <div class="card-header"> 
                                <h4 class="card-title">Upload by Branch</h4> 
                            </div>
                            <form action="./?page=processes_member_contribution_data" method="post" enctype="multipart/form-data" class="was-validated">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="" class="form-label">Branch</label>
                                        <select name="branch_id" class="form-control select2-form select2bs4-form" required id="">
                                            <?php
                                                $branchId = null;
                                                if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                    $branchId = $_SESSION['branchid'];  
                                                }
                                    
                                                $branches = selectAllBranches($conn, $branchId);
                                    
                                                if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                    echo '<option value="">--Select Below--</option>';
                                                }
                                    
                                                if ($branches && is_array($branches)) {
                                                    foreach ($branches as $result) {
                                                        $selected = ($branchId == $result['id']) ? 'selected' : '';
                                                        echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Select type of contribution</label>
                                        <select name="type" class="form-control" required>
                                            <option value="">select below</option>
                                            <option value="amana">Amana Contribution</option>
                                            <option value="saving">Saving Contribution</option>
                                            <option value="share">Share Contribution</option>
                                          
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Date</label>
                                        <input type="date" name="date" class="form-control" required id="">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Select File</label>
                                        <input type="file" name="file" id="" class="form-control" required accept=".csv, .xls, .xlsx">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-sm btn-info btn-block">Process Branch Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
            
                    <!-- General Upload Card (No Branch Selection) -->
                    <div class="col-md-6">
                        <div class="card card-success">
                            <div class="card-header"> 
                                <h4 class="card-title">General Upload</h4> 
                            </div>
                            <form action="./?page=processes_general_contribution_data" method="post" enctype="multipart/form-data" class="was-validated">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="">Select type of contribution</label>
                                        <select name="type" class="form-control" required>
                                            <option value="">select below</option>
                                            <option value="amana">Amana Contribution</option>
                                            <option value="saving">Saving Contribution</option>
                                            <option value="share">Share Contribution</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Date</label>
                                        <input type="date" name="date" class="form-control" required id="">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Select File</label>
                                        <input type="file" name="file" id="" class="form-control" required accept=".csv, .xls, .xlsx">
                                    </div>
                                    
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-sm btn-success btn-block">Process General Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-warning">
                            <div class="card-header"> 
                                <h4 class="card-title">General Balance Corr Upload</h4> 
                            </div>
                            <form action="./?page=processes_general_balance_contribution_data" method="post" enctype="multipart/form-data" class="was-validated">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="">Select type of contribution</label>
                                        <select name="type" class="form-control" required>
                                            <option value="">select below</option>
                                            <option value="amana">Amana Contribution</option>
                                            <option value="saving">Saving Contribution</option>
                                            <option value="share">Share Contribution</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Date</label>
                                        <input type="date" name="date" class="form-control" required id="">
                                    </div>
                                    <div class="form-group">
                                        <label for="">Select File</label>
                                        <input type="file" name="file" id="" class="form-control" required accept=".csv, .xls, .xlsx">
                                    </div>
                                    
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-sm btn-warning btn-block">Process Balance Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                </div>    
                    
                <?php
                break;
            
            case"processes_member_contribution_data":
                $branchId = (int) $_POST['branch_id'];
                $type = $_POST['type'];
                $date = (string) $_POST['date'];
               // print_r($_POST);
                include("./views/member/process_member_contribution.php");
                break;
            
            case"processes_general_contribution_data":
                $type = $_POST['type'];
                print_r($_POST);
                $date = (string) $_POST['date'];
                include("./views/member/process_general_contribution.php");
                break;
                
                case"processes_general_balance_contribution_data":
                $type = $_POST['type'];
                print_r($_POST);
                $date = (string) $_POST['date'];
                include("./views/member/processes_general_balance_contribution_data.php");
                break;
              
              case"processes_loan_contribution_data":
              $branchId = (int) $_POST['branch_id'];
              //$type =  $_POST['type'];
              //$date = (string) $_POST['date'];
              include("./views/loans/process_loan_contribution.php");
              break;
            case"all_member_list":
              include("./views/member/all_member_list.php");
            break;
            case"member_list_per_branch":
              include("./views/member/list_branch_member.php");
            break;
              case"update_member_list_per_branch":
              include("./views/member/update_member_list_per_branch.php");
            break;
             case"edit_member":
              include("./views/member/edit_member.php");
            break;
             case"change_branch_member":
              include("./views/member/change_branch_member.php");
            break;
           case"upload_loan":
                ?>
                <div class="row">
                    <!-- Branch-Specific Loan Upload Card -->
                    <div class="col-md-6">
                        <div class="card card-info">
                            <div class="card-header"> 
                                <h4 class="card-title">Loan Upload by Branch</h4> 
                            </div>
                            <form action="./?page=process_loan_upload" method="post" enctype="multipart/form-data" class="was-validated">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="">Select Branch</label>
                                        <select name="branch_id" class="form-control select2-form select2bs4-form" required id="">
                                            <?php
                                                $branchId = null;
                                                if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                    $branchId = $_SESSION['branchid'];  
                                                }
                                    
                                                $branches = selectAllBranches($conn, $branchId);
                                    
                                                if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                    echo '<option value="">--Select Below--</option>';
                                                }
                                    
                                                if ($branches && is_array($branches)) {
                                                    foreach ($branches as $result) {
                                                        $selected = ($branchId == $result['id']) ? 'selected' : '';
                                                        echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Select Loan File</label>
                                        <input type="file" name="file" id="" class="form-control" required accept=".csv, .xls, .xlsx">
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-sm btn-info btn-block" name="upload_loan">Process Branch Loan Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
            
                    <!-- General Loan Upload Card (No Branch Selection) -->
                    <div class="col-md-6">
                        <div class="card card-warning">
                            <div class="card-header"> 
                                <h4 class="card-title">General Loan Upload</h4> 
                            </div>
                            <form action="./?page=process_general_loan_upload" method="post" enctype="multipart/form-data" class="was-validated">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Disbursement Date</label>
                                        <input name="date" type='date' required class='form-control'  />
                                    </div>
                                    <div class="form-group">
                                        <label for="">Select Loan File</label>
                                        <input type="file" name="file" id="" class="form-control" required accept=".csv, .xls, .xlsx">
                                    </div>
                                    
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-sm btn-warning btn-block" name="upload_general_loan">Process General Loan Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
                break;
            
            case"process_loan_upload":
                $branchId = (int) $_POST['branch_id'];
                include("./views/loan/upload_loan.php");
                break;
            
            case"process_general_loan_upload":
                // No branch_id needed - will be detected from member data
                include("./views/loan/upload_general_loan.php");
                break;
            // end of branch
            // voucher
            
            
              case"upload_loan_repayments":
    ?>
    <div class="row">
        <!-- Branch-Specific Loan Repayment Upload Card -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header"> 
                    <h4 class="card-title">Loan Repayment Upload by Branch</h4> 
                </div>
                <form action="./?page=process_loan_repayment_upload" method="post" enctype="multipart/form-data" class="was-validated">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Select Branch</label>
                            <select name="branch_id" class="form-control select2-form select2bs4-form" required id="">
                                <?php
                                    $branchId = null;
                                    if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                        $branchId = $_SESSION['branchid'];  
                                    }
                        
                                    $branches = selectAllBranches($conn, $branchId);
                        
                                    if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                        echo '<option value="">--Select Below--</option>';
                                    }
                        
                                    if ($branches && is_array($branches)) {
                                        foreach ($branches as $result) {
                                            $selected = ($branchId == $result['id']) ? 'selected' : '';
                                            echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="">Repayment Date</label>
                            <input type="date" name="date" class="form-control" required id="">
                        </div>
                        <div class="form-group">
                            <label for="">Select Repayment File</label>
                            <input type="file" name="file" id="" class="form-control" required accept=".csv, .xls, .xlsx">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-sm btn-primary btn-block" name="upload_branch_loan_repayment">Process Branch Repayment Data</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- General Loan Repayment Upload Card (No Branch Selection) -->
        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header"> 
                    <h4 class="card-title">General Loan Repayment Upload</h4> 
                </div>
                <form action="./?page=process_general_loan_repayment_upload" method="post" enctype="multipart/form-data" class="was-validated">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Repayment Date</label>
                            <input type="date" name="date" class="form-control" required id="">
                        </div>
                        <div class="form-group">
                            <label for="">Select Repayment File</label>
                            <input type="file" name="file" id="" class="form-control" required accept=".csv, .xls, .xlsx">
                        </div>
                        
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-sm btn-success btn-block" name="upload_general_loan_repayment">Process General Repayment Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    break;

case"process_loan_repayment_upload":
    $branchId = (int) $_POST['branch_id'];
    $date = (string) $_POST['date'];
    include("./views/loan/upload_loan_repayment.php");
    break;

case"process_general_loan_repayment_upload":
    // No branch_id needed - will be detected from member loan data
    $date = (string) $_POST['date'];
    include("./views/loan/upload_general_loan_repayment.php");
    break;
              
            case"receipt_voucher":
              include("./views/voucher/receipt_voucher.php");
            break;
            case "payment_voucher":
              include("./views/voucher/payment_voucher.php");
            break;
            case"journal_voucher":
              include("./views/voucher/journal_voucher.php");
              break;
            // min vouchers
            case"min_receipt_voucher":
              include("./views/voucher/min_receipt_voucher.php");
              break;
              case"min_journal_voucher":
              include("./views/voucher/min_journal_voucher.php");
              break;
            // end min voucher
            //  opening balance
            case"add_opening_balance":
              include("./views/opening_balance/add_opening_balance.php");
            break;
            case "opening_balance_list":
              include("./views/opening_balance/opening_balance_list.php");
            break;
            // end of opening Balance
            //voucher report
              case"pending_voucher_list":
                include("./views/voucher/pending_voucher_list.php");
              break;
              case"approve_voucher":
                $id = (int) $_GET['voucher_id'];
                $status = $conn->real_escape_string(  $_GET['status']);
                $approveVoucher = approveTransaction($conn,$id,$status);
                if($approveVoucher){
                  echo "<script>alert('SUCCESS'); window.location.href='./?page=pending_voucher_list'</script>";
                }
                break;
              case"transaction_list":
                  include("./views/voucher/transaction_list.php");
                break;
              case"preview_voucher":
                $id = (int) $_GET['voucher_id'];
                  include("./views/voucher/preview_transaction.php");
                break;
            // end voucher report

            // sub report
              case "min_sub_report_form":
                include("./views/min_subsidiary/min_sub_report_form.php");
                break;
              case"min_sub_report":
                $minSubId = (int) $_GET['id'];
                include("./views/min_subsidiary/min_sub_report.php");
              break;
              case"min_sub_report_branch_form":
                include("./views/min_subsidiary/min_sub_report_by_branch_form.php");
                break;
              case"view_min_sub_report_by_branch":
                $branchId = (int) $_POST['branch'];
                $minSubId = (int) $_POST['min_sub'];
                include("./views/min_subsidiary/min_sub_report_by_branch.php");
                break;
                 case"min_sub_ledger_report_form":
                  include("./views/min_subsidiary/min_sub_ledger_report_form.php");
                break;
                case"min_sub_ledger_report":
                  $subId = (int) $_POST['sub_id'];
                  $branchId = (int) $_POST['branch_id'];
                  include("./views/min_subsidiary/min_sub_ledger_report.php");
                break;
            // end of sub report
            // sub report
                case "sub_report_by_branch":
                  ?>
                    <div class=" card-info card ">
                        <div class=" card-header"> <h4 class=" card-title">Subsidiary Report Per Branch</h4> </div>
                        <form action="./?page=view_sub_report_per_branch" class=" was-validated" method="post">
                            <div class=" card-body">
                                <div class=" form-group">
                                    <label for=""> Select Branch </label>
                                    <select name="branch" class="form-control select2-form select2bs4-form" required id="">
                                                                   <?php
                                                                        $branchId = null;
                                                                        if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                                            $branchId = $_SESSION['branchid'];  
                                                                        }
                                                            
                                                                        $branches = selectAllBranches($conn, $branchId);
                                                            
                                                                        if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                                            echo '<option value="">--Select Below--</option>';
                                                                             echo '<option value="0">All Branch</option>';
                                                                            
                                                                        }
                                                            
                                                                        if ($branches && is_array($branches)) {
                                                                            foreach ($branches as $result) {
                                                                                $selected = ($branchId == $result['id']) ? 'selected' : '';
                                                                                echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                                                                            }
                                                                        }
                                                                    ?>
                                                                </select>
                                </div>
                                <div class=" form-group">
                                  <label for=""> Select Subsidiaries</label>
                                    <select name="subId" class=" form-control select2-form select2bs4-form" required id="">
                                              <option value="">Select Below</option>
                                              <?php
                                                    $subs = getAllSubsidiaries($conn);
                                                    if($subs && is_array($subs)){
                                                      foreach($subs as $sub){
                                                        echo "<option value='$sub[id]'>$sub[name]</option>";
                                                      }
                                                    }
                                              ?>
                                    </select>
                                </div>
                            </div>
                            <div class=" card-footer">
                                                    <button type="submit" class=" btn btn-info btn-sm btn-block">View Report</button>
                            </div>
                        </form>
                    </div>
                  <?php
                break;
                case"view_sub_report_per_branch":
                   // print_r($_POST);
                    $subId = (int) $_POST['subId'];
                    $branchId = (int) $_POST['branch'];
                    include("./views/subsidiary/view_sub_report_per_branch.php"); 
                  break;
                    case"approved_loan_list":
                    // include("./views/loan/approved_loan_list.php"); 
                    // break;
            case"view_loan_details":
                include("./views/loan/view_loan_details.php");
                break;

            // ===== NEW FEATURE ROUTES =====

            // Budget Management
            case"create_budget":
                include("./views/budget/create_budget.php");
                break;
            case"all_budgets":
                include("./views/budget/all_budget.php");
                break;
            case"view_budget":
                include("./views/budget/review_budget.php");
                break;
            case"edit_budget":
                include("./views/budget/edit_budget.php");
                break;
            case"review_budget":
                include("./views/budget/review_budget.php");
                break;

            // Grantor Requests
            case"my_grantor_requests":
                include("./views/grantor/my_grantor_requests.php");
                break;

            // Meeting Minutes
            case"create_meeting":
                include("./views/meetings/create_meeting.php");
                break;
            case"meeting_list":
                include("./views/meetings/meeting_list.php");
                break;
            case"view_meeting":
                include("./views/meetings/view_meeting.php");
                break;
            case"edit_meeting":
                include("./views/meetings/edit_meeting.php");
                break;

            // Loan Advisor
            case"loan_adviser":
                include("./views/loan/loan_adviser.php");
                break;

            // Role Management
            case"manage_roles":
                include("./views/roles/manage_roles.php");
                break;
            case"assign_user_roles":
                include("./views/roles/assign_roles.php");
                break;

            // Notifications
            case"notifications":
                include("./views/notifications/notifications.php");
                break;
            case"mark_notifications_read":
                markAllNotificationsRead($conn, $_SESSION['userid']);
                echo "<script>window.location.href='./?page=notifications';</script>";
                break;
            // ===== END NEW FEATURE ROUTES =====

        }

        
      ?>
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <strong> &copy; <?= date("Y") ?> <a href="tellicerp.co.tz">Tellicerp</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <!-- <b>Version</b> 3.2.0 -->
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="./dist/datatable2/jquery-3.7.1.js"></script>
 <script src="./dist/datatable2/datatables.js"></script>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="./plugins/select2/js/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="plugins/jquery-ui/jquery-ui.min.js"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<!-- <script src="plugins/sparklines/sparkline.js"></script> -->
<!-- JQVMap -->
<!-- <script src="plugins/jqvmap/jquery.vmap.min.js"></script> -->
<!-- <script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script> -->
<!-- jQuery Knob Chart -->
<script src="plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>
<script src="./dist/js/html-table-search.js"></script>
<script src="./dist/js/functions.js"></script>
<script scr="./dist/js/graphs_function.js"></script>
<!-- AdminLTE for demo purposes -->
<!-- <script src="dist/js/demo.js"></script> -->
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<!-- <script src="dist/js/pages/dashboard.js"></script> -->




</body>
</html>
<script>

  $(document).ready(function(){
   
    $(function () {
         $(".select2-form").select2({
                       placeholder: "Select an option",
                             });
            $('.select2bs4-form').select2({
      theme: 'bootstrap4'
    })
         });


});
$(document).ready(function(){
    $('table.table-search').tableSearch({
        searchText:'Search here',
        searchPlaceHolder:'Input Value'
    });
});

</script>
