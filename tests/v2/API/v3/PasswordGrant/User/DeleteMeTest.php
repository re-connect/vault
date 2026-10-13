<?php

namespace App\Tests\v2\API\v3\PasswordGrant\User;

use App\DataFixtures\v2\BeneficiaryFixture;
use App\DataFixtures\v2\MemberFixture;
use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\ClientBeneficiaire;
use App\Entity\Contact;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Evenement;
use App\Entity\Note;
use App\Entity\Rappel;
use App\Entity\User;
use App\Tests\Factory\BeneficiaireFactory;
use App\Tests\Factory\ClientFactory;
use App\Tests\Factory\ContactFactory;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\EventFactory;
use App\Tests\Factory\FolderFactory;
use App\Tests\Factory\NoteFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\v2\API\AbstractPasswordGrantApiTestCase;
use Symfony\Component\HttpFoundation\Request;

class DeleteMeTest extends AbstractPasswordGrantApiTestCase
{
    public function testBeneficiaryDeletesOwnAccountWithAllTheirData(): void
    {
        $beneficiary = BeneficiaireFactory::findByEmail(BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_DELETE);
        $folder = FolderFactory::createOne(['beneficiaire' => $beneficiary]);
        $event = EventFactory::createOne(['beneficiaire' => $beneficiary]);
        $event->addRappel((new Rappel())->setDate(new \DateTime('+1 day')));
        $externalLink = (new ClientBeneficiaire(ClientFactory::find(['nom' => 'rosalie']), 'deleted-account-distant-id'))->setEntity($beneficiary);
        $this->em->persist($externalLink);
        $this->em->flush();

        $data = [
            User::class => [$beneficiary->getUser()->getId()],
            Beneficiaire::class => [$beneficiary->getId()],
            Document::class => [
                DocumentFactory::createOne(['beneficiaire' => $beneficiary])->getId(),
                DocumentFactory::createOne(['beneficiaire' => $beneficiary, 'dossier' => $folder])->getId(),
            ],
            Dossier::class => [$folder->getId()],
            Contact::class => [ContactFactory::createOne(['beneficiaire' => $beneficiary])->getId()],
            Note::class => [NoteFactory::createOne(['beneficiaire' => $beneficiary])->getId()],
            Evenement::class => [$event->getId()],
            Rappel::class => $event->getRappels()->map(fn (Rappel $reminder) => $reminder->getId())->toArray(),
            BeneficiaireCentre::class => $beneficiary->getBeneficiairesCentres()->map(fn (BeneficiaireCentre $relay) => $relay->getId())->toArray(),
        ];
        $rosalie = ClientFactory::find(['nom' => 'rosalie']);
        $externalLinkCriteria = ['distantId' => 'deleted-account-distant-id', 'client' => $rosalie->getId()];
        $this->assertNotNull($this->em->getRepository(ClientBeneficiaire::class)->findOneBy($externalLinkCriteria));

        $client = static::createClient();
        $this->loginAsUser($client, BeneficiaryFixture::BENEFICIARY_MAIL_SETTINGS_DELETE);
        $client->request(Request::METHOD_DELETE, $this->generateUrl('/users/me'));

        $this->assertResponseStatusCodeSame(204);
        $this->em->clear();
        foreach ($data as $class => $ids) {
            $this->assertNotEmpty($ids, sprintf('The test should create at least one %s', $class));
            foreach ($ids as $id) {
                $this->assertNull($this->em->find($class, $id), sprintf('%s #%d should have been deleted', $class, $id));
            }
        }
        $this->assertNull(
            $this->em->getRepository(ClientBeneficiaire::class)->findOneBy($externalLinkCriteria),
            'The external link should have been deleted',
        );

        // The access token of the deleted account can not be used anymore
        $client->request(Request::METHOD_GET, $this->generateUrl('/users/me'));
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMemberCanNotDeleteOwnAccount(): void
    {
        $user = UserFactory::findByEmail(MemberFixture::MEMBER_MAIL);

        $this->requestAsUser(MemberFixture::MEMBER_MAIL, Request::METHOD_DELETE, '/users/me');

        $this->assertResponseStatusCodeSame(403);
        $this->em->clear();
        $this->assertNotNull($this->em->find(User::class, $user->getId()));
    }
}
