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

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
class InMemoryWizardStorage implements WizardStorageInterface
{
    private array $data = [];

    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $wizardName): array
    {
        return $this->data[$wizardName] ?? [];
    }

    public function has(string $wizardName): bool
    {
        return isset($this->data[$wizardName]);
    }

    public function remove(string $wizardName): void
    {
        unset($this->data[$wizardName]);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $wizardName, array $steps): void
    {
        $this->data[$wizardName] = array_column(
            array_map(function (string $key, mixed $model) {
                return [$key, $this->serializer->serialize($model, 'json', [
                    AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                    AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
                ])];
            }, array_keys($steps), $steps),
            1,
            0
        );
    }

    public function loadStep(string $wizardName, string $stepName, object $model): void
    {
        $steps = $this->get($wizardName);
        $step = $steps[$stepName] ?? null;

        if (null !== $step) {
            $type = \get_class($model);

            $this->serializer->deserialize($step, $type, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $model]);
        }
    }
}
