<?php
/*
 * This file is part of the SoureCode package.
 *
 * (c) Jason Schilling <jason@sourecode.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SoureCode\Component\Form\Storage;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
interface WizardStorageInterface
{
    public function clear(): void;

    /**
     * @return array<string, object>
     */
    public function get(string $wizardName): array;

    public function has(string $wizardName): bool;

    public function loadStep(string $wizardName, string $stepName, object $model): void;

    public function remove(string $wizardName): void;

    /**
     * @param array<string, object> $steps
     */
    public function set(string $wizardName, array $steps): void;
}
