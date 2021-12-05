<?php
/*
 * This file is part of the SoureCode package.
 *
 * (c) Jason Schilling <jason@sourecode.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SoureCode\Component\Form\Tests;

use SoureCode\Component\Form\Storage\InMemoryWizardStorage;
use SoureCode\Component\Form\Tests\Mock\Form\AddressType;
use SoureCode\Component\Form\Tests\Mock\Form\Wizard\MockWizard;
use SoureCode\Component\Form\Tests\Mock\Model\Address;
use SoureCode\Component\Form\Tests\Mock\Model\Person;
use SoureCode\Component\Form\WizardStep;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class WizardTest extends TypeTestCase
{
    protected SerializerInterface $serializer;

    public function testGetStep(): void
    {
        $storage = new InMemoryWizardStorage($this->serializer);
        $wizard = new MockWizard($this->factory, $storage);

        $wizard->init();

        $wizard->loadStepData('address', $address = new Address());

        $actual = $wizard->getStep('address');

        $this->assertInstanceOf(WizardStep::class, $actual);
        $this->assertSame($address, $actual->getData());
        $this->assertSame(AddressType::class, $actual->getType());
        $this->assertSame([], $actual->getOptions());
    }

    public function testHandleRequest()
    {
        $storage = new InMemoryWizardStorage($this->serializer);
        $wizard = new MockWizard($this->factory, $storage);

        $wizard->init();

        $wizard->loadStepData('address', $address = new Address());

        $request = Request::create('/', 'POST', [
            'address' => [
                'street' => 'Street',
                'city' => 'City',
                'zip' => 'Zip',
            ],
        ]);

        $wizard->handleRequest($request);

        $this->assertSame($address, $wizard->getStep('address')->getData());
        $this->assertSame('Street', $address->street);
        $this->assertSame('City', $address->city);
        $this->assertSame('Zip', $address->zip);
    }

    public function testInit(): void
    {
        $storage = new InMemoryWizardStorage($this->serializer);
        $wizard = new MockWizard($this->factory, $storage);

        $wizard->init();

        $steps = $wizard->getSteps();

        $this->assertCount(2, $steps);
    }

    public function testNextStep(): void
    {
        $storage = new InMemoryWizardStorage($this->serializer);
        $wizard = new MockWizard($this->factory, $storage);

        $request = Request::create('/', 'POST', [
            'address' => [
                'zip' => '12345',
                'city' => 'Berlin',
                'street' => 'Foo Street',
                'number' => '1',
            ],
        ]);

        $wizard->init();

        $wizard->loadStepData('address', new Address());
        $wizard->loadStepData('person', new Person());

        $wizard->handleRequest($request);

        $actual = $wizard->nextStep();

        self::assertTrue($actual);
        self::assertSame('person', $wizard->getCurrentStep());
    }

    public function testWizardFull(): void
    {
        $storage = new InMemoryWizardStorage($this->serializer);
        $wizard = new MockWizard($this->factory, $storage);

        $request = Request::create('/', 'POST', [
            'address' => [
                'zip' => '12345',
                'city' => 'Berlin',
                'street' => 'Foo Street',
                'number' => '1',
            ],
        ]);

        $wizard->init();

        $wizard->loadStepData('address', $address = new Address());
        $wizard->loadStepData('person', $person = new Person());

        $form = $wizard->handleRequest($request);

        // This check ensures there are no transformation failures
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());

        self::assertSame('12345', $address->zip);
        self::assertSame('Berlin', $address->city);
        self::assertSame('Foo Street', $address->street);
        self::assertSame('1', $address->number);

        self::assertNull($person->firstname);
        self::assertNull($person->lastname);

        $wizard->reset();

        $request = Request::create('/?step=person', 'POST', [
            'person' => [
                'firstname' => 'John',
                'lastname' => 'Doe',
            ],
        ]);

        // Handle next step

        $wizard->init();

        $wizard->loadStepData('address', $address = new Address());
        $wizard->loadStepData('person', $person = new Person());

        $form = $wizard->handleRequest($request);

        // This check ensures there are no transformation failures
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());

        self::assertSame('12345', $address->zip);
        self::assertSame('Berlin', $address->city);
        self::assertSame('Foo Street', $address->street);
        self::assertSame('1', $address->number);

        self::assertSame('John', $person->firstname);
        self::assertSame('Doe', $person->lastname);
    }

    protected function getExtensions()
    {
        $extensions = parent::getExtensions();

        $extensions[] = new HttpFoundationExtension();

        return $extensions;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
    }
}
