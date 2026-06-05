<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('center/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Raise Support Ticket</h5>
    <a href="<?= site_url('center/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="alert alert-info p-2 mb-3" style="font-size:.84rem;">
    <i class="bi bi-info-circle me-1"></i>
    Project: <strong><?= esc($project['name']) ?></strong> &nbsp;|&nbsp;
    Center: <strong><code><?= esc($center['center_code']) ?></code> — <?= esc($center['center_name']) ?></strong>
</div>

<?php
// Map raw CodeIgniter field/error messages to user-friendly descriptions
$friendlyErrors = [];
$rawErrors = session('errors') ?? [];
$rawError  = session('error')  ?? '';

$fieldLabels = [
    'subject'       => 'Subject',
    'urgency'       => 'Urgency Level',
    'issue_type'    => 'Type of Issue',
    'remote_access' => 'Remote Access',
    'mobile_number' => 'Mobile Number',
    'anydesk_id'    => 'AnyDesk ID',
    'description'   => 'Issue Description',
];

foreach ($rawErrors as $field => $msg) {
    $label = $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field));
    if ($field === 'mobile_number' && (str_contains($msg, 'exact_length') || str_contains($msg, 'regex'))) {
        $friendlyErrors[] = "<strong>Mobile Number</strong> must be exactly 10 digits (numbers only).";
    } elseif (str_contains($msg, 'required') || str_contains($msg, 'Required')) {
        $friendlyErrors[] = "<strong>{$label}</strong> is required — please fill this in before submitting.";
    } elseif (str_contains($msg, 'min_length') || str_contains($msg, 'max_length')) {
        $friendlyErrors[] = "<strong>{$label}</strong> is too short or too long.";
    } else {
        $friendlyErrors[] = "<strong>{$label}</strong>: {$msg}";
    }
}
?>

