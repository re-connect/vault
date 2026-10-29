<?php

namespace App\Tests\v2\Entity;

use App\Entity\BeneficiaireCentre;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\RelayFactory;
use Symfony\Component\Validator\Constraints\NotNull;
use Zenstruck\Foundry\Test\Factories;

class BeneficiaireCentreTest extends AbstractEntityTest
{
    use Factories;

    public function testEntityIsValid(): void
    {
        $this->assertEntityIsValid($this->createBeneficiaryRelay());
    }

    /** @dataProvider provideInvalidEntities */
    public function testEntityIsNotValid(\Closure $removeField, string $property): void
    {
        $beneficiaryRelay = $this->createBeneficiaryRelay();
        $removeField($beneficiaryRelay);

        $this->assertEntityIsNotValid($beneficiaryRelay, $property, NotNull::class);
    }

    public static function provideInvalidEntities(): \Generator
    {
        yield 'Beneficiary relay without beneficiary should be invalid' => [
            static fn (BeneficiaireCentre $beneficiaryRelay) => $beneficiaryRelay->setBeneficiaire(),
            'beneficiaire',
        ];
        yield 'Beneficiary relay without relay should be invalid' => [
            static fn (BeneficiaireCentre $beneficiaryRelay) => $beneficiaryRelay->setCentre(),
            'centre',
        ];
    }

    private function createBeneficiaryRelay(): BeneficiaireCentre
    {
        return (new BeneficiaireCentre())
            ->setBeneficiaire(BeneficiaireFactory::createOne())
            ->setCentre(RelayFactory::createOne());
    }
}
