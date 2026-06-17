<?php
    $meeting_id = (int) $_GET['id'];
    $meeting = selectMeetingMinutesById($conn, $meeting_id);
    $attendees = selectAttendeesByMinutesId($conn, $meeting_id);
    if(!$meeting):
        echo "<script>alert('Meeting not found'); window.location.href='./?page=meeting_list';</script>";
        exit;
    endif;
?>
<div class="card">
    <div class="card-header">
        <h4 class="card-title"><?= $meeting['title'] ?></h4>
        <div class="card-tools">
            <span class="badge badge-<?= $meeting['status'] === 'published' ? 'success' : 'warning' ?>"><?= ucfirst($meeting['status']) ?></span>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><strong>Date:</strong> <?= date('d F Y', strtotime($meeting['meeting_date'])) ?></div>
            <div class="col-md-3"><strong>Type:</strong> <?= $meeting['meeting_type'] ?></div>
            <div class="col-md-3"><strong>Venue:</strong> <?= $meeting['venue'] ?></div>
            <div class="col-md-3"><strong>Chairperson:</strong> <?= $meeting['chairperson'] ?></div>
        </div>
        <hr>
        <div class="meeting-content p-3 border rounded">
            <?= $meeting['content'] ?>
        </div>
        <?php if($attendees && is_array($attendees) && count($attendees) > 0): ?>
        <hr>
        <h5>Attendees</h5>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Name</th><th>Role</th><th>Present</th></tr></thead>
            <tbody>
                <?php foreach($attendees as $a): ?>
                <tr>
                    <td><?= $a['name'] ?></td>
                    <td><?= $a['role'] ?></td>
                    <td><?= $a['present'] ? '<span class="text-success"><i class="fas fa-check"></i> Yes</span>' : '<span class="text-danger"><i class="fas fa-times"></i> No</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php if($meeting['status'] === 'draft'): ?>
        <hr>
        <form action="./controllers/meeting_controller.php" method="post" onsubmit="return confirm('Publish these minutes?')">
            <input type="hidden" name="meeting_id" value="<?= $meeting['id'] ?>">
            <button type="submit" name="publish_meeting" class="btn btn-success"><i class="fas fa-check"></i> Publish Minutes</button>
        </form>
        <?php endif; ?>
    </div>
</div>
