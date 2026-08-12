<?php

declare(strict_types=1);

use PeanutAdmin\Kernel\Migration\OwnedMigration;
use think\migration\Migrator;

final class CreateFixtureRecord extends Migrator implements OwnedMigration
{
    public static function moduleKey(): string
    {
        return 'fixture.record';
    }

    public static function ownedTables(): array
    {
        return ['fixture_scope', 'fixture_record', 'fixture_outbox'];
    }

    public static function reversible(): bool
    {
        return true;
    }

    public function up(): void
    {
        $this->execute(<<<'SQL'
CREATE TABLE fixture_scope (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    UNIQUE KEY uk_fixture_scope_tenant_id (tenant_id, id)
) ENGINE=InnoDB;
CREATE TABLE fixture_record (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    scope_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    revision BIGINT UNSIGNED NOT NULL,
    KEY idx_fixture_record_tenant_id (tenant_id, id),
    KEY idx_fixture_record_tenant_scope (tenant_id, scope_id)
) ENGINE=InnoDB;
CREATE TABLE fixture_outbox (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    event_key VARCHAR(96) NOT NULL,
    resource_id VARCHAR(128) NULL,
    KEY idx_fixture_outbox_tenant_id (tenant_id, id)
) ENGINE=InnoDB
SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS fixture_outbox');
        $this->execute('DROP TABLE IF EXISTS fixture_record');
        $this->execute('DROP TABLE IF EXISTS fixture_scope');
    }
}
