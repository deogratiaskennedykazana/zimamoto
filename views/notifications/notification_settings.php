<?php
// Handle form submission
if (isset($_POST['save_notification_settings'])) {
    $settings = [
        'notify_email'  => isset($_POST['notify_email']) ? 1 : 0,
        'notify_sms'    => isset($_POST['notify_sms']) ? 1 : 0,
        'notify_in_app' => 1,
        'email_address' => $conn->real_escape_string($_POST['email_address'] ?? ''),
        'phone_number'  => $conn->real_escape_string($_POST['phone_number'] ?? ''),
    ];
    $updated = updateUserNotificationSettings($conn, (int)$_SESSION['userid'], $settings);
    if ($updated) {
        setNotification('success', 'Notification settings saved.');
    } else {
        setNotification('danger', 'Failed to save settings.');
    }
    echo "<script>window.location.href='./?page=notification_settings';</script>";
    exit;
}

$prefs = getUserNotificationSettings($conn, (int)$_SESSION['userid']);
?>

<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title"><i class="fas fa-bell mr-2"></i>Notification Settings</h4>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-outline card-info">
                        <div class="card-header"><h5 class="card-title">Notification Channels</h5></div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="notify_in_app" checked disabled>
                                    <label class="custom-control-label" for="notify_in_app">
                                        <strong>In-App Notifications</strong>
                                        <small class="text-muted d-block">Always on — shows in the bell icon</small>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="notify_email" name="notify_email" value="1"
                                        <?= ($prefs && $prefs['notify_email']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="notify_email">
                                        <strong>Email Notifications</strong>
                                        <small class="text-muted d-block">Receive alerts via email</small>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="notify_sms" name="notify_sms" value="1"
                                        <?= ($prefs && $prefs['notify_sms']) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="notify_sms">
                                        <strong>SMS Notifications</strong>
                                        <small class="text-muted d-block">Receive alerts via SMS</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-outline card-success">
                        <div class="card-header"><h5 class="card-title">Contact Details</h5></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Email Address for Notifications</label>
                                <input type="email" name="email_address" class="form-control"
                                    value="<?= htmlspecialchars($prefs['email_address'] ?? $_SESSION['email'] ?? '') ?>"
                                    placeholder="your@email.com">
                            </div>
                            <div class="form-group">
                                <label>Phone Number for SMS</label>
                                <input type="text" name="phone_number" class="form-control"
                                    value="<?= htmlspecialchars($prefs['phone_number'] ?? '') ?>"
                                    placeholder="07XXXXXXXX or 255XXXXXXXXX">
                                <small class="text-muted">Tanzanian format: 07XXXXXXXX or 255XXXXXXXXX</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-warning">
                <div class="card-header"><h5 class="card-title">What triggers notifications?</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success mr-1"></i> Loan application status changes</li>
                                <li><i class="fas fa-check-circle text-success mr-1"></i> Guarantor (grantor) requests</li>
                                <li><i class="fas fa-check-circle text-success mr-1"></i> Guarantor responses</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success mr-1"></i> Meeting minutes published</li>
                                <li><i class="fas fa-check-circle text-success mr-1"></i> Budget approvals/rejections</li>
                                <li><i class="fas fa-check-circle text-success mr-1"></i> System announcements</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success mr-1"></i> Voucher approvals</li>
                                <li><i class="fas fa-check-circle text-success mr-1"></i> Account status updates</li>
                                <li><i class="fas fa-check-circle text-success mr-1"></i> Admin broadcasts</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="save_notification_settings" class="btn btn-primary btn-block">
                <i class="fas fa-save mr-1"></i> Save Settings
            </button>
        </form>
    </div>
</div>
