<?php
// Thin wrapper kept for structural symmetry; audit_log() lives in includes/helpers.php
require_once __DIR__ . '/../includes/helpers.php';
final class AuditService {
    public static function log(?int $adminId, string $action, ?string $entityType = null, ?int $entityId = null, $old = null, $new = null): void {
        audit_log($adminId, $action, $entityType, $entityId, $old, $new);
    }
}