<?php if (! empty($friendlyErrors) || $rawError): ?>
<div class="alert alert-danger mb-3" style="font-size:.85rem;border-left:4px solid #dc2626;">
    <div class="fw-semibold mb-1" style="font-size:.9rem;">
        <i class="bi bi-x-circle-fill me-1"></i>Submission not possible — please fix the following:
    </div>
    <?php if ($rawError): ?>
        <div class="mt-1">• <?= esc($rawError) ?></div>
    <?php endif; ?>
    <?php foreach ($friendlyErrors as $err): ?>
        <div class="mt-1">• <?= $err ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="form-card" style="max-width:700px;">
    <form method="POST" action="<?= site_url('center/tickets/raise') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- 1. Subject -->
        <div class="mb-3">
            <label class="form-label">Subject <span class="text-danger">*</span></label>
            <input type="text" name="subject" class="form-control"
                   value="<?= old('subject') ?>"
                   placeholder="Brief one-line description of the issue" required>
        </div>

        <!-- 2. Urgency — nothing pre-selected -->
        <div class="mb-3">
            <label class="form-label">Urgency Level <span class="text-danger">*</span></label>
            <div class="row g-2">
                <?php $urgencies = [
                    'P1' => ['Critical', 'Exam cannot continue at all.',         'danger'],
                    'P2' => ['High',     'Significant problem, partially works.','warning'],
                    'P3' => ['Medium',   'Minor issue, not blocking.',            'primary'],
                ]; ?>
                <?php foreach ($urgencies as $val => [$label, $desc, $color]): ?>
                <div class="col-md-4">
                    <label class="d-block">
                        <input type="radio" name="urgency" value="<?= $val ?>" class="d-none urgency-radio"
                               <?= old('urgency') === $val ? 'checked' : '' ?>>
                        <div class="urgency-card border rounded p-2 text-center" style="cursor:pointer;transition:.15s;font-size:.83rem;">
                            <div class="urgency-badge badge-<?= strtolower($val) ?> mb-1"><?= $val ?></div>
                            <div class="fw-semibold"><?= $label ?></div>
                            <div class="text-muted" style="font-size:.75rem;"><?= $desc ?></div>
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3. Issue (dropdown from DB) -->
        <div class="mb-3">
            <label class="form-label">Issue <span class="text-danger">*</span></label>
            <select name="issue_id" id="issueSelect" class="form-select" required>
                <option value="">— Select the issue —</option>
                <?php foreach ($issues as $iss): ?>
                    <option value="<?= $iss['id'] ?>"
                        <?= old('issue_id') == $iss['id'] ? 'selected' : '' ?>>
                        <?= esc($iss['issue_text']) ?>
                    </option>
                <?php endforeach; ?>
                <option value="other" <?= old('issue_id') === 'other' ? 'selected' : '' ?>>
                    Other (describe below)
                </option>
            </select>
        </div>

        <!-- Custom description — shown ONLY when "Other" is selected from dropdown -->
        <div class="mb-3" id="descriptionBox" style="display:none;">
            <label class="form-label">Describe the Issue <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control" rows="4"
                      placeholder="Describe in detail — what happened, what you see, any error messages…"><?= old('description') ?></textarea>
        </div>

        <!-- 4. Type of Issue — Technical & Operational only, nothing pre-selected -->
        <div class="mb-3">
            <label class="form-label">Type of Issue <span class="text-danger">*</span></label>
            <div class="d-flex gap-3 flex-wrap">
                <?php foreach ([
                    'technical'   => ['bi-cpu',   'Technical',   'Hardware, software, network issues'],
                    'operational' => ['bi-people', 'Operational', 'Process, staff, logistics issues'],
                ] as $val => [$icon, $label, $desc]): ?>
                <label class="flex-grow-1" style="min-width:140px;max-width:220px;">
                    <input type="radio" name="issue_type" value="<?= $val ?>" class="d-none issue-type-radio"
                           <?= old('issue_type') === $val ? 'checked' : '' ?>>
                    <div class="issue-type-card border rounded p-2 text-center" style="cursor:pointer;transition:.15s;font-size:.82rem;">
                        <i class="bi <?= $icon ?>" style="font-size:1.3rem;display:block;margin-bottom:.25rem;"></i>
                        <div class="fw-semibold"><?= $label ?></div>
                        <div class="text-muted" style="font-size:.72rem;"><?= $desc ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 5. Remote Access Needed -->
        <div class="mb-3">
            <label class="form-label">Remote Access Needed <span class="text-danger">*</span></label>
            <div class="d-flex gap-3">
                <?php foreach ([
                    'yes' => ['bi-wifi',     'Yes', 'Remote access required'],
                    'no'  => ['bi-wifi-off', 'No',  'No remote access needed'],
                ] as $val => [$icon, $label, $desc]): ?>
                <label class="flex-grow-1" style="max-width:200px;">
                    <input type="radio" name="remote_access" value="<?= $val ?>" class="d-none remote-radio"
                           <?= old('remote_access') === $val ? 'checked' : '' ?>>
                    <div class="remote-card border rounded p-2 text-center" style="cursor:pointer;transition:.15s;font-size:.82rem;">
                        <i class="bi <?= $icon ?>" style="font-size:1.3rem;display:block;margin-bottom:.25rem;"></i>
                        <div class="fw-semibold"><?= $label ?></div>
                        <div class="text-muted" style="font-size:.72rem;"><?= $desc ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- AnyDesk ID — shown only when Remote Access = Yes -->
        <div class="mb-3" id="anydeskBox" style="display:none;">
            <label class="form-label">
                <i class="bi bi-display me-1 text-primary"></i>AnyDesk ID <span class="text-danger">*</span>
            </label>
            <input type="text" name="anydesk_id" id="anydeskId" class="form-control"
                   value="<?= old('anydesk_id') ?>"
                   placeholder="e.g. 123 456 789"
                   maxlength="20">
            <div class="form-text">Enter the AnyDesk ID so the admin can connect remotely.</div>
        </div>

        <!-- 6. Mobile Number -->
        <div class="mb-4">
            <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
            <input type="tel" name="mobile_number" id="mobile_number"
                   class="form-control <?= session('errors.mobile_number') ? 'is-invalid' : '' ?>"
                   value="<?= old('mobile_number') ?>"
                   placeholder="e.g. 9876543210"
                   maxlength="10"
                   pattern="[0-9]{10}"
                   inputmode="numeric"
                   required>
            <?php if (session('errors.mobile_number')): ?>
                <div class="invalid-feedback">Mobile Number must be exactly 10 digits (numbers only).</div>
            <?php else: ?>
                <div class="form-text">10-digit mobile number, numbers only.</div>
            <?php endif; ?>
            <div id="mobileError" class="invalid-feedback" style="display:none;"></div>
        </div>

        <!-- 7. Attachments -->
        <div class="mb-4">
            <label class="form-label">Attachments <small class="text-muted">(optional — screenshots, photos)</small></label>
            <input type="file" name="attachments[]" id="attachmentInput" class="form-control" accept="image/*,.pdf" multiple>
            <div class="form-text">Max <strong>30MB</strong> per file &nbsp;·&nbsp; Up to <?= $max_files ?> files.</div>
            <div id="attachmentError" class="mt-2" style="display:none;">
                <div class="alert alert-danger p-2 mb-0" style="font-size:.83rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span id="attachmentErrorMsg"></span>
                </div>
            </div>
            <div id="attachmentFileList" class="mt-2" style="font-size:.8rem;color:#475569;"></div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger px-4">
                <i class="bi bi-ticket-detailed me-1"></i>Submit Ticket
            </button>
            <a href="<?= site_url('center/dashboard') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('center/_idle_timeout') ?>
