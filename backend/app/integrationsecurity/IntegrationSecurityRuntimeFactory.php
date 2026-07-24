<?php

declare(strict_types=1);

namespace PeanutAdmin\App\integrationsecurity;

use PDO;
use PeanutAdmin\IntegrationSecurity\Application\MachineIdentityService;
use PeanutAdmin\IntegrationSecurity\Application\MachineScopeCatalog;
use PeanutAdmin\IntegrationSecurity\Application\MachineScopeGrantPolicy;
use PeanutAdmin\IntegrationSecurity\Application\MachineScopeGrantResolver;
use PeanutAdmin\IntegrationSecurity\Application\SessionSecurityService;
use PeanutAdmin\IntegrationSecurity\Application\WebhookDeliveryLogService;
use PeanutAdmin\IntegrationSecurity\Application\WebhookService;
use PeanutAdmin\IntegrationSecurity\Crypto\AesGcmWebhookSecretProtector;
use PeanutAdmin\IntegrationSecurity\Persistence\PdoIntegrationSecurityRepository;
use PeanutAdmin\IntegrationSecurity\Webhook\SystemHostAddressResolver;
use PeanutAdmin\IntegrationSecurity\Webhook\WebhookDestinationPolicy;
use PeanutAdmin\Kernel\Context\AuthorizedOperationContext;

final class IntegrationSecurityRuntimeFactory
{
    public static function machines(PDO $pdo): MachineIdentityService
    {
        $config = self::config();
        $catalog = new MachineScopeCatalog($config['machine_scopes']);
        $resolver = new class($config['machine_scopes']) implements MachineScopeGrantResolver {
            public function __construct(private array $scopes) {}
            public function grantableScopes(AuthorizedOperationContext $context): array { return $this->scopes; }
        };
        return new MachineIdentityService(new PdoIntegrationSecurityRepository($pdo), new MachineScopeGrantPolicy($catalog, $resolver));
    }

    public static function webhooks(PDO $pdo): WebhookService
    {
        $config = self::config();
        return new WebhookService(
            new PdoIntegrationSecurityRepository($pdo),
            new WebhookDestinationPolicy(new SystemHostAddressResolver()),
            new AesGcmWebhookSecretProtector($config['key_id'], $config['base64_key']),
        );
    }

    public static function deliveries(PDO $pdo): WebhookDeliveryLogService { return new WebhookDeliveryLogService(new PdoIntegrationSecurityRepository($pdo)); }
    public static function sessions(PDO $pdo): SessionSecurityService { return new SessionSecurityService(new PdoIntegrationSecurityRepository($pdo)); }

    /** @return array{key_id:string,base64_key:string,machine_scopes:list<string>} */
    private static function config(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/integration-security.php';
        if (!is_array($config) || !is_string($config['key_id'] ?? null) || !is_string($config['base64_key'] ?? null) || !is_array($config['machine_scopes'] ?? null)) {
            throw new \RuntimeException('INTEGRATION_SECURITY_CONFIG_INVALID');
        }
        return $config;
    }
}
