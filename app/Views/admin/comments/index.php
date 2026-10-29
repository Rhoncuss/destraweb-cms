<?= $this->extend('admin/layout') ?>

<?= $this->section('actions') ?>
    <a href="<?= admin_url('settings/engagement') ?>" class="btn btn-soft"><i class="fa-solid fa-gear me-2"></i>Comment settings</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'spam' => 'Spam', 'trash' => 'Trash', 'all' => 'All'];
$qs = static function (array $overrides = []) use ($status, $query): string {
    $params = array_filter(array_merge(['status' => $status, 'q' => $query], $overrides), static fn ($v) => $v !== '' && $v !== null);

    return admin_url('comments' . ($params ? '?' . http_build_query($params) : ''));
};
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $out !== '' ? $out : '?';
};
?>

<div class="dst-card mb-3">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex flex-wrap gap-1 me-auto">
                <?php foreach ($statusLabels as $key => $label): ?>
                    <a href="<?= $qs(['status' => $key]) ?>" class="btn btn-sm <?= $status === $key ? 'btn-brand' : 'btn-soft' ?>">
                        <?= esc($label) ?>
                        <span class="ms-1" style="opacity:.65;"><?= (int) ($counts[$key] ?? 0) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <form method="get" action="<?= admin_url('comments') ?>" class="d-flex gap-2">
                <input type="hidden" name="status" value="<?= esc($status, 'attr') ?>">
                <input type="search" name="q" value="<?= esc($query) ?>" class="form-control form-control-sm" placeholder="Search author or text…" style="width:220px;">
                <button class="btn btn-soft btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>
</div>

<?php if ($items === []): ?>
    <div class="dst-card">
        <div class="dst-empty">
            <i class="fa-regular fa-comments"></i>
            <h3>No <?= esc(strtolower($statusLabels[$status] ?? 'comments')) ?> comments</h3>
            <p>When readers join the conversation, their comments appear here for moderation.</p>
        </div>
    </div>
