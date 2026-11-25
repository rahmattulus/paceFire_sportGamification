<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth_check.php';
require_user();

$errors = [];
$form = [
    'judul' => '',
    'deskripsi' => '',
    'jenis_olahraga' => 'semua',
    'tipe_target' => 'jarak',
    'nilai_target' => '',
    'tingkat_kesulitan' => 'easy',
    'tanggal_mulai' => date('Y-m-d'),
    'tanggal_selesai' => date('Y-m-d', strtotime('+7 days')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Sesi formulir tidak valid.';
    } else {
        foreach ($form as $key => $value) {
            $form[$key] = trim((string) ($_POST[$key] ?? $value));
        }

        $target = filter_var($form['nilai_target'], FILTER_VALIDATE_FLOAT);
        if (strlen($form['judul']) < 3 || strlen($form['judul']) > 120) {
            $errors[] = 'Judul harus 3 sampai 120 karakter.';
        }
        if (!in_array($form['jenis_olahraga'], ['lari', 'sepeda', 'semua'], true)) {
            $errors[] = 'Jenis olahraga tidak valid.';
        }
        if (!in_array($form['tipe_target'], ['jarak', 'poin'], true)) {
            $errors[] = 'Tipe target tidak valid.';
        }
        if ($target === false || $target <= 0) {
            $errors[] = 'Nilai target harus lebih dari nol.';
        }
        if (!isset(CHALLENGE_WINNER_BONUSES[$form['tingkat_kesulitan']])) {
            $errors[] = 'Tingkat kesulitan tidak valid.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['tanggal_mulai'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $form['tanggal_selesai'])
            || $form['tanggal_selesai'] < $form['tanggal_mulai']) {
            $errors[] = 'Rentang tanggal challenge tidak valid.';
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $bonus = CHALLENGE_WINNER_BONUSES[$form['tingkat_kesulitan']];
                $insert = $pdo->prepare(
                    'INSERT INTO challenges
                     (creator_id, judul, deskripsi, jenis_olahraga, tipe_target, nilai_target,
                      tingkat_kesulitan, bonus_poin_pemenang, tanggal_mulai, tanggal_selesai)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([
                    $_SESSION['user_id'],
                    $form['judul'],
                    $form['deskripsi'] ?: null,
                    $form['jenis_olahraga'],
                    $form['tipe_target'],
                    $target,
                    $form['tingkat_kesulitan'],
                    $bonus,
                    $form['tanggal_mulai'],
                    $form['tanggal_selesai'],
                ]);
                $challengeId = (int) $pdo->lastInsertId();
                $pdo->prepare(
                    'INSERT INTO challenge_participants (challenge_id, user_id) VALUES (?, ?)'
                )->execute([$challengeId, $_SESSION['user_id']]);
                $pdo->commit();

                set_flash('success', 'Challenge berhasil dibuat dan Anda terdaftar sebagai peserta.');
                redirect('/pacefire/challenge_detail.php?id=' . $challengeId);
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('PaceFire challenge create failed: ' . $exception->getMessage());
                $errors[] = 'Challenge gagal dibuat.';
            }
        }
    }
}

$pageTitle = 'Buat Challenge - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Kompetisi Baru</p>
    <h1>Buat Challenge</h1>
</section>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>
<section class="panel-card activity-form-card">
    <form method="post" class="stack-form">
        <?= csrf_field() ?>
        <label>Judul
            <input type="text" name="judul" value="<?= e($form['judul']) ?>" maxlength="120" required>
        </label>
        <label>Deskripsi
            <textarea name="deskripsi" rows="4"><?= e($form['deskripsi']) ?></textarea>
        </label>
        <label>Jenis Olahraga
            <select name="jenis_olahraga">
                <option value="semua" <?= $form['jenis_olahraga'] === 'semua' ? 'selected' : '' ?>>Semua</option>
                <option value="lari" <?= $form['jenis_olahraga'] === 'lari' ? 'selected' : '' ?>>Lari</option>
                <option value="sepeda" <?= $form['jenis_olahraga'] === 'sepeda' ? 'selected' : '' ?>>Sepeda</option>
            </select>
        </label>
        <label>Tipe Target
            <select name="tipe_target">
                <option value="jarak" <?= $form['tipe_target'] === 'jarak' ? 'selected' : '' ?>>Jarak</option>
                <option value="poin" <?= $form['tipe_target'] === 'poin' ? 'selected' : '' ?>>Poin</option>
            </select>
        </label>
        <label>Nilai Target
            <input type="number" name="nilai_target" value="<?= e($form['nilai_target']) ?>" min="0.01" step="0.01" required>
        </label>
        <label>Tingkat Kesulitan
            <select name="tingkat_kesulitan" id="tingkat-kesulitan">
                <option value="easy" <?= $form['tingkat_kesulitan'] === 'easy' ? 'selected' : '' ?>>Easy - 50 poin</option>
                <option value="medium" <?= $form['tingkat_kesulitan'] === 'medium' ? 'selected' : '' ?>>Medium - 100 poin</option>
                <option value="hard" <?= $form['tingkat_kesulitan'] === 'hard' ? 'selected' : '' ?>>Hard - 200 poin</option>
            </select>
        </label>
        <label>Tanggal Mulai
            <input type="date" name="tanggal_mulai" value="<?= e($form['tanggal_mulai']) ?>" required>
        </label>
        <label>Tanggal Selesai
            <input type="date" name="tanggal_selesai" value="<?= e($form['tanggal_selesai']) ?>" required>
        </label>
        <button class="btn btn-volt" type="submit">Buat Challenge</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
