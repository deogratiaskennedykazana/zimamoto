<?php
// Validate and sanitize input
$repaymentMode = strtolower(trim($_GET['repayment_mode'] ?? ''));
$fileName = basename(urldecode($_GET['name'] ?? '')); // Get only filename
$loanId = (int) ($_GET['id'] ?? 0);

// Determine folder based on repayment mode
$baseDir = '';

if ($repaymentMode === 'salary') {
    $baseDir = 'uploads/salary_slips/';
} elseif ($repaymentMode === 'standing_order') {
    $baseDir = 'uploads/standing_orders/';
}

$filePath = $baseDir . $fileName;


?>

<div class=" card card-primary">
    <div class=" card-header">  
        <h4 class=" card-title">Review Attachment - <?php echo ucfirst(str_replace('_', ' ', $repaymentMode)); ?></h4> 
    </div>
    
    <div class=" card-body">
        <?php if($filePath && file_exists($filePath)): ?>
            
            <div class="text-center mb-3">
                <h5>File: <?php echo basename($filePath); ?></h5>
                <a href="<?php echo $filePath; ?>" download class="btn btn-success">
                    <i class="fas fa-download"></i> Download Attachment
                </a>
            </div>
            
            <div class="attachment-preview">
                <?php
                $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                
                if(in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])):
                ?>
                    <!-- Image Preview -->
                    <div class="text-center">
                        <img src="<?php echo $filePath; ?>" alt="Attachment Preview" 
                             style="max-width: 100%; max-height: 600px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                
                <?php elseif($fileExtension == 'pdf'): ?>
                    <!-- PDF Preview -->
                    <div class="text-center">
                        <embed src="<?php echo $filePath; ?>" type="application/pdf" 
                               width="100%" height="600px" 
                               style="border: 1px solid #ddd; border-radius: 5px;">
                        <p class="mt-2 text-muted">
                            <small>If PDF doesn't display, <a href="<?php echo $filePath; ?>" target="_blank">click here to open in new tab</a></small>
                        </p>
                    </div>
                
                <?php else: ?>
                    <!-- Unsupported file type -->
                    <div class="text-center p-4">
                        <i class="fas fa-file fa-5x text-muted mb-3"></i>
                        <h5>File Preview Not Available</h5>
                        <p class="text-muted">This file type cannot be previewed. Please download to view.</p>
                        <a href="<?php echo $filePath; ?>" target="_blank" class="btn btn-primary">
                            <i class="fas fa-external-link-alt"></i> Open File
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
        <?php else: ?>
            <!-- File not found -->
            <div class="text-center p-4">
                <i class="fas fa-exclamation-triangle fa-5x text-warning mb-3"></i>
                <h5>File Not Found</h5>
                <p class="text-muted">The requested attachment could not be found.</p>
                <p class="text-muted"><small>Looking for: <?php echo htmlspecialchars($filePath); ?></small></p>
                <button onclick="history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <div class=" card-footer">
        <button onclick="history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Loan Details
        </button>
        <?php if($filePath && file_exists($filePath)): ?>
            <a href="<?php echo $filePath; ?>" download class="btn btn-success float-right">
                <i class="fas fa-download"></i> Download
            </a>
        <?php endif; ?>
    </div>
</div>