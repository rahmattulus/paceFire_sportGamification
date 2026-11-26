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

$userStatusStatement = $pdo->prepare(
    'SELECT current_streak, last_active_date FROM users WHERE id = ?'
);
$userStatusStatement->execute([$_SESSION['user_id']]);
$userStatus = $userStatusStatement->fetch();

$today = date('Y-m-d');
$todayActivityStatement = $pdo->prepare(
    'SELECT COUNT(*) AS jumlah, COALESCE(SUM(poin_total), 0) AS poin
     FROM activities
     WHERE user_id = ? AND tanggal = ?'
);
$todayActivityStatement->execute([$_SESSION['user_id'], $today]);
$todayActivityData = $todayActivityStatement->fetch();
$previewFirstActivity = (int) $todayActivityData['jumlah'] === 0;
$previewStreak = $userStatus['last_active_date'] === $today
    ? (int) $userStatus['current_streak']
    : ($userStatus['last_active_date'] === date('Y-m-d', strtotime('-1 day'))
        ? (int) $userStatus['current_streak'] + 1
        : 1);
$previewRemainingPoints = max(0, DAILY_POINT_CAP - (int) $todayActivityData['poin']);

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
                $closedChallenges = evaluate_user_challenges($pdo, $_SESSION['user_id']);

                $challengeMessage = '';
                if ($closedChallenges) {
                    $challengeNames = [];
                    foreach ($closedChallenges as $challengeId) {
                        $challengeName = $pdo->prepare('SELECT judul FROM challenges WHERE id = ?');
                        $challengeName->execute([$challengeId]);
                        $title = $challengeName->fetchColumn();
                        $challengeNames[] = $title;
                    }
                    $challengeMessage = ' Memenangkan challenge: ' . implode(', ', $challengeNames) . '.';
                }

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
                    '+' . $points['poin_total'] . ' poin. Streak: ' . $streak['current_streak'] . ' hari.' . $badgeMessage . $challengeMessage
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
    <p>Pilih jenis olahraga, isi jarak dan durasi. Jalur resmi bisa mengisi jarak otomatis.</p>
</section>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
<?php endforeach; ?>

<section class="activity-layout">
    <article class="panel-card activity-form-card">
        <form
            method="post"
            class="stack-form"
            id="activity-form"
            data-current-streak="<?= $previewStreak ?>"
            data-first-activity="<?= $previewFirstActivity ? '1' : '0' ?>"
            data-remaining-points="<?= $previewRemainingPoints ?>"
            data-running-rate="<?= POINT_RATE_RUNNING ?>"
            data-cycling-rate="<?= POINT_RATE_CYCLING ?>"
        >
            <?= csrf_field() ?>
            <fieldset class="sport-type-fieldset">
                <legend>Jenis olahraga</legend>
                <div class="sport-type-tiles">
                    <label class="sport-tile sport-tile-run">
                        <input type="radio" name="jenis_olahraga" value="lari" <?= $form['jenis_olahraga'] === 'lari' ? 'checked' : '' ?> required>
                        <span class="sport-tile-body">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="15" cy="5" r="2"/><path d="m12 8 3 2 3 1M9 10l3-2-2 5-4 3m6-3 3 3 2 4m-9-4-3 4"/></svg>
                            <span><strong>Lari</strong><small>Gerak dengan ritmemu</small></span>
                        </span>
                    </label>
                    <label class="sport-tile sport-tile-bike">
                        <input type="radio" name="jenis_olahraga" value="sepeda" <?= $form['jenis_olahraga'] === 'sepeda' ? 'checked' : '' ?> required>
                        <span class="sport-tile-body">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="17" r="4"/><circle cx="18" cy="17" r="4"/><path d="m6 17 4-8h4l4 8M9 13h7m-6-4H8"/></svg>
                            <span><strong>Sepeda</strong><small>Jelajahi lebih jauh</small></span>
                        </span>
                    </label>
                </div>
            </fieldset>
            <label>Pilih jalur <span class="field-optional">(opsional)</span>
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
            <div class="form-pair">
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
                    <input type="number" name="durasi_menit" id="durasi-menit" value="<?= e($form['durasi_menit']) ?>" min="0.01" step="0.01" required>
                </label>
            </div>
            <button class="btn btn-volt" type="submit">Simpan aktivitas</button>
        </form>
    </article>
    <aside class="activity-preview" id="activity-preview" aria-live="polite">
        <h2>Perkiraan aktivitas</h2>
        <p>Pace, poin, dan streak dihitung dari aktivitas yang kamu isi.</p>
        <dl class="estimate-list">
            <div><dt>Pace / kecepatan</dt><dd id="estimate-pace">—</dd></div>
            <div><dt>Perkiraan poin</dt><dd id="estimate-points">—</dd></div>
            <div><dt>Streak setelah aktivitas</dt><dd><?= $previewStreak ?> hari</dd></div>
        </dl>
        <small>Poin mengikuti batas harian dan dapat berubah jika ada bonus.</small>
    </aside>
</section>
<script src="/pacefire/assets/js/activity_form.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
