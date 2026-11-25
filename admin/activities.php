<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_once dirname(__DIR__) . '/includes/gamification.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Sesi formulir tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        $activityId = filter_input(INPUT_POST, 'activity_id', FILTER_VALIDATE_INT);

        if ($action === 'delete' && $activityId) {
            try {
                $pdo->beginTransaction();

                $activityStatement = $pdo->prepare(
                    'SELECT user_id FROM activities WHERE id = ?'
                );
                $activityStatement->execute([$activityId]);
                $activity = $activityStatement->fetch();

                if (!$activity) {
                    throw new RuntimeException('Aktivitas tidak ditemukan.');
                }

                $userId = (int) $activity['user_id'];
                $pdo->prepare('DELETE FROM activities WHERE id = ?')->execute([$activityId]);
                recalculate_user_after_activity_delete($pdo, $userId);

                $pdo->commit();
                set_flash('success', 'Aktivitas berhasil dihapus dan poin/streak pengguna dihitung ulang.');
                redirect('/pacefire/admin/activities.php');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('PaceFire admin activity delete failed: ' . $exception->getMessage());
                $errors[] = 'Aktivitas gagal dihapus.';
            }
        } else {
            $errors[] = 'Permintaan moderasi tidak valid.';
        }
    }
}

$filter = $_GET['filter'] ?? 'semua';
if (!in_array($filter, ['semua', 'mencurigakan'], true)) {
    $filter = 'semua';
}

$extremeCondition =
    "(a.jenis_olahraga = 'lari'
      AND ((a.durasi_menit * 60 / a.jarak_km) < 180
        OR (a.durasi_menit * 60 / a.jarak_km) > 900))
     OR
     (a.jenis_olahraga = 'sepeda'
      AND (a.jarak_km / (a.durasi_menit / 60) < 8
        OR a.jarak_km / (a.durasi_menit / 60) > 45))";

$sql = 'SELECT a.*, u.nama AS nama_pengguna, u.username, r.nama_jalur
        FROM activities a
        JOIN users u ON u.id = a.user_id
        LEFT JOIN routes r ON r.id = a.route_id';
if ($filter === 'mencurigakan') {
    $sql .= ' WHERE ' . $extremeCondition;
}
$sql .= ' ORDER BY a.created_at DESC, a.id DESC';
$activities = $pdo->query($sql)->fetchAll();

$pageTitle = 'Moderasi Aktivitas - PaceFire';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Panel Admin</p>
    <h1>Moderasi Aktivitas</h1>
</section>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<nav class="challenge-tabs">
    <a class="<?= $filter === 'semua' ? 'active' : '' ?>" href="?filter=semua">Semua Aktivitas</a>
    <a class="<?= $filter === 'mencurigakan' ? 'active' : '' ?>" href="?filter=mencurigakan">Mencurigakan</a>
</nav>

<section class="panel-card table-panel">
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pengguna</th>
                <th>Jenis</th>
                <th>Jarak</th>
                <th>Durasi</th>
                <th>Metrik</th>
                <th>Poin</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($activities as $activity): ?>
                <?php
                if ($activity['jenis_olahraga'] === 'lari') {
                    $paceSeconds = ((float) $activity['durasi_menit'] * 60) / (float) $activity['jarak_km'];
                    $metric = format_pace((float) $activity['jarak_km'], (float) $activity['durasi_menit']) . ' /km';
                    $extreme = $paceSeconds < 180 || $paceSeconds > 900;
                } else {
                    $speed = (float) $activity['jarak_km'] / ((float) $activity['durasi_menit'] / 60);
                    $metric = number_format($speed, 1) . ' km/jam';
                    $extreme = $speed < 8 || $speed > 45;
                }
                ?>
                <tr class="<?= $extreme ? 'suspicious-row' : '' ?>">
                    <td><?= e($activity['tanggal']) ?></td>
                    <td><?= e($activity['nama_pengguna']) ?><br><small>@<?= e($activity['username']) ?></small></td>
                    <td><?= e(ucfirst($activity['jenis_olahraga'])) ?></td>
                    <td><?= number_format((float) $activity['jarak_km'], 2) ?> km</td>
                    <td><?= number_format((float) $activity['durasi_menit'], 2) ?> menit</td>
                    <td><?= e($metric) ?></td>
                    <td class="points-badge"><?= (int) $activity['poin_total'] ?></td>
                    <td><?= $extreme ? '<span class="status-error">Mencurigakan</span>' : 'Normal' ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Hapus aktivitas ini dan hitung ulang total poin/streak?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="activity_id" value="<?= (int) $activity['id'] ?>">
                            <button class="btn-delete" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$activities): ?>
                <tr><td colspan="9" class="empty-state">Tidak ada aktivitas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
