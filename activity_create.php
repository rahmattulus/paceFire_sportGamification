<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/gamification.php';
require_user();

$errors = [];
$form = [
    'jenis_olahraga' => 'lari',
    'route_id' => '',
    'jarak_km' => '',
    'durasi_menit' => '',
];

if (isset($_GET['route_id']) && ctype_digit((string) $_GET['route_id'])) {
    $form['route_id'] = (int) $_GET['route_id'];
}

$routes = $pdo->query(
    'SELECT id, nama_jalur, jenis_olahraga, jarak_km
     FROM routes
     WHERE is_active = 1
     ORDER BY nama_jalur'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Sesi formulir tidak valid.';
    } else {
        $form['jenis_olahraga'] = (string) ($_POST['jenis_olahraga'] ?? '');
        $form['route_id'] = trim((string) ($_POST['route_id'] ?? ''));
        $form['jarak_km'] = trim((string) ($_POST['jarak_km'] ?? ''));
        $form['durasi_menit'] = trim((string) ($_POST['durasi_menit'] ?? ''));

        $jarak = filter_var($form['jarak_km'], FILTER_VALIDATE_FLOAT);
        $durasi = filter_var($form['durasi_menit'], FILTER_VALIDATE_FLOAT);
        $routeId = $form['route_id'] !== '' ? filter_var($form['route_id'], FILTER_VALIDATE_INT) : null;

        if ($routeId === false) {
            $routeId = null;
        }
        if ($jarak === false) {
            $errors[] = 'Jarak tidak valid.';
        }
        if ($durasi === false) {
            $errors[] = 'Durasi tidak valid.';
        }

        if (!$errors) {
            $errors = validate_activity(
                $pdo,
                $_SESSION['user_id'],
                $form['jenis_olahraga'],
                (float) $jarak,
                (float) $durasi,
                $routeId
            );
        }

        if (!$errors) {
            $today = date('Y-m-d');

            $todayStatement = $pdo->prepare(
                'SELECT COUNT(*) AS jumlah, COALESCE(SUM(poin_total), 0) AS poin
                 FROM activities
                 WHERE user_id = ? AND tanggal = ?'
            );
            $todayStatement->execute([$_SESSION['user_id'], $today]);
            $todayData = $todayStatement->fetch();

            $firstActivityToday = (int) $todayData['jumlah'] === 0;
            $remainingDailyPoints = DAILY_POINT_CAP - (int) $todayData['poin'];

            if ($remainingDailyPoints <= 0) {
                $errors[] = 'Kuota poin harian 500 poin sudah tercapai.';
            } else {
                $streakPreview = calculate_streak($pdo, $_SESSION['user_id'], $today);
                $pointsPreview = calculate_points(
                    $form['jenis_olahraga'],
                    (float) $jarak,
                    $streakPreview['current_streak'],
                    $firstActivityToday,
                    $remainingDailyPoints
                );
                $calculatedTotal = $pointsPreview['poin_dasar'] + $pointsPreview['poin_bonus'];
                if ($calculatedTotal > $remainingDailyPoints) {
                    $errors[] = 'Aktivitas ini melebihi sisa kuota poin harian 500 poin.';
                }
            }
        }

        if (!$errors) {
            $today = date('Y-m-d');

            try {
                $pdo->beginTransaction();

                $streak = calculate_streak($pdo, $_SESSION['user_id'], $today);
                $points = calculate_points(
                    $form['jenis_olahraga'],
                    (float) $jarak,
                    $streak['current_streak'],
                    $firstActivityToday,
                    $remainingDailyPoints
                );

                $insert = $pdo->prepare(
                    'INSERT INTO activities
                     (user_id, route_id, jenis_olahraga, jarak_km, durasi_menit, tanggal, poin_dasar, poin_bonus, poin_total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insert->execute([
                    $_SESSION['user_id'],
                    $routeId,
                    $form['jenis_olahraga'],
                    $jarak,
                    $durasi,
                    $today,
                    $points['poin_dasar'],
                    $points['poin_bonus'],
                    $points['poin_total'],
                ]);

                $pdo->prepare(
                    'UPDATE users
                     SET total_poin = total_poin + ?,
                         current_streak = ?,
                         longest_streak = ?,
                         last_active_date = ?
                     WHERE id = ?'
                )->execute([
                    $points['poin_total'],
                    $streak['current_streak'],
                    $streak['longest_streak'],
                    $today,
                    $_SESSION['user_id'],
                ]);

                $newBadges = evaluate_badges($pdo, $_SESSION['user_id']);

                $pdo->commit();

                $badgeMessage = '';
                if ($newBadges) {
                    $badgeMessage = ' Badge baru: ' . implode(
                        ', ',
                        array_map(static fn(array $badge) => $badge['nama'] . ' +' . $badge['bonus_poin'] . ' poin', $newBadges)
                    ) . '.';
                }

                set_flash(
                    'success',
                    '+' . $points['poin_total'] . ' poin. Streak: ' . $streak['current_streak'] . ' hari.' . $badgeMessage
                );
                redirect('/pacefire/dashboard.php');
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('PaceFire activity save failed: ' . $exception->getMessage());
                $errors[] = 'Aktivitas gagal disimpan. Silakan coba lagi.';
            }
        }
    }
}

$selectedRoute = null;
if ($form['route_id'] !== '') {
    foreach ($routes as $route) {
        if ((int) $route['id'] === (int) $form['route_id']) {
            $selectedRoute = $route;
            $form['jenis_olahraga'] = $route['jenis_olahraga'];
            $form['jarak_km'] = number_format((float) $route['jarak_km'], 2, '.', '');
            break;
        }
    }
}

$pageTitle = 'Catat Aktivitas - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Aktivitas Baru</p>
    <h1>Catat Pergerakanmu</h1>
</section>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<section class="panel-card activity-form-card">
    <form method="post" class="stack-form" id="activity-form">
        <?= csrf_field() ?>
        <label>Jenis Olahraga
            <select name="jenis_olahraga" id="jenis-olahraga" required>
                <option value="lari" <?= $form['jenis_olahraga'] === 'lari' ? 'selected' : '' ?>>Lari</option>
                <option value="sepeda" <?= $form['jenis_olahraga'] === 'sepeda' ? 'selected' : '' ?>>Sepeda</option>
            </select>
        </label>
        <label>Pilih Jalur (Opsional)
            <select name="route_id" id="route-id">
                <option value="">Tanpa jalur resmi</option>
                <?php foreach ($routes as $route): ?>
                    <option
                        value="<?= (int) $route['id'] ?>"
                        data-type="<?= e($route['jenis_olahraga']) ?>"
                        data-distance="<?= e(number_format((float) $route['jarak_km'], 2, '.', '')) ?>"
                        <?= (string) $form['route_id'] === (string) $route['id'] ? 'selected' : '' ?>
                    >
                        <?= e($route['nama_jalur']) ?> (<?= number_format((float) $route['jarak_km'], 2) ?> km)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Jarak (km)
            <input
                type="number"
                name="jarak_km"
                id="jarak-km"
                value="<?= e($form['jarak_km']) ?>"
                min="0.01"
                step="0.01"
                required
                <?= $selectedRoute ? 'readonly' : '' ?>
            >
        </label>
        <label>Durasi (menit)
            <input type="number" name="durasi_menit" value="<?= e($form['durasi_menit']) ?>" min="0.01" step="0.01" required>
        </label>
        <button class="btn btn-volt" type="submit">Simpan Aktivitas</button>
    </form>
</section>
<script src="/pacefire/assets/js/activity_form.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
