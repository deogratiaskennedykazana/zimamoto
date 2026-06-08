<?php
// Get URL parameters
$branch_id = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
$loan_id = isset($_GET['loan_id']) ? (int) $_GET['loan_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

// Fetch data using existing functions
$userDetails = selectUserById($conn, $user_id);
$memberDetails = selectMemberByUserId($conn, $user_id);
$loanDetails = selectLoanById($conn, $loan_id);
$grantors = selectLoanGrantorByLoanId($conn, $loan_id);
?>

<div class="card card-info">
    <div class="card-header">
        <h4>Loan Form</h4>
    </div>
    <div class="card-body">
         
            
            <style>
                .zisa-loan-form-container {
                    font-family: 'Times New Roman', serif;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    line-height: 1.4;
                    font-size: 14px;
                }
                
                .zisa-form-header-box {
                    border: 2px solid black;
                    padding: 10px;
                    text-align: center;
                    margin-bottom: 20px;
                }
                
                .zisa-photo-placeholder {
                    width: 120px;
                    height: 120px;
                    border: 1px solid black;
                    margin: 10px auto;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background-color: #f0f0f0;
                }
                
                .zisa-organization-header {
                    text-align: center;
                    margin: 20px 0;
                    font-weight: bold;
                }
                
                .zisa-section-title {
                    font-weight: bold;
                    margin: 20px 0 10px 0;
                }
                
                .zisa-form-row {
                    margin: 8px 0;
                }
                
                .zisa-checkbox-row {
                    margin: 10px 0;
                }
                
                .zisa-loan-form-container input[type="text"], 
                .zisa-loan-form-container input[type="number"], 
                .zisa-loan-form-container input[type="date"], 
                .zisa-loan-form-container input[type="tel"], 
                .zisa-loan-form-container input[type="email"] {
                    border: none;
                    border-bottom: 1px solid black;
                    padding: 2px;
                    margin: 0 5px;
                    font-family: 'Times New Roman', serif;
                    background: transparent;
                }
                
                .zisa-loan-form-container input[type="checkbox"] {
                    margin: 0 5px;
                }
                
                .zisa-signature-section {
                    border: 1px solid black;
                    padding: 10px;
                    margin: 20px 0;
                }
                
                .zisa-signature-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin: 20px 0;
                }
                
                .zisa-guarantee-text {
                    margin: 15px 0;
                    text-align: justify;
                }
                
                .zisa-office-use {
                    /*border: 2px solid black;*/
                    padding: 15px;
                    margin: 20px 0;
                }
                
                .zisa-fees-info {
                    background-color: #f9f9f9;
                    padding: 10px;
                    border: 1px solid #ccc;
                    margin: 15px 0;
                }
                
                .zisa-warning-text {
                    font-weight: bold;
                    text-align: center;
                    margin: 10px 0;
                }
                
                .zisa-guarantor-section {
                    margin: 20px 0;
                }
                
                .zisa-guarantor-details {
                    margin: 10px 0;
                }
                
                .zisa-guarantor-signature {
                    margin: 15px 0;
                }
                
                .zisa-guarantor-accounts {
                    margin: 10px 0; 
                    font-size: 12px;
                }
                
                .zisa-accounts-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                
                .zisa-accounts-table td {
                    border: 1px solid #ccc;
                    padding: 5px;
                    font-size: 12px;
                }
                
                .zisa-authorization-section {
                    margin: 15px 0;
                }
                
                .zisa-committee-section {
                    margin: 20px 0;
                }
                
                .zisa-committee-member {
                    margin: 15px 0;
                }
                
                .zisa-note-section {
                    margin: 20px 0; 
                    padding: 10px; 
                    background-color: #f0f0f0;
                }
            </style>
            
            <div class="zisa-loan-form-container">
                <!-- START: Organization Header -->
                <div class="zisa-organization-header">
                    <strong>CHAMA CHA USHIRIKA WA AKIBA NA MIKOPO CHA ZIMAMOTO</strong><br>
                    <strong>ZIMAMOTO SAVING AND CREDIT COOPERATIVE SOCIETY LTD</strong><br>
                    <strong>S.L.P 1509, Dodoma</strong><br>
                    <strong>Simu: +255 739 800 094, +255 739 800 09497.</strong><br>
                    <strong>Barua pepe: <u>saccos@zimamoto.go.tz</u></strong><br><br>
                    <strong>Reg No. NA. PRI-DOM-DOM-CC-2022-136</strong><br><br>
                    <strong>FOMU YA MAOMBI YA MKOPO ZISA</strong>
                </div>
                <!-- END: Organization Header -->

                <!-- START: Section 1 - Personal Information -->
                <div class="zisa-section-title">1. MAELEZO BINAFSI:</div>
                
                <div class="zisa-form-row">
                    Jina Kamili <input type="text" style="width: 200px;" value="<?= isset($userDetails['name']) ? htmlspecialchars($userDetails['name']) : '' ?>"> 
                    Cheki Namba <input type="text" style="width: 100px;" value="<?= isset($memberDetails['check_no']) ? htmlspecialchars($memberDetails['check_no']) : '' ?>"> 
                    Cheo <input type="text" style="width: 100px;"> 
                    Tarehe ya kuzaliwa <input type="date" style="width: 120px;">
                </div>
                
                <div class="zisa-form-row">
                    Mwaka wa kujiunga na Chama <input type="text" style="width: 100px;"> 
                    Namba ya Uanachama ZISA/18/<input type="text" style="width: 80px;" value="<?= isset($memberDetails['reg_no']) ? htmlspecialchars($memberDetails['reg_no']) : '' ?>">
                </div>
                
                <div class="zisa-form-row">
                    Tarehe ya kuajiriwa <input type="date" style="width: 120px;"> 
                    Tarehe ya kustaafu <input type="date" style="width: 120px;">
                </div>
                
                <div class="zisa-form-row">
                    Hali ya ndoa <input type="text" style="width: 150px;"> 
                    Idadi ya wategemezi <input type="number" style="width: 80px;">
                </div>
                
                <div class="zisa-form-row">
                    Mshahara wa sasa <input type="text" style="width: 150px;">
                </div>
                
                <div class="zisa-form-row">
                    Mkoa <input type="text" style="width: 150px;" value="<?= isset($userDetails['branch_name']) ? htmlspecialchars($userDetails['branch_name']) : '' ?>"> 
                    Kituo <input type="text" style="width: 150px;"> 
                    Simu No. <input type="tel" style="width: 120px;" value="<?= isset($memberDetails['phone']) ? htmlspecialchars($memberDetails['phone']) : '' ?>">
                </div>
                <!-- END: Section 1 - Personal Information -->

                <!-- START: Section 2 - Loan Application -->
                <div class="zisa-section-title">2. MAOMBI YA MKOPO:</div>
                <div>Tiki kibox kulingana na aina ya mkopo unao uomba</div>
                
                <div class="zisa-checkbox-row">
                    <input type="checkbox"> Mkopo wa Maendeleo
                    <input type="checkbox"> Dharura
                    <input type="checkbox"> Elimu
                    <input type="checkbox"> Likizo
                    <input type="checkbox"> Sikukuu
                    <input type="checkbox"> Chap-chap
                    <input type="checkbox"> Maalum
                </div>
                
                <div class="zisa-form-row">
                    Ninaomba Mkopo wa Tshs <input type="text" style="width: 200px;" value="<?= isset($loanDetails['principle']) ? number_format($loanDetails['principle'], 2) : '' ?>">
                </div>
                
                <div class="zisa-form-row">
                    Nitakaoulipa kwa muda wa miezi <input type="number" style="width: 80px;" value="<?= isset($loanDetails['period']) ? $loanDetails['period'] : '' ?>">
                </div>
                
                <div class="zisa-form-row">
                    Ambazo nitarejesha Tshs <input type="text" style="width: 150px;"> Kwa kila mwezi kupitia, 
                    <input type="checkbox"> Mshahara, <input type="checkbox"> Makato ya Bank
                </div>
                
                <div class="zisa-form-row">
                    Akaunti ya Benki ya Mwanachama <input type="text" style="width: 250px;">
                </div>
                
                <div class="zisa-form-row">
                    Jina la Bank <input type="text" style="width: 200px;">
                </div>
                
                <div class="zisa-form-row">
                    Jina la Akaunti <input type="text" style="width: 300px;">
                </div>
                <!-- END: Section 2 - Loan Application -->

                <!-- START: Section 3 - Loan Guarantee -->
                <div class="zisa-section-title">3. DHAMANA YA MKOPO:</div>
                
                <div class="zisa-guarantee-text">
                    Dhamana ya mkopo huu ni <strong>"AKIBA ZANGU"</strong>. Ninathibitisha kuwa maelezo niliyotoa hapo juu ni ya kweli. 
                    Hivyo basi kuulipa mkopo huu pamoja na riba zake hakuwezi kuniletea matatizo ya kifedha na kushindwa kufanya 
                    marejesho kwa Wakati. Ninakubaliana na sheria zote zinazohusu Mkopo na Makato yake ndani ya Chama.
                </div>
                
                <div class="zisa-guarantee-text">
                    <strong>'Iwapo kutajitokeza tatizo lolote nje ya yale yalioyo bainishwa kwenye marsharti ya chama na sera ya mikopo 
                    na kupelekea kushindwa kurejesha Mkopo huu. Naelekeza marejesho ya mkopo huu yafanyike kupitia Akiba,Hisa na 
                    stahiki zangu za jeshi ili kulipa deni langu.'</strong>
                </div>
                
                <div class="zisa-signature-row">
                    <div>Saini ya Mwombaji <input type="text" style="width: 150px;"></div>
                    <div>Tarehe <input type="date" style="width: 120px;"></div>
                </div>
                
                <div class="zisa-note-section">
                    <strong>NB:</strong> Ambatananisha Fomu hii pamoja na Picha(Kiraia), Salary slip (3), Bank Statement ya miezi 3 
                    (yenye Muhuri wa bank) ya hivi karibuni <em>(Makato yasiyo ya Mshahara)</em>, fomu ya Uaminifu 
                    <em>(Makato yasiyo ya Mshahara)</em>, Standing Order <em>(Makato yasiyo ya Mshahara)</em>. 
                    <strong>Fomu zote za maombi ya Mkopo na viambata vyake zitumwe kwa barua pepe hii saccos@zimamoto.go.tz</strong>
                </div>
                <!-- END: Section 3 - Loan Guarantee -->

                <!-- START: Section 4 - Loan Fees -->
                <div class="zisa-section-title">4. GHARAMA ZA MKOPO:</div>
                
                <div class="zisa-fees-info">
                    <strong>Fomu ya mkopo ni 5,000/=, Ada ya mkopo ni 1% ya Mkopo na Ada ya Majanga ni 1% ya Mkopo. 
                    Akauti za chama za kulipia ada ya mkopo na marejesho ni 0133427255701 Zimamoto Saccos Ltd (CRDB)/ 
                    51710055244 Zimamoto Saccos Ltd (NMB)</strong>
                </div>
                
                <div class="zisa-warning-text">
                    IKUMBUKWE: "20% ITATOZWA KAMA ADAHBU KWA KILA REJESHO LA MWEZI LITAKALO CHELEWESHWA KUREJESHWA CHAMANI".
                </div>
                <!-- END: Section 4 - Loan Fees -->

                <!-- START: Section 5 - Guarantors -->
                <div class="zisa-section-title">5. WADHAMINI:</div>
                
                <?php
                if($grantors && is_array($grantors)){
                    $grantorIndex = 0;
                    foreach($grantors as $grantor){
                        $grantorIndex++;
                        $label = $grantorIndex == 1 ? 'a.' : 'b.';
                        $text = $grantorIndex == 1 ? 'Mdhamini wa Kwanza' : 'Mdhamini wa pili';
                        ?>
                        <div class="zisa-guarantor-section">
                            <strong><?= $label ?></strong> Jina la <?= $text ?> <input type="text" style="width: 200px;" value="<?= htmlspecialchars($grantor['name']) ?>"> 
                            Checki Namba <input type="text" style="width: 100px;"> namba ya Mwanachama <input type="text" style="width: 100px;">
                            
                            <div class="zisa-guarantor-details">
                                Ninamdhamini Mkopaji kiasi cha Tshs <input type="text" style="width: 200px;"> hivyo nitawajibika kuulipa 
                                mkopo huu kupitia mshahara wangu au Posho yangu ya chakula endapo mkopaji atashindwa au atakataa kuulipa 
                                mkopo huu. Dhamana yangu ni <strong>'Akiba zangu'</strong>.
                            </div>
                            
                            <div class="zisa-guarantor-signature">
                                Saini <input type="text" style="width: 150px;"> Tarehe <input type="date" style="width: 120px;">
                            </div>
                            
                            <!-- Guarantor Account Details -->
                            <?php
                            $accounts1 = selectMinSubsByUserId($conn, (int) $grantor['grantor_id']);
                            if($accounts1 && is_array($accounts1)){
                                echo "<div class='zisa-guarantor-accounts'>";
                                echo "<strong>Akiba za Mdhamini:</strong>";
                                echo "<table class='zisa-accounts-table'>";
                                echo "<tr><td>#</td><td>Account</td><td>Amount</td></tr>";
                                $counter = 1;
                                foreach($accounts1 as $account1){
                                    $accountBalance = 0;
                                    $minTransactions = getMinTransactionByMinSubId($conn, $account1['id']);
                                    if($minTransactions && is_array($minTransactions)){
                                        foreach($minTransactions as $minTransaction){
                                            if($minTransaction['dr_account'] == $account1['id']){
                                                $accountBalance += $minTransaction['amount'];
                                            }elseif($minTransaction['cr_account'] == $account1['id']){
                                                $accountBalance -= $minTransaction['amount'];
                                            }
                                        }
                                    }
                                    echo "<tr>";
                                    echo "<td>$counter</td>";
                                    echo "<td>$account1[name]</td>";
                                    echo "<td>". number_format($accountBalance, 2) ."</td>";
                                    echo "</tr>";
                                    $counter++;
                                }
                                echo "</table>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                        <?php
                        if($grantorIndex >= 2) break; // Only show first 2 guarantors
                    }
                } else {
                    // Show empty guarantor sections if no data
                    ?>
                    <div class="zisa-guarantor-section">
                        <strong>a.</strong> Jina la Mdhamini wa Kwanza <input type="text" style="width: 200px;"> 
                        Checki Namba <input type="text" style="width: 100px;"> namba ya Mwanachama <input type="text" style="width: 100px;">
                        
                        <div class="zisa-guarantor-details">
                            Ninamdhamini Mkopaji kiasi cha Tshs <input type="text" style="width: 200px;"> hivyo nitawajibika kuulipa 
                            mkopo huu kupitia mshahara wangu au Posho yangu ya chakula endapo mkopaji atashindwa au atakataa kuulipa 
                            mkopo huu. Dhamana yangu ni <strong>'Akiba zangu'</strong>.
                        </div>
                        
                        <div class="zisa-guarantor-signature">
                            Saini <input type="text" style="width: 150px;"> Tarehe <input type="date" style="width: 120px;">
                        </div>
                    </div>
                    
                    <div class="zisa-guarantor-section">
                        <strong>b.</strong> Jina la Mdhamini wa pili <input type="text" style="width: 200px;"> 
                        Checki Namba <input type="text" style="width: 100px;"> namba ya Mwanachama <input type="text" style="width: 100px;">
                        
                        <div class="zisa-guarantor-details">
                            Ninamdhamini Mkopaji kiasi cha Tshs <input type="text" style="width: 200px;"> hivyo nitawajibika kuulipa 
                            mkopo huu kupitia mshahara wangu au Posho yangu ya chakula endapo mkopaji atashindwa au atakataa kuulipa 
                            mkopo huu. Dhamana yangu ni <strong>'Akiba zangu'</strong>.
                        </div>
                        
                        <div class="zisa-guarantor-signature">
                            Saini <input type="text" style="width: 150px;"> Tarehe <input type="date" style="width: 120px;">
                        </div>
                    </div>
                    <?php
                }
                ?>
                <!-- END: Section 5 - Guarantors -->

                <!-- START: Branch Leader Authorization -->
                <div class="zisa-office-use">
                    <div class="zisa-section-title">IDHINI KUTOKA KWA KIONGOZI WA TAWI.</div>
                    
                    <div class="zisa-authorization-section">
                        Mimi <input type="text" style="width: 200px;"> Nikiwa Kiongozi wa Tawi la <strong>ZISA LTD</strong> 
                        Mkoa wa <input type="text" style="width: 150px;" value="<?= isset($userDetails['branch_name']) ? htmlspecialchars($userDetails['branch_name']) : '' ?>"> nina <strong>idhinisha</strong>/<strong>siidhinishi</strong> 
                        Mwanachama husika kupata Mkopo wa Tshs <input type="text" style="width: 150px;" value="<?= isset($loanDetails['principle']) ? number_format($loanDetails['principle'], 2) : '' ?>"> ambao ataurejesha kwa 
                        muda wa miezi <input type="number" style="width: 80px;" value="<?= isset($loanDetails['period']) ? $loanDetails['period'] : '' ?>"> ndani ya Chama kama alivyoomba kwenye maombi yake ya Mkopo.
                    </div>
                    
                    <div class="zisa-authorization-section">
                        <strong>Sababu ya kutoidhinisha Mkopo wa mwanachama husika</strong>
                        <input type="text" style="width: 300px;">
                    </div>
                    
                    <div class="zisa-authorization-section">
                        <strong>Jina:</strong> <input type="text" style="width: 150px;"> 
                        <strong>Wadhifa wa kiongozi:</strong> <input type="text" style="width: 120px;"> 
                        <strong>Sahihi:</strong> <input type="text" style="width: 120px;"> 
                        <strong>Tarehe:</strong> <input type="date" style="width: 120px;">
                    </div>
                </div>
                <!-- END: Branch Leader Authorization -->

                <!-- START: Office Use Only Section -->
                <div class="zisa-office-use">
                    <div class="zisa-section-title">KWA MATUMIZI YA OFISI TU!!</div>
                    
                    <div class="zisa-committee-section">
                        <strong>A. AFISA MIKOPO</strong>
                        
                        <div class="zisa-authorization-section">
                            Ninathibitisha kua mwanachama <input type="text" style="width: 200px;" value="<?= isset($userDetails['name']) ? htmlspecialchars($userDetails['name']) : '' ?>">
                        </div>
                        
                        <div class="zisa-authorization-section">
                            Ana Akiba ya Tshs <input type="text" style="width: 150px;"> na ana sifa ya kupata Mkopo wa Tsh <input type="text" style="width: 150px;" value="<?= isset($loanDetails['principle']) ? number_format($loanDetails['principle'], 2) : '' ?>">
                        </div>
                        
                        <div class="zisa-authorization-section">
                            Muda wa Marejesho kwa Mkopo Mzima ni miezi <input type="number" style="width: 80px;" value="<?= isset($loanDetails['period']) ? $loanDetails['period'] : '' ?>"> 
                            Riba Tarajiwa ni Tshs <input type="text" style="width: 150px;">
                        </div>
                        
                        <div class="zisa-authorization-section">
                            Rejesho kila mwezi ni Tshs <input type="text" style="width: 150px;">
                        </div>
                        
                        <div class="zisa-authorization-section">
                            <strong>Jina:</strong> <input type="text" style="width: 200px;"> 
                            <strong>Sahihi:</strong> <input type="text" style="width: 150px;">
                        </div>
                    </div>
                    
                    <div class="zisa-committee-section">
                        <strong>(WALIOPITISHA MKOPO HUU)</strong>
                        
                        <div class="zisa-authorization-section">
                            <strong>B. KAMATI YA MIKOPO</strong>
                            
                            <div class="zisa-authorization-section">
                                Katika kikao cha Tarehe <input type="date" style="width: 120px;"> Kamati ya Mikopo imeidhinisha mkopo wa
                            </div>
                            
                            <div class="zisa-authorization-section">
                                Tshs <input type="text" style="width: 150px;" value="<?= isset($loanDetails['principle']) ? number_format($loanDetails['principle'], 2) : '' ?>"> Utakaolipwa kwa kipindi cha miezi <input type="number" style="width: 80px;" value="<?= isset($loanDetails['period']) ? $loanDetails['period'] : '' ?>"> 
                                na Riba ya Tsh <input type="text" style="width: 150px;">
                            </div>
                            
                            <div class="zisa-committee-member">
                                1. Jina <input type="text" style="width: 150px;"> Saini <input type="text" style="width: 120px;"> 
                                M/kiti Tarehe <input type="date" style="width: 120px;">
                            </div>
                            
                            <div class="zisa-committee-member">
                                2. Jina <input type="text" style="width: 150px;"> Saini <input type="text" style="width: 120px;"> 
                                Katibu Tarehe <input type="date" style="width: 120px;">
                            </div>
                            
                            <div class="zisa-committee-member">
                                3. Jina <input type="text" style="width: 150px;"> Saini <input type="text" style="width: 120px;"> 
                                Mjumbe Tarehe <input type="date" style="width: 120px;">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END: Office Use Only Section -->
                
                
              
            </div>
         

  <!-- START: Download Button -->
<div class="text-right mt-3">
    <button class="btn btn-info" onclick="downloadLoanFormPDF()">
        Download PDF
    </button>
    <button class="btn btn-warning" onclick="printLoanFormPDF()">
        Print
    </button>
</div>
<!-- END: Download Button -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
function downloadLoanFormPDF() {
    var contentArea = document.querySelector('.card-body');
    var opt = {
        margin: 0.5,
        filename: 'zisa_loan_form.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(contentArea).save();
}

function printLoanFormPDF() {
    var contentArea = document.querySelector('.card-body');
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Loan Form</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
            </style>
        </head>
        <body>
            ${contentArea.innerHTML}
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() {
                        window.close();
                    };
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>
