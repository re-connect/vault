<?php

namespace App\Tests\v2\Listener;

use App\Entity\Attributes\Document;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\DocumentFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class DocumentListenerTest extends KernelTestCase
{
    use Factories;
    private ?EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->em = $this->getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->em) {
            $this->em->close();
            $this->em = null;
        }
    }

    /**
     * @dataProvider fileNameSanitizationProvider
     */
    public function testFileNameSanitizationOnUpdate(string $originalName, string $expectedSanitizedName, bool $noExtension = false): void
    {
        $dummyName = 'dummy.pdf';
        $document = DocumentFactory::createOne([
            'nom' => $dummyName,
            'extension' => $noExtension ? '' : 'pdf',
        ])->object();

        $noExtension
            ? self::assertTrue('dummy_pdf' === $document->getNom())
            : self::assertTrue($dummyName === $document->getNom());

        $document->setNom($originalName);
        $this->em->flush();
        $document = DocumentFactory::find($document);
        self::assertTrue($expectedSanitizedName === $document->getNom());
    }

    /**
     * @dataProvider fileNameSanitizationProvider
     */
    public function testFileNameSanitizationOnCreate(string $originalName, string $expectedSanitizedName, bool $noExtension = false): void
    {
        $document = (new Document())
            ->setNom($originalName)
            ->setTaille(100)
            ->setExtension($noExtension ? '' : 'pdf')
            ->setBeneficiaire(BeneficiaireFactory::random()->object());
        $this->em->persist($document);
        $this->em->flush();
        $document = DocumentFactory::find($document);

        self::assertTrue($expectedSanitizedName === $document->getNom());
    }

    public function fileNameSanitizationProvider(): array
    {
        return [
            // Test slashes
            ['file/with/slashes.pdf', 'file_with_slashes.pdf'],
            ['file\\with\\backslashes.pdf', 'file_with_backslashes.pdf'],

            // Test dots
            ['file.with.multiple.dots.pdf', 'file_with_multiple_dots.pdf'],
            ['...leading.dots.pdf', 'leading_dots.pdf'],
            ['trailing.dots...pdf', 'trailing_dots.pdf'],

            // Test special characters
            ['file:with*special?chars.pdf', 'file_with_special_chars.pdf'],
            ['file<with>pipes|.pdf', 'file_with_pipes.pdf'],
            ['file"with"quotes.pdf', 'file_with_quotes.pdf'],

            // Test mixed problematic characters
            ['../../etc/passwd', 'etc_passwd', true],
            ['file/../../etc/passwd.pdf', 'file_etc_passwd.pdf'],

            // Test edge cases
            ['   spaces   .pdf', 'spaces.pdf'],
            ['no_extension', 'no_extension', true],

            // Test with dashes
            ['file-with-dashes.pdf', 'file_with_dashes.pdf'],

            // Test normal filenames (should not change)
            ['normal_filename.pdf', 'normal_filename.pdf'],
            ['file_with_underscores.pdf', 'file_with_underscores.pdf'],
        ];
    }
}
