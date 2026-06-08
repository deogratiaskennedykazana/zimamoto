<div class="card card-info">
    <div class="card-header"> 
        <h5 class="card-title"> <?= $_SESSION['username']?> Contribution summary</h5> 
    </div>

    <div class="card-body">
        <!-- My Contributions Section -->
        <div class="row">
            <div class="col-12">
                <h4>My Contributions</h4>
            </div>
        </div>
        
        <div class="row m-1">
            <!-- Shares -->
            <div class="col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="far fa-bookmark"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Shares</span>
                        <span class="info-box-number">
                            <?php
                            $shareBalance = 0;
                            $shareMinSUb = null;
                            $shareAccount = selectMinSubByUserIDAndCategory($conn,$_SESSION['userid'], "share");
                            if($shareAccount && is_array($shareAccount)){
                                $shareMinSUb = $shareAccount['id'];
                                $transactions = getMinTransactionByMinSubId($conn, $shareMinSUb);
                                if($transactions && is_array($transactions)){
                                    foreach($transactions as $transaction){
                                        if($transaction['dr_account'] == $shareMinSUb){
                                            $shareBalance += $transaction['amount'];
                                        } elseif($transaction['cr_account'] == $shareMinSUb){
                                            $shareBalance -= $transaction['amount'];
                                        }
                                    }
                                }
                            }
                            echo number_format( $shareBalance*-1,2);
                            ?>
                        </span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">
                            Share balance as of <?= date("F-Y") ?>
                        </span>
                        <a href="./?page=min_sub_report&id=<?=$shareMinSUb?>" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Savings -->
            <div class="col-md-3">
                <div class="info-box bg-secondary">
                    <span class="info-box-icon"><i class="far fa-bookmark"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Savings</span>
                        <span class="info-box-number">
                            <?php
                            $shareBalance = 0;
                            $shareMinSUb = null;
                            $shareAccount = selectMinSubByUserIDAndCategory($conn,$_SESSION['userid'], "saving");
                            if($shareAccount && is_array($shareAccount)){
                                $shareMinSUb = $shareAccount['id'];
                                $transactions = getMinTransactionByMinSubId($conn, $shareMinSUb);
                                if($transactions && is_array($transactions)){
                                    foreach($transactions as $transaction){
                                        if($transaction['dr_account'] == $shareMinSUb){
                                            $shareBalance += $transaction['amount'];
                                        } elseif($transaction['cr_account'] == $shareMinSUb){
                                            $shareBalance -= $transaction['amount'];
                                        }
                                    }
                                }
                            }
                            echo number_format( $shareBalance*-1,2);
                            ?>
                        </span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">
                            Saving balance as of <?= date("F-Y") ?>
                        </span>
                        <a href="./?page=min_sub_report&id=<?=$shareMinSUb?>" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Loan -->
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="far fa-bookmark"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Loan</span>
                        <span class="info-box-number">
                            <?php
                            $shareBalance = 0;
                            $shareMinSUb = null;
                            $shareAccount = selectMinSubByUserIDAndCategory($conn,$_SESSION['userid'], "loan");
                            if($shareAccount && is_array($shareAccount)){
                                $shareMinSUb = $shareAccount['id'];
                                $transactions = getMinTransactionByMinSubId($conn, $shareMinSUb);
                                if($transactions && is_array($transactions)){
                                    foreach($transactions as $transaction){
                                        if($transaction['dr_account'] == $shareMinSUb){
                                            $shareBalance += $transaction['amount'];
                                        } elseif($transaction['cr_account'] == $shareMinSUb){
                                            $shareBalance -= $transaction['amount'];
                                        }
                                    }
                                }
                            }
                            echo number_format( $shareBalance*-1,2);
                            ?>
                        </span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">
                            Loan balance as of <?= date("F-Y") ?>
                        </span>
                        <a href="./?page=min_sub_report&id=<?=$shareMinSUb?>" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Pending Loan Repayment -->
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="far fa-bookmark"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Loan Repayment</span>
                        <span class="info-box-number">.</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">
                            Repayment waiting as of <?= date("F-Y") ?>
                        </span>
                        <a href="./?page=min_sub_report&id=<?=$shareMinSUb?>" class="btn btn-primary btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- End My Contributions Row -->

        <!-- System Summaries Section -->
        <div class="row <?= (($_SESSION['role'] ==='member') &&  ($_SESSION['userlevel'] ==='branch' )) ? ' d-none' :"" ?>">
            <div class="col-12">
                <h4>System Summaries</h4>
            </div>
        </div>
        
        <div class="row m-1  <?= (($_SESSION['role'] ==='member') &&  ($_SESSION['userlevel'] ==='branch' )) ? ' d-none' :"" ?>">
            <!-- Today's Loan Disbursements -->
            <div class="col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Today's Loan Disbursements</span>
                        <span class="info-box-number">TZS 0</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">As of <?= date('d-M-Y') ?></span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Today's Collections -->
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Today's Collections</span>
                        <span class="info-box-number">TZS 0</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">From all branches</span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Expected Collections -->
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Expected Collections</span>
                        <span class="info-box-number">TZS 0</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">Planned for <?= date('d-M-Y') ?></span>
                        <a href="#" class="btn btn-info btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Active Loan Accounts -->
            <div class="col-md-3">
                <div class="info-box bg-secondary">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Active Loan Accounts</span>
                        <span class="info-box-number"></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">Across all branches</span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Loans in Arrears -->
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Loans in Arrears</span>
                        <span class="info-box-number"></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">As of <?= date('d-M-Y') ?></span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Pending Loan Requests -->
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Loan Requests</span>
                        <span class="info-box-number"></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">Awaiting approval</span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Approved Loans -->
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-thumbs-up"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Approved Loans (This Week)</span>
                        <span class="info-box-number"></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description"></span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- This Week's Disbursement -->
            <div class="col-md-3">
                <div class="info-box bg-primary">
                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">This Week's Disbursement</span>
                        <span class="info-box-number">0</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">Monday – Today</span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Outstanding Loan Portfolio -->
            <div class="col-md-3">
                <div class="info-box bg-dark">
                    <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Outstanding Loan Portfolio</span>
                        <span class="info-box-number">0</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">Across all accounts</span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>

            <!-- Recovery Rate -->
            <div class="col-md-3">
                <div class="info-box bg-teal">
                    <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Recovery Rate (Month)</span>
                        <span class="info-box-number"></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">Target </span>
                        <a href="#" class="btn btn-warning btn-sm btn-block text-light"> View More </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- End System Summaries Row -->
    </div>
