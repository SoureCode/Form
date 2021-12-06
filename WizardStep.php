<?php
/*
 * This file is part of the SoureCode package.
 *
 * (c) Jason Schilling <jason@sourecode.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SoureCode\Component\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
class WizardStep
{
    private ?object $data = null;

    private array $options;

    /**
     * @var class-string<FormTypeInterface> $type
     */
    private string $type;

    /**
     * @param class-string<FormTypeInterface> $type
     */
    public function __construct(string $type, array $options = [])
    {
        $this->type = $type;
        $this->options = $options;
    }

    public function getData(): ?object
    {
        return $this->data;
    }

    public function setData(?object $data): void
    {
        $this->data = $data;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return class-string<FormTypeInterface>
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param class-string<FormTypeInterface> $type
     *
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
