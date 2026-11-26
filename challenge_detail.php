<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/gamification.php';
require_user();

sync_expired_challenges($pdo);
$challengeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$challengeId) {
    set_flash('error', 'Challenge tidak ditemukan.');
    redirect('/pacefire/challenges.php');
}

$challengeStatement = $pdo->prepare(
    'SELECT c.*, u.nama AS nama_pembuat
     FROM challenges c
     JOIN users u ON u.id = c.creator_id
     WHERE c.id = ?'
);
$challengeStatement->execute([$challengeId]);
$challenge = $challengeStatement->fetch();
if (!$challenge) {
    set_flash('error', 'Challenge tidak ditemukan.');
    redirect('/pacefire/challenges.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'join' && $challenge['status'] === 'aktif') {
        $check = $pdo->prepare(
            'SELECT 1 FROM challenge_participants WHERE challenge_id = ? AND user_id = ?'
        );
        $check->execute([$challengeId, $_SESSION['user_id']]);
        if (!$check->fetch()) {
            $pdo->prepare(
                'INSERT INTO challenge_participants (challenge_id, user_id) VALUES (?, ?)'
            )->execute([$challengeId, $_SESSION['user_id']]);
            set_flash('success', 'Berhasil bergabung dengan challenge.');
        }
    }
    redirect('/pacefire/challenge_detail.php?id=' . $challengeId);
}

$participantStatement = $pdo->prepare(
    'SELECT 1 FROM challenge_participants WHERE challenge_id = ? AND user_id = ?'
);
$participantStatement->execute([$challengeId, $_SESSION['user_id']]);
$joined = (bool) $participantStatement->fetch();

$participantRows = $pdo->prepare(
    'SELECT u.id, u.nama, cp.peringkat_akhir, cp.bonus_poin_didapat
     FROM challenge_participants cp
     JOIN users u ON u.id = cp.user_id
     WHERE cp.challenge_id = ?'
);
$participantRows->execute([$challengeId]);
$participantRows = $participantRows->fetchAll();

$leaderboard = [];
foreach ($participantRows as $participant) {
    $leaderboard[] = [
        'id' => (int) $participant['id'],
        'nama' => $participant['nama'],
        'score' => challenge_participant_score($pdo, $challengeId, (int) $participant['id']),
        'peringkat_akhir' => $participant['peringkat_akhir'],
        'bonus_poin_didapat' => (int) $participant['bonus_poin_didapat'],
    ];
}
usort($leaderboard, static function (array $a, array $b): int {
    $scoreComparison = $b['score'] <=> $a['score'];
    return $scoreComparison !== 0 ? $scoreComparison : $a['id'] <=> $b['id'];
});

$personalScore = $joined
    ? challenge_participant_score($pdo, $challengeId, $_SESSION['user_id'])
    : 0;
$progress = $challenge['nilai_target'] > 0
    ? min(100, round(($personalScore / (float) $challenge['nilai_target']) * 100))
    : 0;
$targetUnit = $challenge['tipe_target'] === 'jarak' ? 'km' : 'poin';

$pageTitle = e($challenge['judul']) . ' - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Challenge komunitas</p>
    <h1><?= e($challenge['judul']) ?></h1>
</section>

<section class="route-detail-grid challenge-detail-grid">
    <article class="panel-card challenge-detail-main">
        <span class="challenge-detail-tag"><?= e(ucfirst($challenge['jenis_olahraga'])) ?> · <?= e(ucfirst($challenge['tingkat_kesulitan'])) ?></span>
        <h2>Info challenge</h2>
        <?php if ($challenge['deskripsi']): ?>
            <p><?= e($challenge['deskripsi']) ?></p>
        <?php endif; ?>
        <dl class="challenge-meta">
            <dt>Pembuat</dt><dd><?= e($challenge['nama_pembuat']) ?></dd>
            <dt>Jenis</dt><dd><?= e(ucfirst($challenge['jenis_olahraga'])) ?></dd>
            <dt>Target</dt><dd><?= number_format((float) $challenge['nilai_target'], 2) ?> <?= e($targetUnit) ?></dd>
            <dt>Periode</dt><dd><?= e($challenge['tanggal_mulai']) ?> s/d <?= e($challenge['tanggal_selesai']) ?></dd>
            <dt>Status</dt><dd><?= e(ucfirst($challenge['status'])) ?></dd>
        </dl>

        <?php if ($joined): ?>
            <div class="challenge-personal-progress">
                <div class="panel-heading-row"><h2>Progresmu</h2><strong><?= $progress ?>%</strong></div>
                <div class="progress-track" role="progressbar" aria-label="Progres challenge" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= $progress ?>%"></span></div>
                <p><?= number_format($personalScore, 2) ?> / <?= number_format((float) $challenge['nilai_target'], 2) ?> <?= e($targetUnit) ?></p>
            </div>
        <?php elseif ($challenge['status'] === 'aktif'): ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="join">
                <button class="btn btn-volt" type="submit">Gabung challenge</button>
            </form>
        <?php endif; ?>
    </article>

    <aside class="panel-card challenge-leaderboard">
        <h2>Peserta challenge</h2>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Peringkat</th><th>Peserta</th><th>Skor</th></tr></thead>
                <tbody>
                <?php foreach ($leaderboard as $index => $participant): ?>
                    <tr>
                        <td><?= $challenge['status'] === 'selesai' && $participant['peringkat_akhir'] ? (int) $participant['peringkat_akhir'] : $index + 1 ?></td>
                        <td><?= e($participant['nama']) ?></td>
                        <td><?= number_format($participant['score'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$leaderboard): ?>
                    <tr><td colspan="3" class="empty-state">Belum ada peserta.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