<script>
// Callbacks registry so card clicks can trigger side-effects (e.g. show/hide fields)
const cardGroupCallbacks = {};

function initCardGroup(radioClass, cardClass, onChangeCb) {
    const radios = document.querySelectorAll('.' + radioClass);
    function refreshHighlight() {
        radios.forEach(r => {
            const card = r.nextElementSibling;
            if (r.checked) {
                card.style.background  = '#eff6ff';
                card.style.borderColor = '#2563eb';
            } else {
                card.style.background  = '';
                card.style.borderColor = '';
            }
        });
        if (onChangeCb) onChangeCb();
    }
    radios.forEach(r => {
        r.nextElementSibling.addEventListener('click', () => { r.checked = true; refreshHighlight(); });
        r.addEventListener('change', refreshHighlight);
    });
    refreshHighlight();
}

// Show/hide AnyDesk ID field based on remote access selection
const anydeskBox   = document.getElementById('anydeskBox');
const anydeskInput = document.getElementById('anydeskId');

function toggleAnydesk() {
    const yesRadio = document.querySelector('.remote-radio[value="yes"]');
    const show = yesRadio && yesRadio.checked;
    anydeskBox.style.display = show ? 'block' : 'none';
    anydeskInput.required    = show;
    if (!show) anydeskInput.value = '';
}

initCardGroup('urgency-radio',    'urgency-card');
initCardGroup('issue-type-radio', 'issue-type-card');
initCardGroup('remote-radio',     'remote-card', toggleAnydesk);

toggleAnydesk(); // run on load for old() repopulation

// Show description box ONLY when "Other" is selected from issue dropdown
const issueSelect = document.getElementById('issueSelect');
const descBox     = document.getElementById('descriptionBox');

function toggleDesc() {
    const showDesc = issueSelect.value === 'other';
    descBox.style.display = showDesc ? 'block' : 'none';
    descBox.querySelector('textarea').required = showDesc;
}
issueSelect.addEventListener('change', toggleDesc);
toggleDesc();

// ── Mobile number — exactly 10 digits ────────────────────────
const mobileInput = document.getElementById('mobile_number');
mobileInput.addEventListener('input', function () {
    // Strip non-numeric characters as user types
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
});
mobileInput.addEventListener('blur', function () {
    if (this.value.length > 0 && this.value.length !== 10) {
        this.classList.add('is-invalid');
        document.getElementById('mobileError').style.display = 'block';
        document.getElementById('mobileError').textContent   = 'Mobile number must be exactly 10 digits.';
    } else {
        this.classList.remove('is-invalid');
        document.getElementById('mobileError').style.display = 'none';
    }
});

// ── Attachment size validation (30 MB per file) ──────────────
const MAX_FILE_BYTES = 30 * 1024 * 1024; // 30 MB
const attachInput    = document.getElementById('attachmentInput');
const attachError    = document.getElementById('attachmentError');
const attachErrorMsg = document.getElementById('attachmentErrorMsg');
const attachList     = document.getElementById('attachmentFileList');
const submitBtn      = document.querySelector('button[type="submit"]');

attachInput.addEventListener('change', function () {
    const files      = Array.from(this.files);
    const oversized  = files.filter(f => f.size > MAX_FILE_BYTES);
    attachError.style.display = 'none';
    attachList.innerHTML      = '';

    if (oversized.length > 0) {
        const names = oversized.map(f =>
            `<strong>${f.name}</strong> (${(f.size / 1024 / 1024).toFixed(1)} MB)`
        ).join(', ');
        const word = oversized.length === 1 ? 'file exceeds' : 'files exceed';
        attachErrorMsg.innerHTML  = `${oversized.length} ${word} the 30MB limit: ${names}. Please remove or compress before uploading.`;
        attachError.style.display = 'block';
        submitBtn.disabled        = true;
        submitBtn.title           = 'Remove oversized files to submit';
    } else {
        submitBtn.disabled = false;
        submitBtn.title    = '';
        if (files.length > 0) {
            attachList.innerHTML = files.map(f =>
                `<span class="badge bg-light text-dark border me-1 mb-1" style="font-weight:500;">
                    <i class="bi bi-paperclip me-1"></i>${f.name}
                    <span class="text-muted ms-1">(${(f.size / 1024 / 1024).toFixed(1)} MB)</span>
                </span>`
            ).join('');
        }
    }
});
</script>
<?= $this->endSection() ?>
