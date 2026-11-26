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
               (SELECT COUNT(*) FROM challenge_participants cp3
                WHERE cp3.challenge_id = c.id) AS jumlah_peserta,
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
    <div class="page-heading-row">
        <div>
            <h1>Challenge</h1>
            <p>Bergerak bareng komunitas, capai target dengan caramu.</p>
        </div>
        <a class="btn btn-volt" href="/pacefire/challenge_create.php">Buat challenge</a>
    </div>
</section>

<nav class="challenge-tabs" aria-label="Tab challenge">
    <a class="<?= $tab === 'tersedia' ? 'active' : '' ?>" href="?tab=tersedia">Tersedia</a>
    <a class="<?= $tab === 'diikuti' ? 'active' : '' ?>" href="?tab=diikuti">Diikuti</a>
    <a class="<?= $tab === 'selesai' ? 'active' : '' ?>" href="?tab=selesai">Selesai</a>
</nav>

<section class="route-grid challenge-grid">
<?php foreach ($challenges as $challenge): ?>
    <?php
    $personalScore = 0;
    if ($challenge['sudah_bergabung']) {
        $personalScore = challenge_participant_score($pdo, (int) $challenge['id'], $_SESSION['user_id']);
    }
    $progress = $challenge['nilai_target'] > 0
        ? min(100, round(($personalScore / (float) $challenge['nilai_target']) * 100))
        : 0;
    $targetUnit = $challenge['tipe_target'] === 'jarak' ? 'km' : 'poin';
    ?>
    <article class="panel-card route-card challenge-card">
        <div class="challenge-card-heading">
            <span class="challenge-art" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4v1a4 4 0 0 0 4 4M17 6h3v1a4 4 0 0 1-4 4"/></svg>
            </span>
            <p class="eyebrow"><?= e(ucfirst($challenge['jenis_olahraga'])) ?> · <?= e(ucfirst($challenge['tingkat_kesulitan'])) ?></p>
        </div>
        <h2><?= e($challenge['judul']) ?></h2>
        <p class="challenge-target"><?= e(ucfirst($challenge['tipe_target'])) ?> <strong><?= number_format((float) $challenge['nilai_target'], 2) ?> <?= e($targetUnit) ?></strong></p>
        <div class="challenge-card-meta">
            <span><?= (int) $challenge['jumlah_peserta'] ?> peserta</span>
            <span>Sampai <?= e(date('d M Y', strtotime($challenge['tanggal_selesai']))) ?></span>
        </div>
        <?php if ($challenge['sudah_bergabung']): ?>
            <div class="challenge-progress">
                <div class="progress-track"><span style="width: <?= $progress ?>%"></span></div>
                <small><?= number_format($personalScore, 2) ?> / <?= number_format((float) $challenge['nilai_target'], 2) ?> <?= e($targetUnit) ?></small>
            </div>
        <?php endif; ?>
        <a class="btn btn-volt" href="/pacefire/challenge_detail.php?id=<?= (int) $challenge['id'] ?>">
            <?= $challenge['sudah_bergabung'] ? 'Lihat progres' : 'Lihat challenge' ?>
        </a>
    </article>
<?php endforeach; ?>
<?php if (!$challenges): ?>
    <div class="panel-card empty-state challenge-empty-state">
        <span class="empty-state-icon" aria-hidden="true">⚑</span>
        <strong>Belum ada challenge di sini</strong>
        <span>Coba cek tab lain atau buat challenge baru bersama komunitas.</span>
        <?php if ($tab !== 'selesai'): ?>
            <a class="btn btn-volt" href="/pacefire/challenge_create.php">Buat challenge</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
