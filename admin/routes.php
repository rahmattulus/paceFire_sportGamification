<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_check.php';
require_admin();

$errors = [];
$editRoute = null;
$form = [
    'nama_jalur' => '',
    'jenis_olahraga' => 'lari',
    'jarak_km' => '',
    'lokasi' => '',
    'deskripsi' => '',
    'tingkat_kesulitan' => 'sedang',
];

if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM routes WHERE id = ?');
    $statement->execute([(int) $_GET['edit']]);
    $editRoute = $statement->fetch();
    if ($editRoute) {
        $form = array_intersect_key($editRoute, $form);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Sesi formulir tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'toggle') {
            $routeId = filter_input(INPUT_POST, 'route_id', FILTER_VALIDATE_INT);
            if ($routeId) {
                $pdo->prepare(
                    'UPDATE routes SET is_active = 1 - is_active WHERE id = ?'
                )->execute([$routeId]);
                set_flash('success', 'Status jalur berhasil diperbarui.');
                redirect('/pacefire/admin/routes.php');
            }
            $errors[] = 'Jalur tidak valid.';
        } elseif ($action === 'save') {
            $form = [
                'nama_jalur' => trim((string) ($_POST['nama_jalur'] ?? '')),
                'jenis_olahraga' => (string) ($_POST['jenis_olahraga'] ?? ''),
                'jarak_km' => trim((string) ($_POST['jarak_km'] ?? '')),
                'lokasi' => trim((string) ($_POST['lokasi'] ?? '')),
                'deskripsi' => trim((string) ($_POST['deskripsi'] ?? '')),
                'tingkat_kesulitan' => (string) ($_POST['tingkat_kesulitan'] ?? ''),
            ];

            $jarak = filter_var($form['jarak_km'], FILTER_VALIDATE_FLOAT);
            if (strlen($form['nama_jalur']) < 3) {
                $errors[] = 'Nama jalur minimal 3 karakter.';
            }
            if (!in_array($form['jenis_olahraga'], ['lari', 'sepeda'], true)) {
                $errors[] = 'Jenis olahraga tidak valid.';
            }
            if ($jarak === false || $jarak <= 0) {
                $errors[] = 'Jarak resmi harus lebih besar dari nol.';
            }
            if (!in_array($form['tingkat_kesulitan'], ['mudah', 'sedang', 'sulit'], true)) {
                $errors[] = 'Tingkat kesulitan tidak valid.';
            }

            if (!$errors) {
                $routeId = filter_input(INPUT_POST, 'route_id', FILTER_VALIDATE_INT);
                if ($routeId) {
                    $pdo->prepare(
                        'UPDATE routes
                         SET nama_jalur = ?, jenis_olahraga = ?, jarak_km = ?, lokasi = ?, deskripsi = ?, tingkat_kesulitan = ?
                         WHERE id = ?'
                    )->execute([
                        $form['nama_jalur'],
                        $form['jenis_olahraga'],
                        $jarak,
                        $form['lokasi'] ?: null,
                        $form['deskripsi'] ?: null,
                        $form['tingkat_kesulitan'],
                        $routeId,
                    ]);
                    set_flash('success', 'Jalur berhasil diperbarui.');
                } else {
                    $pdo->prepare(
                        'INSERT INTO routes (nama_jalur, jenis_olahraga, jarak_km, lokasi, deskripsi, tingkat_kesulitan)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    )->execute([
                        $form['nama_jalur'],
                        $form['jenis_olahraga'],
                        $jarak,
                        $form['lokasi'] ?: null,
                        $form['deskripsi'] ?: null,
                        $form['tingkat_kesulitan'],
                    ]);
                    set_flash('success', 'Jalur baru berhasil ditambahkan.');
                }
                redirect('/pacefire/admin/routes.php');
            }
        }
    }
}

$routes = $pdo->query(
    'SELECT id, nama_jalur, jenis_olahraga, jarak_km, tingkat_kesulitan, is_active
     FROM routes
     ORDER BY is_active DESC, nama_jalur ASC'
)->fetchAll();

$pageTitle = 'Kelola Jalur - PaceFire';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Administrasi</p>
    <h1>Kelola Jalur</h1>
</section>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<section class="admin-route-layout">
    <article class="panel-card">
        <h2><?= $editRoute ? 'Edit Jalur' : 'Tambah Jalur' ?></h2>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="route_id" value="<?= $editRoute ? (int) $editRoute['id'] : '' ?>">
            <label>Nama Jalur
                <input type="text" name="nama_jalur" value="<?= e($form['nama_jalur']) ?>" maxlength="100" required>
            </label>
            <label>Jenis Olahraga
                <select name="jenis_olahraga" required>
                    <option value="lari" <?= $form['jenis_olahraga'] === 'lari' ? 'selected' : '' ?>>Lari</option>
                    <option value="sepeda" <?= $form['jenis_olahraga'] === 'sepeda' ? 'selected' : '' ?>>Sepeda</option>
                </select>
            </label>
            <label>Jarak Resmi (km)
                <input type="number" name="jarak_km" value="<?= e($form['jarak_km']) ?>" min="0.01" step="0.01" required>
            </label>
            <label>Lokasi
                <input type="text" name="lokasi" value="<?= e($form['lokasi']) ?>" maxlength="150">
            </label>
            <label>Deskripsi
                <textarea name="deskripsi" rows="4"><?= e($form['deskripsi']) ?></textarea>
            </label>
            <label>Tingkat Kesulitan
                <select name="tingkat_kesulitan" required>
                    <option value="mudah" <?= $form['tingkat_kesulitan'] === 'mudah' ? 'selected' : '' ?>>Mudah</option>
                    <option value="sedang" <?= $form['tingkat_kesulitan'] === 'sedang' ? 'selected' : '' ?>>Sedang</option>
                    <option value="sulit" <?= $form['tingkat_kesulitan'] === 'sulit' ? 'selected' : '' ?>>Sulit</option>
                </select>
            </label>
            <button class="btn btn-volt" type="submit"><?= $editRoute ? 'Simpan Perubahan' : 'Tambah Jalur' ?></button>
        </form>
    </article>

    <article class="panel-card table-panel">
        <h2>Daftar Jalur</h2>
        <div class="table-scroll">
            <table>
                <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>Jarak</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($routes as $route): ?>
                    <tr>
                        <td><?= e($route['nama_jalur']) ?></td>
                        <td><?= e(ucfirst($route['jenis_olahraga'])) ?></td>
                        <td><?= number_format((float) $route['jarak_km'], 2) ?> km</td>
                        <td><?= $route['is_active'] ? 'Aktif' : 'Nonaktif' ?></td>
                        <td class="table-actions">
                            <a class="btn-link" href="?edit=<?= (int) $route['id'] ?>">Edit</a>
                            <form method="post" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="route_id" value="<?= (int) $route['id'] ?>">
                                <button class="btn-link" type="submit">
                                    <?= $route['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
