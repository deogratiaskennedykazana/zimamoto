<?php
    $meeting_id = (int) $_GET['id'];
    $meeting = selectMeetingMinutesById($conn, $meeting_id);
    $attendees = selectAttendeesByMinutesId($conn, $meeting_id);
    if(!$meeting || $meeting['status'] !== 'draft'):
        echo "<script>alert('Meeting not found or cannot be edited'); window.location.href='./?page=meeting_list';</script>";
        exit;
    endif;
?>
<div class="card card-warning">
    <div class="card-header"><h4 class="card-title">Edit Meeting Minutes</h4></div>
    <form action="./controllers/meeting_controller.php" method="post" class="was-validated">
        <input type="hidden" name="meeting_id" value="<?= $meeting['id'] ?>">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Meeting Title</label>
                        <input type="text" name="title" class="form-control" value="<?= $meeting['title'] ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Meeting Date</label>
                        <input type="date" name="meeting_date" class="form-control" value="<?= $meeting['meeting_date'] ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Meeting Type</label>
                        <select name="meeting_type" class="form-control">
                            <option value="General" <?= $meeting['meeting_type'] === 'General' ? 'selected' : '' ?>>General</option>
                            <option value="Board" <?= $meeting['meeting_type'] === 'Board' ? 'selected' : '' ?>>Board</option>
                            <option value="Committee" <?= $meeting['meeting_type'] === 'Committee' ? 'selected' : '' ?>>Committee</option>
                            <option value="Staff" <?= $meeting['meeting_type'] === 'Staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="Special" <?= $meeting['meeting_type'] === 'Special' ? 'selected' : '' ?>>Special</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Venue</label>
                        <input type="text" name="venue" class="form-control" value="<?= $meeting['venue'] ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Chairperson</label>
                        <input type="text" name="chairperson" class="form-control" value="<?= $meeting['chairperson'] ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="draft" <?= $meeting['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $meeting['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
            <hr>
            <h5>Minutes Content</h5>
            <div class="form-group">
                <textarea name="content" id="summernote" class="form-control" rows="15"><?= $meeting['content'] ?></textarea>
            </div>
            <hr>
            <h5>Attendees</h5>
            <div class="table-responsive">
                <table class="table table-bordered" id="attendeeTable">
                    <thead>
                        <tr>
                            <th>Name</th><th>Role/Title</th><th>Present</th>
                            <th><button type="button" class="btn btn-success btn-sm" onclick="addAttendeeRow()"><i class="fas fa-plus"></i></button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($attendees && is_array($attendees)): foreach($attendees as $a): ?>
                        <tr>
                            <td><input type="text" name="attendee_name[]" class="form-control" value="<?= $a['name'] ?>"></td>
                            <td><input type="text" name="attendee_role[]" class="form-control" value="<?= $a['role'] ?>"></td>
                            <td><input type="checkbox" name="attendee_present[]" value="1" <?= $a['present'] ? 'checked' : '' ?>></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAttendeeRow(this)"><i class="fas fa-times"></i></button></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td><input type="text" name="attendee_name[]" class="form-control" placeholder="Full name"></td>
                            <td><input type="text" name="attendee_role[]" class="form-control" placeholder="Role"></td>
                            <td><input type="checkbox" name="attendee_present[]" value="1" checked></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAttendeeRow(this)"><i class="fas fa-times"></i></button></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="update_meeting" class="btn btn-warning">Update Minutes</button>
            <a href="./?page=meeting_list" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<script>
function addAttendeeRow() {
    var tbody = document.getElementById('attendeeTable').getElementsByTagName('tbody')[0];
    var row = tbody.insertRow();
    row.innerHTML = `<td><input type="text" name="attendee_name[]" class="form-control" placeholder="Full name"></td>
        <td><input type="text" name="attendee_role[]" class="form-control" placeholder="Role"></td>
        <td><input type="checkbox" name="attendee_present[]" value="1" checked></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeAttendeeRow(this)"><i class="fas fa-times"></i></button></td>`;
}
function removeAttendeeRow(btn) {
    var tbody = document.getElementById('attendeeTable').getElementsByTagName('tbody')[0];
    if(tbody.rows.length > 1) btn.closest('tr').remove();
}
$(function() {
    $('#summernote').summernote({
        height: 400,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video', 'hr']],
            ['view', ['fullscreen', 'codeview', 'help']],
            ['height', ['height']]
        ]
    });
});
</script>
