<?php

namespace App\Factory;

use App\Entity\Attributes\SharedPersonalData;
use App\Entity\DonneePersonnelle;
use App\Entity\Interface\ShareablePersonalData;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SharedPersonalDataFactory
{
    public const int SELECTOR_LENGTH = 24;
    public const string CHARACTERS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JWTTokenManagerInterface $JWTTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generateSharedPersonalData(User $user, DonneePersonnelle $personalData, string $email, string $locale = 'FR'): ?SharedPersonalData
    {
        if (!$personalData instanceof ShareablePersonalData) {
            return null;
        }

        $expirationDate = new \DateTime('+7 days');
        $selector = $this->generateSelector();
        $token = $this->generateToken($user, $personalData, $expirationDate, $selector);
        $downloadUrl = $this->getDownloadUrl($user->getId(), $token, $locale);
        $personalData->setPublicDownloadUrl($downloadUrl);

        $sharedPersonalData = $personalData::createShareablePersonalData()
            ->setPersonalData($personalData)
            ->setSharedBy($user)
            ->setSharedWithEmail($email)
            ->setExpirationDate($expirationDate)
            ->setToken($token)
            ->setSelector($selector);

        $this->em->persist($sharedPersonalData);
        $this->em->flush();

        return $sharedPersonalData;
    }

    private function generateSelector(): string
    {
        $string = '';
        for ($i = 0; $i < self::SELECTOR_LENGTH; ++$i) {
            try {
                $characterIndex = random_int(0, strlen(self::CHARACTERS) - 1);
            } catch (\Exception) {
                $characterIndex = 0;
            }
            $string .= self::CHARACTERS[$characterIndex];
        }

        return str_shuffle($string);
    }

    private function generateToken(User $user, ShareablePersonalData $personalData, \DateTime $expirationDate, string $selector): string
    {
        return $this->JWTTokenManager->createFromPayload($user, [
            'user_id' => $user->getId(),
            'personal_data_class' => $personalData::class,
            'personal_data_id' => $personalData->getId(),
            'expiration_date' => $expirationDate,
            'selector' => $selector,
        ]);
    }

    public function getDownloadUrl(int $userId, string $token, string $locale): string
    {
        return $this->urlGenerator->generate('download_shared_personal_data', [
            'id' => $userId,
            'token' => $token,
            'lang' => $locale,
        ], $this->urlGenerator::ABSOLUTE_URL);
    }
}
