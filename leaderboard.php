<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$leaders = $pdo->query(
    'SELECT u.id, u.nama, u.total_poin, u.current_streak, u.longest_streak,
            COUNT(ub.badge_id) AS jumlah_badge
     FROM users u
     LEFT JOIN user_badges ub ON ub.user_id = u.id
     WHERE u.role = \'user\'
     GROUP BY u.id, u.nama, u.total_poin, u.current_streak, u.longest_streak
     ORDER BY u.total_poin DESC, u.longest_streak DESC, u.nama ASC'
)->fetchAll();

$pageTitle = 'Leaderboard Global - PaceFire';
require __DIR__ . '/includes/header.php';
?>
<section class="page-heading">
    <p class="eyebrow">Peringkat Komunitas</p>
    <h1>Leaderboard global</h1>
    <p>Rayakan progres dan konsistensi bersama komunitas PaceFire.</p>
</section>

<section class="panel-card table-panel">
    <div class="table-scroll">
        <table>
            <thead>
            <tr>
                <th>Peringkat</th>
                <th>Atlet</th>
                <th>Total Poin</th>
                <th>Streak Saat Ini</th>
                <th>Streak Terpanjang</th>
                <th>Jumlah Badge</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($leaders as $index => $leader): ?>
                <tr class="<?= $index < 3 ? 'top-ranked ' : '' ?><?= isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === (int) $leader['id'] ? 'current-user' : '' ?>">
                    <td><strong class="leaderboard-rank<?= $index < 3 ? ' medal-' . ($index + 1) : '' ?>">#<?= $index + 1 ?></strong></td>
                    <td>
                        <span class="leaderboard-person">
                            <span class="leaderboard-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($leader['nama'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                            <strong><?= e($leader['nama']) ?></strong>
                            <?php if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === (int) $leader['id']): ?>
                                <span class="you-label">Kamu</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="points-badge"><?= number_format((int) $leader['total_poin']) ?></td>
                    <td><?= (int) $leader['current_streak'] ?></td>
                    <td><?= (int) $leader['longest_streak'] ?></td>
                    <td><?= (int) $leader['jumlah_badge'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$leaders): ?>
                <tr><td colspan="6" class="empty-state">Belum ada atlet di leaderboard.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
