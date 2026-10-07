<?php
require_once __DIR__ . '/../includes/helpers.php';

final class DonationService {
    public static function generateReference(): string {
        $year = date('Y');
        for ($i = 0; $i < 10; $i++) {
            $code = 'EBZ-' . $year . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmt = db()->prepare('SELECT 1 FROM donations WHERE reference = ?');
            $stmt->execute([$code]);
            if (!$stmt->fetchColumn()) return $code;
        }
        throw new RuntimeException('Could not generate unique reference');
    }

    public static function create(string $name, string $email, ?string $phone, float $amount): array {
        $reference = self::generateReference();
        $stmt = db()->prepare(
          'INSERT INTO donations (reference, name, email, phone, amount, status) VALUES (?,?,?,?,?,\'pending\')'
        );
        $stmt->execute([$reference, $name, $email, $phone, $amount]);
        return ['id' => (int)db()->lastInsertId(), 'reference' => $reference];
    }

    public static function findByReference(string $ref): ?array {
        $stmt = db()->prepare('SELECT * FROM donations WHERE reference = ?');
        $stmt->execute([$ref]);
        return $stmt->fetch() ?: null;
    }
}
