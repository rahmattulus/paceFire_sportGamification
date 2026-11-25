<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Sesi formulir tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        $challengeId = filter_input(INPUT_POST, 'challenge_id', FILTER_VALIDATE_INT);

        if ($action === 'cancel' && $challengeId) {
            $statement = $pdo->prepare(
                'UPDATE challenges
                 SET status = \'dibatalkan\'
                 WHERE id = ? AND status = \'aktif\''
            );
            $statement->execute([$challengeId]);
            set_flash('success', 'Challenge berhasil dibatalkan.');
            redirect('/pacefire/admin/challenges.php');
        } else {
            $errors[] = 'Permintaan pembatalan tidak valid.';
        }
    }
}

$challenges = $pdo->query(
    'SELECT c.*, u.nama AS nama_pembuat,
            COUNT(cp.user_id) AS jumlah_peserta
     FROM challenges c
     JOIN users u ON u.id = c.creator_id
     LEFT JOIN challenge_participants cp ON cp.challenge_id = c.id
     GROUP BY c.id, u.nama
     ORDER BY c.created_at DESC'
)->fetchAll();

$pageTitle = 'Moderasi Challenge - PaceFire';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Panel Admin</p>
    <h1>Moderasi Challenge</h1>
</section>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<section class="panel-card table-panel">
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Challenge</th>
                <th>Pembuat</th>
                <th>Target</th>
                <th>Peserta</th>
                <th>Periode</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($challenges as $challenge): ?>
                <tr>
                    <td><?= e($challenge['judul']) ?></td>
                    <td><?= e($challenge['nama_pembuat']) ?></td>
                    <td><?= number_format((float) $challenge['nilai_target'], 2) ?> <?= e($challenge['tipe_target']) ?></td>
                    <td><?= (int) $challenge['jumlah_peserta'] ?></td>
                    <td><?= e($challenge['tanggal_mulai']) ?><br><?= e($challenge['tanggal_selesai']) ?></td>
                    <td><?= e(ucfirst($challenge['status'])) ?></td>
                    <td>
                        <?php if ($challenge['status'] === 'aktif'): ?>
                            <form method="post" onsubmit="return confirm('Batalkan challenge ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="challenge_id" value="<?= (int) $challenge['id'] ?>">
                                <button class="btn-delete" type="submit">Batalkan</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$challenges): ?>
                <tr><td colspan="7" class="empty-state">Belum ada challenge.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
