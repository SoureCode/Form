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

use RuntimeException;
use SoureCode\Component\Form\Storage\WizardStorageInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Jason Schilling <jason@sourecode.dev>
 */
abstract class Wizard
{
    protected ?string $currentStep = null;

    protected FormFactoryInterface $formFactory;

    protected ?Request $request = null;

    /**
     * @var array<string, WizardStep>
     */
    protected array $steps = [];

    protected WizardStorageInterface $storage;

    public function __construct(FormFactoryInterface $formFactory, WizardStorageInterface $storage)
    {
        $this->formFactory = $formFactory;
        $this->storage = $storage;
    }

    public function clear(): void
    {
        $this->storage->remove(static::class);
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    public function handleRequest(Request $request): FormInterface
    {
        /**
         * @var ?string $queryStep
         */
        $queryStep = $request->query->get('step', null);

        if (null !== $queryStep) {
            if (!\array_key_exists($queryStep, $this->steps)) {
                throw new \OutOfBoundsException(sprintf('The step "%d" does not exist.', $queryStep));
            }

            $this->currentStep = $queryStep;
        }

        if (null === $this->currentStep) {
            $this->currentStep = $this->getFirstStep();
        }

        if (null === $this->currentStep) {
            throw new RuntimeException('No current step set.');
        }

        $form = $this->createForm();

        $form->handleRequest($request);

        $this->steps[$this->currentStep]->setData($form->getData());

        if ((Request::METHOD_POST === $request->getMethod()) && $form->isSubmitted() && $form->isValid()) {
            $this->save();
        }

        return $form;
    }

    public function getFirstStep(): ?string
    {
        return array_key_first($this->steps);
    }

    public function createForm(): FormInterface
    {
        $stepName = $this->getCurrentStep();

        return $this->createFormForStep($stepName);
    }

    protected function save(): void
    {
        $data = [];

        foreach ($this->steps as $stepName => $step) {
            $stepData = $step->getData();

            if (null !== $stepData) {
                $data[$stepName] = $stepData;
            }
        }

        $this->storage->set(static::class, $data);
    }

    public function getCurrentStep(): string
    {
        if (null === $this->currentStep) {
            $this->currentStep = $this->getFirstStep();
        }

        if (null === $this->currentStep) {
            throw new RuntimeException('No step is defined.');
        }

        return $this->currentStep;
    }

    protected function createFormForStep(string $stepName): FormInterface
    {
        $step = $this->getStep($stepName);

        return $this->formFactory->create(
            $step->getType(),
            $step->getData(),
            array_merge(
                [
                    'action' => '?step='.$stepName,
                ],
                $step->getOptions(),
            )
        );
    }

    public function getStep(string $step): WizardStep
    {
        if (\array_key_exists($step, $this->steps)) {
            return $this->steps[$step];
        }

        throw new \OutOfBoundsException(sprintf('The step "%d" does not exist.', $step));
    }

    public function init(): void
    {
        $this->configureSteps();
    }

    abstract protected function configureSteps(): void;

    public function loadStepData(string $stepName, object $model): void
    {
        $wizard = static::class;
        $this->storage->loadStep($wizard, $stepName, $model);
        $this->setStepData($stepName, $model);
    }

    public function setStepData(string $stepName, ?object $data): void
    {
        $this->getStep($stepName)->setData($data);
    }

    public function nextStep(): bool
    {
        $currentStep = $this->getCurrentStep();
        $stepNames = array_keys($this->steps);
        $currentStepIndex = array_search($currentStep, $stepNames, true);

        if (false !== $currentStepIndex) {
            $nextStepIndex = $currentStepIndex + 1;

            if (isset($stepNames[$nextStepIndex])) {
                $this->currentStep = $stepNames[$nextStepIndex];

                return true;
            }
        }

        return false;
    }

    public function reset(): void
    {
        $this->currentStep = null;
        $this->request = null;
        $this->steps = [];
    }

    /**
     * @param class-string<FormTypeInterface> $type
     */
    protected function addStep(string $name, string $type, array $options = []): WizardStep
    {
        $this->steps[$name] = new WizardStep($type, $options);

        return $this->steps[$name];
    }
}
