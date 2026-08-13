<?php

declare(strict_types=1);

namespace PeanutAdmin\Kernel\Tests\Unit\Persistence\Schema;

use PeanutAdmin\Kernel\Persistence\Schema\KernelSchema;
use PHPUnit\Framework\TestCase;

final class KernelSchemaTest extends TestCase
{
    public function testDirectInstallIncludesCurrentTenantClientSchema(): void
    {
        $historicalChallenge = KernelSchema::createSql('pa_login_challenge');
        self::assertStringNotContainsString('`client_key`', $historicalChallenge);

        $install = implode("\n", KernelSchema::installSql());
        self::assertStringContainsString('ADD COLUMN `client_key`', $install);
        self::assertStringContainsString('DROP CHECK `chk_tenant_session_client`', $install);
        self::assertStringContainsString("'^[a-z][a-z0-9-]{0,63}$'", $install);
    }
}
