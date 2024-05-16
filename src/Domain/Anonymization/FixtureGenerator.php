<?php

namespace App\Domain\Anonymization;

use App\Entity\User;

class FixtureGenerator
{
    final public const RANDOM_LAST_NAMES = ['Martin', 'Bernard', 'Thomas', 'Petit', 'Robert', 'Richard', 'Durand', 'Dubois', 'Moreau', 'Laurent', 'Simon', 'Michel', 'Lefèvre', 'Leroy', 'Roux', 'David', 'Bertrand', 'Morel', 'Fournier', 'Girard', 'Bonnet', 'Dupont', 'Lambert', 'Fontaine', 'Rousseau', 'Vincent', 'Muller', 'Lefevre', 'Faure', 'André', 'Mercier', 'Blanc', 'Guerin', 'Boyer', 'Garnier', 'Chevalier', 'Francois', 'Legrand', 'Gauthier', 'Garcia', 'Perrin', 'Robin', 'Clement', 'Morin', 'Nicolas', 'Henry', 'Roussel', 'Mathieu', 'Gautier', 'Masson'];
    final public const RANDOM_FIRST_NAMES = ['Jean', 'Marie', 'Pierre', 'Jeanne', 'Michel', 'Françoise', 'André', 'Monique', 'Philippe', 'Catherine', 'René', 'Nathalie', 'Louis', 'Isabelle', 'Alain', 'Jacqueline', 'Jacques', 'Anne', 'Bernard', 'Sylvie', 'Marcel', 'Martine', 'Daniel', 'Madeleine', 'Roger', 'Nicole', 'Robert', 'Suzanne', 'Paul', 'Hélène', 'Claude', 'Christine', 'Christian', 'Marguerite', 'Henri', 'Denise', 'Georges', 'Louise', 'Nicolas', 'Christiane', 'François', 'Yvonne', 'Patrick', 'Valérie', 'Gérard', 'Sophie', 'Christophe', 'Sandrine', 'Joseph', 'Stéphanie'];
    final public const RANDOM_PHONE_NUMBERS = ['+33 7 67 95 09 67', '+33 8 37 05 45 08', '08 19 24 23 39', '05 85 23 79 02', '01 35 27 02 21', '0325761537', '0953265131', '+33 (0)1 99 25 38 08', '04 78 80 27 83', '0166075560', '0494693562', '+33 1 30 84 18 17', '+33 6 88 41 11 01', '06 74 89 92 72', '02 47 43 40 26', '+33 5 83 81 02 88', '01 74 01 52 70', '+33 6 29 45 50 91', '+33 (0)5 61 19 45 70', '0622228797', '01 41 70 16 89', '+33 4 01 02 89 33', '+33 (0)1 78 59 47 19', '+33 6 46 96 76 98', '0847241383', '+33 7 67 95 09 67', '+33 8 37 05 45 08', '08 19 24 23 39', '05 85 23 79 02', '01 35 27 02 21', '0325761537', '0953265131', '+33 (0)1 99 25 38 08', '04 78 80 27 83', '0166075560', '0494693562', '+33 1 30 84 18 17', '+33 6 88 41 11 01', '06 74 89 92 72', '02 47 43 40 26', '+33 5 83 81 02 88', '01 74 01 52 70', '+33 6 29 45 50 91', '+33 (0)5 61 19 45 70', '0622228797', '01 41 70 16 89', '+33 4 01 02 89 33', '+33 (0)1 78 59 47 19', '+33 6 46 96 76 98', '0847241383'];
    final public const ANONYMIZED_SUBJECT = 'Titre anonymisé';
    final public const ANONYMIZED_CONTENT = 'Ce contenu a été anonymisé pour protéger les données personnelles qui pourraient être présentes';
    final public const RANDOM_CITIES = ['Lyon', 'Rennes', 'Strasbourg', 'Lille', 'Paris', 'Rouen', 'Bordeaux', 'Montpellier', 'Nantes', 'Marseille'];
    final public const RANDOM_STREET_NAME = ['du Chapitre', 'des Lilas', 'Victor Hugo', 'Jean Moulin', 'Rosa Parks', 'Simone Veil', 'Pierre et Marie Curie', 'Simone de Beauvoir', 'Georgette Agutte'];

    /**
     * @throws \Exception
     */
    public static function generateRandomLastName(): string
    {
        return self::RANDOM_LAST_NAMES[random_int(0, 49)];
    }

    /**
     * @throws \Exception
     */
    public static function generateRandomFirstName(): string
    {
        return self::RANDOM_FIRST_NAMES[random_int(0, 49)];
    }

    /**
     * @throws \Exception
     */
    public static function generateRandomEmail(string $lastName, ?string $firstName): string
    {
        return mb_strtolower(sprintf('%s.%s%d@yopmail.com', $firstName, $lastName, random_int(0, 999999)));
    }

    /**
     * @throws \Exception
     */
    public static function generateRandomPhoneNumber(): string
    {
        return self::RANDOM_PHONE_NUMBERS[random_int(0, 24)];
    }

    /**
     * @throws \Exception
     */
    public static function generateRandomAddress(): string
    {
        return sprintf('%d rue %s, %s', random_int(1, 60), self::RANDOM_STREET_NAME[random_int(0, 8)], self::RANDOM_CITIES[random_int(0, 9)]);
    }

    public static function generateUsername(User $user, string $firstname, string $lastname, string $birthDate): string
    {
        $username = $user->isBeneficiaire() ? sprintf('%s.%s.%s', $firstname, $lastname, $birthDate) : sprintf('%s.%s', $lastname, $firstname);

        return strtolower(sprintf('%s(%d)', $username, $user->getId()));
    }
}
