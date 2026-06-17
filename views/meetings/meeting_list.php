<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title">Meeting Minutes</h4>
        <div class="card-tools">
            <a href="./?page=create_meeting" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> New Meeting</a>
        </div>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="meetingTabs">
            <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab">All</a></li>
            <li class="nav-item"><a class="nav-link" href="#draft" data-toggle="tab">Draft</a></li>
            <li class="nav-item"><a class="nav-link" href="#published" data-toggle="tab">Published</a></li>
        </ul>
        <div class="tab-content mt-3">
            <?php
            $allMeetings = selectAllMeetingMinutes($conn);
            $grouped = ['all' => $allMeetings, 'draft' => [], 'published' => []];
            foreach($allMeetings as $m) {
                if(isset($grouped[$m['status']])) $grouped[$m['status']][] = $m;
            }
            foreach(['all', 'draft', 'published'] as $tab):
            ?>
            <div class="tab-pane <?= $tab === 'all' ? 'active' : '' ?>" id="<?= $tab ?>">
                <div class="table-responsive">
                    <table class="table table-bordered table-search">
                        <thead>
                            <tr><th>Title</th><th>Date</th><th>Type</th><th>Venue</th><th>Status</th><th>Created By</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($grouped[$tab] as $m): ?>
                            <tr>
                                <td><?= $m['title'] ?></td>
                                <td><?= date('d/m/Y', strtotime($m['meeting_date'])) ?></td>
                                <td><?= $m['meeting_type'] ?></td>
                                <td><?= $m['venue'] ?></td>
                                <td><span class="badge badge-<?= $m['status'] === 'published' ? 'success' : 'warning' ?>"><?= ucfirst($m['status']) ?></span></td>
                                <td><?= $m['created_by_name'] ?></td>
                                <td>
                                    <a href="./?page=view_meeting&id=<?= $m['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                    <?php if($m['status'] === 'draft'): ?>
                                    <a href="./?page=edit_meeting&id=<?= $m['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                    <a href="./controllers/meeting_controller.php?delete_meeting=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
