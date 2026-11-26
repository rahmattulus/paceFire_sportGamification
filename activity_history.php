<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';
require_user();

$jenis = $_GET['jenis'] ?? '';
$tanggalMulai = trim((string) ($_GET['tanggal_mulai'] ?? ''));
$tanggalSelesai = trim((string) ($_GET['tanggal_selesai'] ?? ''));

$where = ['a.user_id = :user_id'];
$params = ['user_id' => $_SESSION['user_id']];

if (in_array($jenis, ['lari', 'sepeda'], true)) {
    $where[] = 'a.jenis_olahraga = :jenis';
    $params['jenis'] = $jenis;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalMulai)) {
    $where[] = 'a.tanggal >= :tanggal_mulai';
    $params['tanggal_mulai'] = $tanggalMulai;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSelesai)) {
    $where[] = 'a.tanggal <= :tanggal_selesai';
    $params['tanggal_selesai'] = $tanggalSelesai;
}

$sql = 'SELECT a.*, r.nama_jalur
        FROM activities a
        LEFT JOIN routes r ON r.id = a.route_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY a.tanggal DESC, a.created_at DESC';
$statement = $pdo->prepare($sql);
$statement->execute($params);
$activities = $statement->fetchAll();

$pageTitle = 'Riwayat Aktivitas - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Riwayat Pribadi</p>
    <h1>Aktivitasku</h1>
    <p>Lihat kembali setiap langkah dan jarak yang sudah kamu tempuh.</p>
</section>

<form method="get" class="filter-bar history-filter">
    <label for="activity-type">Jenis olahraga
        <select id="activity-type" name="jenis">
            <option value="">Semua</option>
            <option value="lari" <?= $jenis === 'lari' ? 'selected' : '' ?>>Lari</option>
            <option value="sepeda" <?= $jenis === 'sepeda' ? 'selected' : '' ?>>Sepeda</option>
        </select>
    </label>
    <label for="activity-from">Dari tanggal
        <input id="activity-from" type="date" name="tanggal_mulai" value="<?= e($tanggalMulai) ?>">
    </label>
    <label for="activity-to">Sampai tanggal
        <input id="activity-to" type="date" name="tanggal_selesai" value="<?= e($tanggalSelesai) ?>">
    </label>
    <button class="btn btn-volt" type="submit">Terapkan filter</button>
</form>

<?php if (!$activities): ?>
    <section class="panel-card empty-state history-empty-state">
        <span class="empty-state-icon" aria-hidden="true">↗</span>
        <strong>Belum ada aktivitas</strong>
        <span>Catat lari atau gowes pertamamu, lalu progresnya akan tampil di sini.</span>
        <a class="btn btn-volt" href="/pacefire/activity_create.php">Catat aktivitas</a>
    </section>
<?php else: ?>
    <?php
    $monthNames = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];
    $activitiesByMonth = [];
    foreach ($activities as $activity) {
        $month = $monthNames[(int) date('n', strtotime($activity['tanggal']))] . ' ' . date('Y', strtotime($activity['tanggal']));
        $activitiesByMonth[$month][] = $activity;
    }
    ?>
    <div class="history-groups">
        <?php foreach ($activitiesByMonth as $monthLabel => $monthActivities): ?>
            <section class="history-month">
                <h2><?= e($monthLabel) ?></h2>
                <div class="history-list">
                <?php foreach ($monthActivities as $activity): ?>
                    <?php
            $jarak = (float) $activity['jarak_km'];
            $durasi = (float) $activity['durasi_menit'];
            $metric = $activity['jenis_olahraga'] === 'lari'
                ? format_pace($jarak, $durasi) . ' /km'
                : number_format($jarak / ($durasi / 60), 1) . ' km/jam';
            $isRun = $activity['jenis_olahraga'] === 'lari';
            $activityName = $isRun ? 'Lari' : 'Sepeda';
            ?>
                    <article class="history-row">
                        <span class="history-icon <?= $isRun ? 'run' : 'bike' ?>" aria-hidden="true">
                            <?php if ($isRun): ?>
                                <svg viewBox="0 0 24 24"><circle cx="15" cy="5" r="2"/><path d="m12 8 3 2 3 1M9 10l3-2-2 5-4 3m6-3 3 3 2 4m-9-4-3 4"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24"><circle cx="6" cy="17" r="4"/><circle cx="18" cy="17" r="4"/><path d="m6 17 4-8h4l4 8M9 13h7m-6-4H8"/></svg>
                            <?php endif; ?>
                        </span>
                        <span class="history-detail">
                            <strong><?= e($activityName) ?><?= $activity['nama_jalur'] ? ' · ' . e($activity['nama_jalur']) : '' ?></strong>
                            <small><?= e(date('d', strtotime($activity['tanggal']))) ?> <?= e($monthNames[(int) date('n', strtotime($activity['tanggal']))]) ?> · <?= number_format($jarak, 2) ?> km · <?= number_format($durasi, 0) ?> menit · <?= e($metric) ?></small>
                        </span>
                        <span class="history-points">+<?= (int) $activity['poin_total'] ?><small>poin</small></span>
                    </article>
                <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
