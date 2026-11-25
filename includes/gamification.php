<?php

require_once dirname(__DIR__) . '/config/constants.php';

function validate_activity(PDO $pdo, int $userId, string $jenis, float $jarak, float $durasi, ?int $routeId = null): array
{
    $errors = [];

    if ($jarak <= 0 || $durasi <= 0) {
        return ['Jarak dan durasi harus lebih besar dari nol.'];
    }

    if ($routeId !== null) {
        $route = $pdo->prepare(
            'SELECT jenis_olahraga, jarak_km, is_active
             FROM routes
             WHERE id = ?'
        );
        $route->execute([$routeId]);
        $routeData = $route->fetch();

        if (!$routeData || !$routeData['is_active']) {
            $errors[] = 'Jalur tidak tersedia.';
        } elseif ($routeData['jenis_olahraga'] !== $jenis) {
            $errors[] = 'Jenis olahraga tidak sesuai dengan jalur yang dipilih.';
        } elseif (abs((float) $routeData['jarak_km'] - $jarak) > 0.001) {
            $errors[] = 'Jarak aktivitas harus mengikuti jarak resmi jalur.';
        }
    }

    if ($jenis === 'lari') {
        if ($jarak < RUNNING_DISTANCE_MIN_KM || $jarak > RUNNING_DISTANCE_MAX_KM) {
            $errors[] = 'Jarak lari harus antara 1 km dan 42 km.';
        }

        $paceSeconds = ($durasi * 60) / $jarak;
        if ($paceSeconds < RUNNING_PACE_MIN_SECONDS || $paceSeconds > RUNNING_PACE_MAX_SECONDS) {
            $errors[] = 'Pace lari tidak wajar. Pace harus antara 3:00 dan 15:00 per km.';
        }
    } elseif ($jenis === 'sepeda') {
        if ($jarak < CYCLING_DISTANCE_MIN_KM || $jarak > CYCLING_DISTANCE_MAX_KM) {
            $errors[] = 'Jarak sepeda harus antara 3 km dan 150 km.';
        }

        $speedKmh = $jarak / ($durasi / 60);
        if ($speedKmh < CYCLING_SPEED_MIN_KMH || $speedKmh > CYCLING_SPEED_MAX_KMH) {
            $errors[] = 'Kecepatan sepeda tidak wajar. Kecepatan harus antara 8 dan 45 km/jam.';
        }
    } else {
        $errors[] = 'Jenis olahraga tidak valid.';
    }

    return $errors;
}

function calculate_streak(PDO $pdo, int $userId, string $today): array
{
    $statement = $pdo->prepare(
        'SELECT current_streak, longest_streak, last_active_date
         FROM users
         WHERE id = ?'
    );
    $statement->execute([$userId]);
    $user = $statement->fetch();

    $current = (int) $user['current_streak'];
    $longest = (int) $user['longest_streak'];
    $lastActive = $user['last_active_date'];

    if ($lastActive === $today) {
        $nextCurrent = $current;
    } elseif ($lastActive === date('Y-m-d', strtotime('-1 day', strtotime($today)))) {
        $nextCurrent = $current + 1;
    } else {
        $nextCurrent = 1;
    }

    $nextLongest = max($longest, $nextCurrent);

    return [
        'current_streak' => $nextCurrent,
        'longest_streak' => $nextLongest,
        'last_active_date' => $today,
    ];
}

function calculate_points(string $jenis, float $jarak, int $currentStreak, bool $firstActivityToday, int $remainingDailyPoints = DAILY_POINT_CAP): array
{
    $rate = $jenis === 'lari' ? POINT_RATE_RUNNING : POINT_RATE_CYCLING;
    $base = (int) floor($jarak * $rate);

    if ($currentStreak >= 14) {
        $streakBonus = (int) floor($base * 0.50);
    } elseif ($currentStreak >= 7) {
        $streakBonus = (int) floor($base * 0.25);
    } elseif ($currentStreak >= 3) {
        $streakBonus = (int) floor($base * 0.10);
    } else {
        $streakBonus = 0;
    }

    $dailyBonus = $firstActivityToday ? 5 : 0;
    $bonus = $streakBonus + $dailyBonus;
    $total = min($base + $bonus, max(0, $remainingDailyPoints));
    $bonus = max(0, $total - $base);

    return [
        'poin_dasar' => $base,
        'poin_bonus' => $bonus,
        'poin_total' => $total,
    ];
}

function evaluate_badges(PDO $pdo, int $userId): array
{
    $userStatement = $pdo->prepare(
        'SELECT current_streak FROM users WHERE id = ?'
    );
    $userStatement->execute([$userId]);
    $currentStreak = (int) $userStatement->fetchColumn();

    $activityCount = (int) $pdo->query(
        'SELECT COUNT(*) FROM activities WHERE user_id = ' . (int) $userId
    )->fetchColumn();

    $runningDistance = (float) $pdo->query(
        "SELECT COALESCE(SUM(jarak_km), 0) FROM activities
         WHERE user_id = " . (int) $userId . " AND jenis_olahraga = 'lari'"
    )->fetchColumn();

    $cyclingDistance = (float) $pdo->query(
        "SELECT COALESCE(SUM(jarak_km), 0) FROM activities
         WHERE user_id = " . (int) $userId . " AND jenis_olahraga = 'sepeda'"
    )->fetchColumn();

    $routeCount = (int) $pdo->query(
        'SELECT COUNT(DISTINCT route_id) FROM activities
         WHERE user_id = ' . (int) $userId . ' AND route_id IS NOT NULL'
    )->fetchColumn();

    $badges = $pdo->query(
        'SELECT id, kode_badge, nama_badge, kriteria_tipe, kriteria_nilai, bonus_poin
         FROM badges'
    )->fetchAll();

    $newBadges = [];

    foreach ($badges as $badge) {
        $earned = false;
        $target = (float) $badge['kriteria_nilai'];

        switch ($badge['kriteria_tipe']) {
            case 'aktivitas':
                $earned = $activityCount >= $target;
                break;
            case 'streak':
                $earned = $currentStreak >= $target;
                break;
            case 'jarak_lari':
                $earned = $runningDistance >= $target;
                break;
            case 'jarak_sepeda':
                $earned = $cyclingDistance >= $target;
                break;
            case 'eksplorasi_jalur':
                $earned = $routeCount >= $target;
                break;
        }

        if (!$earned) {
            continue;
        }

        $owned = $pdo->prepare(
            'SELECT 1 FROM user_badges WHERE user_id = ? AND badge_id = ?'
        );
        $owned->execute([$userId, $badge['id']]);
        if ($owned->fetch()) {
            continue;
        }

        $insert = $pdo->prepare(
            'INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)'
        );
        $insert->execute([$userId, $badge['id']]);

        $bonus = (int) $badge['bonus_poin'];
        $update = $pdo->prepare(
            'UPDATE users SET total_poin = total_poin + ? WHERE id = ?'
        );
        $update->execute([$bonus, $userId]);

        $newBadges[] = [
            'nama' => $badge['nama_badge'],
            'bonus_poin' => $bonus,
        ];
    }

    return $newBadges;
}
