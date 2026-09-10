<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchCoverage\Tests\Fixtures\Coverage\App\Concerns;

trait ActionTrait
{
    abstract public function handle(): void;
}
