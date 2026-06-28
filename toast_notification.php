<?php

// FIX: this used to only render when type === 'success', so every
// setNotification('error'|'danger'|'warning'|'info', ...) call across the
// whole app (budget, loans, vouchers, etc.) was silently discarded by
// getNotification() — the user got bounced back with zero feedback on
// failures. Now every type renders, each with its own color/icon.
$notification = getNotification();
if ($notification):
    $__type = $notification['type'] ?? 'info';
    $__themes = [
        'success' => ['from' => '#10b981', 'to' => '#059669', 'icon' => 'M5 13l4 4L19 7'],
        'error'   => ['from' => '#ef4444', 'to' => '#dc2626', 'icon' => 'M6 18L18 6M6 6l12 12'],
        'danger'  => ['from' => '#ef4444', 'to' => '#dc2626', 'icon' => 'M6 18L18 6M6 6l12 12'],
        'warning' => ['from' => '#f59e0b', 'to' => '#d97706', 'icon' => 'M12 9v3.75m0 3.75h.008M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'info'    => ['from' => '#3b82f6', 'to' => '#2563eb', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
    $__theme = $__themes[$__type] ?? $__themes['info'];
?>
<div id="toast-notification" style="position: fixed; bottom: 30px; right: 30px; background: linear-gradient(135deg, <?php echo $__theme['from']; ?> 0%, <?php echo $__theme['to']; ?> 100%); color: white; padding: 14px 18px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.25), 0 2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; z-index: 9999; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; font-size: 14px; font-weight: 500; max-width: 380px; min-width: 280px; animation: slideInBounce 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); backdrop-filter: blur(10px);">
    
    <!-- Icon with Circle Background -->
    <div style="width: 36px; height: 36px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="<?php echo $__theme['icon']; ?>"></path>
        </svg>
    </div>
    
    <!-- Message -->
    <span style="flex: 1; line-height: 1.4;"><?php echo htmlspecialchars($notification['message']); ?></span>
    
    <!-- Countdown Circle Timer -->
    <div style="position: relative; width: 32px; height: 32px; flex-shrink: 0;">
        <svg style="width: 32px; height: 32px; transform: rotate(-90deg);" viewBox="0 0 36 36">
            <circle cx="18" cy="18" r="16" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="2.5"></circle>
            <circle id="countdown-circle" cx="18" cy="18" r="16" fill="none" stroke="white" stroke-width="2.5" stroke-dasharray="100" stroke-dashoffset="0" stroke-linecap="round" style="transition: stroke-dashoffset 0.1s linear;"></circle>
        </svg>
        <span id="countdown-number" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px; font-weight: 700;">40</span>
    </div>
    
    <!-- Close Button -->
    <button onclick="closeToast()" style="background: rgba(255,255,255,0.2); border: none; color: white; cursor: pointer; font-size: 18px; line-height: 1; padding: 6px; margin-left: 4px; border-radius: 6px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
</div>

<style>
@keyframes slideInBounce {
    0% {
        transform: translateX(500px) scale(0.8);
        opacity: 0;
    }
    50% {
        transform: translateX(-20px) scale(1.05);
    }
    100% {
        transform: translateX(0) scale(1);
        opacity: 1;
    }
}

@keyframes slideOut {
    0% {
        transform: translateX(0) scale(1);
        opacity: 1;
    }
    100% {
        transform: translateX(500px) scale(0.8);
        opacity: 0;
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}
</style>

<script>
let countdownInterval;
let secondsLeft = 40;
const totalSeconds = 40;
const circumference = 100;

function updateCountdown() {
    secondsLeft--;
    document.getElementById('countdown-number').textContent = secondsLeft;
    
    const progress = (secondsLeft / totalSeconds) * circumference;
    document.getElementById('countdown-circle').style.strokeDashoffset = circumference - progress;
    
    if (secondsLeft <= 0) {
        clearInterval(countdownInterval);
        closeToast();
    }
}

function closeToast() {
    clearInterval(countdownInterval);
    var toast = document.getElementById('toast-notification');
    if (toast) {
        toast.style.animation = 'slideOut 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
        setTimeout(function() {
            toast.remove();
        }, 400);
    }
}

// Start countdown
countdownInterval = setInterval(updateCountdown, 1000);

// Add pulse animation on hover
document.getElementById('toast-notification').addEventListener('mouseenter', function() {
    this.style.animation = 'pulse 0.3s ease-in-out';
});

document.getElementById('toast-notification').addEventListener('mouseleave', function() {
    this.style.animation = 'none';
});
</script>
<?php endif; ?>