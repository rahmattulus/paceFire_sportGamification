<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/gamification.php';
require_user();

sync_expired_challenges($pdo);
$tab = $_GET['tab'] ?? 'tersedia';
if (!in_array($tab, ['tersedia', 'diikuti', 'selesai'], true)) {
    $tab = 'tersedia';
}

$params = [$_SESSION['user_id']];
$tabWhere = '';
if ($tab === 'tersedia') {
    $tabWhere = "WHERE c.status = 'aktif'
                 AND NOT EXISTS (
                     SELECT 1 FROM challenge_participants cp2
                     WHERE cp2.challenge_id = c.id AND cp2.user_id = ?
                 )";
    $params = [$_SESSION['user_id']];
} elseif ($tab === 'diikuti') {
    $tabWhere = "JOIN challenge_participants joined ON joined.challenge_id = c.id
                 WHERE c.status = 'aktif' AND joined.user_id = ?";
} else {
    $tabWhere = "WHERE c.status = 'selesai'";
    $params = [];
}

$sql = 'SELECT c.*,
               creator.nama AS nama_pembuat,
               EXISTS(SELECT 1 FROM challenge_participants cp2
                      WHERE cp2.challenge_id = c.id AND cp2.user_id = ?) AS sudah_bergabung
        FROM challenges c
        JOIN users creator ON creator.id = c.creator_id';

if ($tab === 'diikuti') {
    $sql .= ' JOIN challenge_participants joined ON joined.challenge_id = c.id';
    $sql .= ' WHERE c.status = \'aktif\' AND joined.user_id = ?';
    $params = [$_SESSION['user_id'], $_SESSION['user_id']];
} elseif ($tab === 'tersedia') {
    $sql .= ' WHERE c.status = \'aktif\'
              AND NOT EXISTS (
                  SELECT 1 FROM challenge_participants cp2
                  WHERE cp2.challenge_id = c.id AND cp2.user_id = ?
              )';
    $params = [$_SESSION['user_id'], $_SESSION['user_id']];
} else {
    $sql .= ' WHERE c.status = \'selesai\'';
    $params = [$_SESSION['user_id']];
}
$sql .= ' ORDER BY c.tanggal_selesai ASC';

$statement = $pdo->prepare($sql);
$statement->execute($params);
$challenges = $statement->fetchAll();

$pageTitle = 'Challenge - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Kompetisi</p>
    <h1>Challenge</h1>
    <a class="btn btn-volt" href="/pacefire/challenge_create.php">Buat Challenge</a>
</section>

<nav class="challenge-tabs" aria-label="Tab challenge">
    <a class="<?= $tab === 'tersedia' ? 'active' : '' ?>" href="?tab=tersedia">Tersedia</a>
    <a class="<?= $tab === 'diikuti' ? 'active' : '' ?>" href="?tab=diikuti">Diikuti</a>
    <a class="<?= $tab === 'selesai' ? 'active' : '' ?>" href="?tab=selesai">Selesai</a>
</nav>

<section class="route-grid">
<?php foreach ($challenges as $challenge): ?>
    <?php
    $personalScore = 0;
    if ($challenge['sudah_bergabung']) {
        $personalScore = challenge_participant_score($pdo, (int) $challenge['id'], $_SESSION['user_id']);
    }
    $progress = $challenge['nilai_target'] > 0
        ? min(100, round(($personalScore / (float) $challenge['nilai_target']) * 100))
        : 0;
    ?>
    <article class="panel-card route-card">
        <p class="eyebrow"><?= e(ucfirst($challenge['tingkat_kesulitan'])) ?> - <?= e(ucfirst($challenge['jenis_olahraga'])) ?></p>
        <h2><?= e($challenge['judul']) ?></h2>
        <p><?= e($challenge['tipe_target']) ?>: <?= number_format((float) $challenge['nilai_target'], 2) ?></p>
        <p><?= e($challenge['tanggal_mulai']) ?> s/d <?= e($challenge['tanggal_selesai']) ?></p>
        <?php if ($challenge['sudah_bergabung']): ?>
            <div class="progress-track"><span style="width: <?= $progress ?>%"></span></div>
            <p><?= number_format($personalScore, 2) ?> / <?= number_format((float) $challenge['nilai_target'], 2) ?></p>
        <?php endif; ?>
        <a class="btn btn-secondary" href="/pacefire/challenge_detail.php?id=<?= (int) $challenge['id'] ?>">Lihat Detail</a>
    </article>
<?php endforeach; ?>
<?php if (!$challenges): ?>
    <div class="panel-card empty-state">Tidak ada challenge pada tab ini.</div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
