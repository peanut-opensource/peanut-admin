<?php

declare(strict_types=1);

namespace PeanutAdmin\App\upgrade;

interface UpgradeTargetVerifier
{
    public function verify(string $root, UpgradePlan $plan): void;
}
