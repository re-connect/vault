<?php
/**
 * Created by PhpStorm.
 * User: mathias
 * Date: 07/01/2019
 * Time: 16:46.
 */

namespace App\Tests\v1\Entity;

use App\Entity\Beneficiaire;
use App\Entity\ConsultationBeneficiaire;
use App\Entity\Membre;

class ConsultationBeneficiaireTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConsultationBeneficiaire
     */
    protected $object;

    /**
     * @throws \Exception
     */
    public function testGetterAndSetter()
    {
        $this->assertNull($this->object->getId());

        $date = new \DateTime();

        // Donnée personnelle

        $this->object->setCreatedAt($date);
        $this->assertEquals($date, $this->object->getCreatedAt());

        $membre = new Membre();
        $this->object->setMembre($membre);
        $this->assertEquals($membre, $this->object->getMembre());

        $beneficaire = new Beneficiaire();
        $this->object->setBeneficiaire($beneficaire);
        $this->assertEquals($beneficaire, $this->object->getBeneficiaire());
    }

    protected function setUp(): void
    {
        $this->object = new ConsultationBeneficiaire();
    }
}