<?php else: ?>
    <form method="post" action="<?= admin_url('comments/moderate') ?>" id="commentsBulkForm">
        <?= csrf_field() ?>
        <div class="dst-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:38px;"><input class="form-check-input" type="checkbox" data-check-all aria-label="Select all"></th>
                            <th>Author</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $bodyText = trim(preg_replace('/\s+/u', ' ', (string) $item['body']) ?? ''); ?>
                        <tr>
                            <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int) $item['id'] ?>" data-check-item aria-label="Select comment"></td>
                            <td style="min-width:170px;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="dst-avatar" style="width:34px;height:34px;font-size:12px;"><?= esc($initials((string) $item['author_name'])) ?></span>
                                    <div class="min-w-0">
                                        <div class="fw-650 truncate"><?= esc($item['author_name']) ?></div>
                                        <div class="text-muted-2 small truncate"><?= esc($item['author_email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="max-width:420px;">
                                <div class="small"><?= esc(mb_strimwidth($bodyText, 0, 190, '…')) ?></div>
                                <?php if (! empty($item['parent_id'])): ?>
                                    <span class="dst-chip mt-1" style="font-size:9px;">Reply</span>
                                <?php endif; ?>
                                <a class="text-muted-2 small d-inline-block mt-1" href="<?= esc(content_url(['id' => (int) $item['content_id']], ['slug' => (string) ($item['content_slug'] ?? '')], default_locale()), 'attr') ?>" target="_blank" rel="noopener">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1" style="font-size:9px;"></i><?= esc($item['content_title'] ?: 'Content #' . (int) $item['content_id']) ?>
                                </a>
                            </td>
                            <td><span class="dst-badge <?= esc((string) $item['status'], 'attr') ?>"><?= esc(ucfirst((string) $item['status'])) ?></span></td>
                            <td class="dst-meta"><?= esc(date('M j, Y', strtotime((string) $item['created_at']))) ?><br><span class="small"><?= esc(date('H:i', strtotime((string) $item['created_at']))) ?></span></td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <?php if ($item['status'] !== 'approved'): ?>
                                        <button class="btn btn-success-soft btn-icon" type="submit" formaction="<?= admin_url('comments/' . (int) $item['id'] . '/status') ?>" name="status" value="approved" title="Approve"><i class="fa-solid fa-check"></i></button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-soft btn-icon" title="Reply"
                                            data-reply-open
                                            data-reply-id="<?= (int) $item['id'] ?>"
                                            data-reply-author="<?= esc($item['author_name'], 'attr') ?>"
                                            data-reply-excerpt="<?= esc(mb_strimwidth($bodyText, 0, 160, '…'), 'attr') ?>">
                                        <i class="fa-solid fa-reply"></i>
                                    </button>
                                    <?php if ($item['status'] !== 'spam'): ?>
                                        <button class="btn btn-soft btn-icon" type="submit" formaction="<?= admin_url('comments/' . (int) $item['id'] . '/status') ?>" name="status" value="spam" title="Mark as spam"><i class="fa-solid fa-ban"></i></button>
                                    <?php endif; ?>
                                    <?php if ($item['status'] !== 'trash'): ?>
                                        <button class="btn btn-danger-soft btn-icon" type="submit" formaction="<?= admin_url('comments/' . (int) $item['id'] . '/status') ?>" name="status" value="trash" title="Move to trash"><i class="fa-regular fa-trash-can"></i></button>
                                    <?php else: ?>
                                        <button class="btn btn-success-soft btn-icon" type="submit" formaction="<?= admin_url('comments/' . (int) $item['id'] . '/status') ?>" name="status" value="pending" title="Restore"><i class="fa-solid fa-rotate-left"></i></button>
                                        <button class="btn btn-danger-soft btn-icon" type="submit" formaction="<?= admin_url('comments/' . (int) $item['id'] . '/delete') ?>" data-confirm="Delete this comment permanently?" title="Delete permanently"><i class="fa-solid fa-xmark"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-body border-top d-flex flex-wrap align-items-center gap-2">
                <select name="action" class="form-select form-select-sm" style="width:auto;" aria-label="Bulk action">
                    <option value="">Bulk actions</option>
                    <option value="approve">Approve</option>
                    <option value="pending">Mark pending</option>
                    <option value="spam">Mark as spam</option>
                    <option value="trash">Move to trash</option>
                    <option value="restore">Restore</option>
                    <option value="delete">Delete permanently</option>
                </select>
                <button class="btn btn-soft btn-sm" type="submit">Apply</button>
                <span class="text-muted-2 small ms-auto"><?= number_format((int) $total) ?> comment(s)</span>
            </div>
        </div>
    </form>

    <?php if ($pages > 1): ?>
        <nav class="mt-3" aria-label="Comment pagination">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= $qs(['page' => $i]) ?>"><?= $i ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="replyForm">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title" id="replyModalLabel">Reply to comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="dst-quote small mb-3" id="replyExcerpt"></div>
                <div class="text-muted-2 small mb-2">Replying as <?= esc($adminUser['name'] ?? 'Administrator') ?></div>
                <textarea class="form-control" name="body" rows="4" maxlength="4000" required placeholder="Write a thoughtful reply…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-brand">Publish reply</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script nonce="<?= esc(csp_nonce(), 'attr') ?>">
(function () {
    var modalEl = document.getElementById('replyModal');
    var form = document.getElementById('replyForm');
    if (!modalEl || !form) { return; }
    var modal = new bootstrap.Modal(modalEl);
    var excerpt = document.getElementById('replyExcerpt');
    var base = document.documentElement.getAttribute('data-base') || '/';

    document.querySelectorAll('[data-reply-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            var id = button.getAttribute('data-reply-id');
            form.action = base + '<?= esc($adminPrefix, 'attr') ?>/comments/' + id + '/reply';
            excerpt.textContent = '“' + (button.getAttribute('data-reply-excerpt') || '') + '” — ' + (button.getAttribute('data-reply-author') || '');
            modal.show();
        });
    });

    var bulk = document.getElementById('commentsBulkForm');
    if (bulk) {
        bulk.addEventListener('submit', function (event) {
            var action = bulk.querySelector('select[name="action"]');
            if (event.submitter && event.submitter.name === 'status') { return; }
            if (action && action.value === '') {
                event.preventDefault();
                if (window.Destra) { Destra.toast('error', 'Choose a bulk action first.'); }
            }
        });
    }
})();
</script>
<?= $this->endSection() ?>
