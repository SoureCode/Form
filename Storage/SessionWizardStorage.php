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

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
class SessionWizardStorage implements WizardStorageInterface
{
    private SerializerInterface $serializer;

    private SessionInterface $session;

    public function __construct(RequestStack $requestStack, SerializerInterface $serializer)
    {
        $this->session = $requestStack->getSession();
        $this->serializer = $serializer;
    }

    public function clear(): void
    {
        $this->session->remove('_wizards');
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $wizardName): array
    {
        $wizards = $this->session->get('_wizards', []);

        if (!isset($wizards[$wizardName])) {
            return [];
        }

        return $wizards[$wizardName];
    }

    public function has(string $wizardName): bool
    {
        return $this->session->has('_wizards') && isset($this->session->get('_wizards')[$wizardName]);
    }

    public function remove(string $wizardName): void
    {
        $wizards = $this->session->get('_wizards', []);

        if (isset($wizards[$wizardName])) {
            unset($wizards[$wizardName]);
        }

        $this->session->set('_wizards', $wizards);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $wizardName, array $steps): void
    {
        $wizards = $this->session->get('_wizards', []);

        $wizards[$wizardName] = array_column(
            array_map(function (string $key, mixed $model) {
                return [$key, $this->serializer->serialize($model, 'json', [
                    AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                    AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
                ])];
            }, array_keys($steps), $steps),
            1,
            0
        );

        $this->session->set('_wizards', $wizards);
    }

    public function loadStep(string $wizardName, string $stepName, mixed $model): void
    {
        $steps = $this->get($wizardName);
        $step = $steps[$stepName] ?? null;

        if (null !== $step) {
            $type = \get_class($model);

            $this->serializer->deserialize($step, $type, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $model,
            ]);
        }
    }
}