</div>



<div class="card card-outline card-primary">
    <div class="card-header">
        <h5>My Loan Category</h5>
        <div class="card-footer">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped">
                    <thead>
                        <tr class="table-primary">
                            <td>#</td>
                            <td>Loan Category</td>
                            <td>Principle</td>
                            <td>Interest</td>
                            <td>Interest rate</td>
                            <td>Period</td>
                            <td>Provided Date</td>
                            <td>Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $loans = selectLoansByStatusAndUserId($conn, "approved", $_SESSION['userid']);
                        if($loans && is_array($loans)){
                            $count = 1;
                            foreach($loans as $loan){
                                echo "<tr>";
                                echo "<td>$count</td>";
                                echo "<td>$loan[product]</td>";
                                echo "<td>". number_format( $loan['principle'],2) ."</td>";
                                echo "<td>". number_format( $loan['interest_amount'],2) ."</td>";
                                echo "<td>". number_format( $loan['interest_rate'],2) ."</td>";
                                echo "<td>". number_format( $loan['period'],2) ."</td>";
                                echo "<td>$loan[approve_date]</td>";
                                echo "<td>";
                                echo "<a class='btn btn-sm btn-outline-primary' href='./?page=view_loan_details&loan_id=$loan[id]&user_id=$loan[user_id]'> Preview </a>";
                                echo "</td>";
                                echo "</tr>";
                                $count++;
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>