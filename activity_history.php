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
</section>

<form method="get" class="filter-bar">
    <label>Jenis
        <select name="jenis">
            <option value="">Semua</option>
            <option value="lari" <?= $jenis === 'lari' ? 'selected' : '' ?>>Lari</option>
            <option value="sepeda" <?= $jenis === 'sepeda' ? 'selected' : '' ?>>Sepeda</option>
        </select>
    </label>
    <label>Dari
        <input type="date" name="tanggal_mulai" value="<?= e($tanggalMulai) ?>">
    </label>
    <label>Sampai
        <input type="date" name="tanggal_selesai" value="<?= e($tanggalSelesai) ?>">
    </label>
    <button class="btn btn-secondary" type="submit">Filter</button>
</form>

<section class="panel-card table-panel">
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Jalur</th>
                <th>Jarak</th>
                <th>Durasi</th>
                <th>Pace / Speed</th>
                <th>Poin</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($activities as $activity): ?>
                <?php
                $jarak = (float) $activity['jarak_km'];
                $durasi = (float) $activity['durasi_menit'];
                $metric = $activity['jenis_olahraga'] === 'lari'
                    ? format_pace($jarak, $durasi) . ' /km'
                    : number_format($jarak / ($durasi / 60), 1) . ' km/jam';
                ?>
                <tr>
                    <td><?= e(date('d M Y', strtotime($activity['tanggal']))) ?></td>
                    <td><?= e(ucfirst($activity['jenis_olahraga'])) ?></td>
                    <td><?= e($activity['nama_jalur'] ?: 'Tanpa jalur') ?></td>
                    <td><?= number_format($jarak, 2) ?> km</td>
                    <td><?= number_format($durasi, 2) ?> menit</td>
                    <td class="pace-value"><?= e($metric) ?></td>
                    <td class="points-badge"><?= (int) $activity['poin_total'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$activities): ?>
                <tr><td colspan="7" class="empty-state">Belum ada aktivitas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
