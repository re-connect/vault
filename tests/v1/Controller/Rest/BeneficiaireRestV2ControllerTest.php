<?php

namespace App\Tests\v1\Controller\Rest;

use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\Client;
use App\Entity\ClientBeneficiaire;
use App\Entity\Document;
use App\Entity\Membre;
use App\Provider\CentreProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BeneficiaireRestV2ControllerTest extends AbstractControllerTest
{
    public const CONTACT_ENTITY_NAME = 'contacts';
    public const NOTE_ENTITY_NAME = 'notes';
    public const EVENEMENT_ENTITY_NAME = 'events';
    public const DOCUMENT_ENTITY_NAME = 'documents';
    public const DOSSIER_ENTITY_NAME = 'folders';

    public function testGetMine()
    {
        $this->loginAsMember();

        $this->client->request(Request::METHOD_GET, $this->generateUrl('beneficiaries/mine'));
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(200);

        $beneficiaries = json_decode($response->getContent(), true);
        $beneficiary = $beneficiaries[0];

        $this->assertArrayHasKey('id', $beneficiary);
        $this->assertArrayHasKey('date_naissance', $beneficiary);
        $this->assertArrayHasKey('total_file_size', $beneficiary);
        $this->assertArrayHasKey('created_at', $beneficiary);
        $this->assertArrayHasKey('updated_at', $beneficiary);
        $this->assertArrayHasKey('centres', $beneficiary);
        $this->assertArrayHasKey('question_secrete', $beneficiary);
        $this->assertArrayHasKey('user', $beneficiary);
    }

    public function testAddExternalLink()
    {
        $member = $this->loginAsMember();
        /**
         * le centre existe / le lien externe existe pour ce bénéficiaire.
         */
        $em = $this->getEntityManager();
        $centreProvider = self::getContainer()->get(CentreProvider::class);
        /** @var Beneficiaire $beneficiaire */
        $beneficiaires = $centreProvider->getBeneficiairesFromMembre($member);
        $beneficiaire = $beneficiaires[0];
        $centre = $beneficiaire->getCentres()[0];
        $client = $em->getRepository(Client::class)->findOneBy(['nom' => 'applimobile']);
        $centres = $em->getRepository(Centre::class)->findByClientIdentifier($client->getRandomId());

        $centre2 = $centres[1];
        $centre3 = $centres[2];
        $centre4 = $centres[3];
        $centre5 = $centres[4];
        $centre6 = $centres[5];

        $beneficiaireCentre = new BeneficiaireCentre();
        $beneficiaireCentre
            ->setBeneficiaire($beneficiaire)
            ->setCentre($centre)
            ->setBValid(true);

        $em->persist($beneficiaireCentre);
        $em->flush();

        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
            [
                'distant_id' => 1,
                'center_id' => $centre->getId(),
                'center_distant_id' => $centre->getId(),
                'membre_distant_id' => 1234,
            ]
        );

        $this->assertResponseStatusCodeSame(400);

        /* le centre n'existe pas / le lien externe n'existe pas */
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
            [
                'distant_id' => 1234,
                'center_distant_id' => $centre2->getId(),
                'membre_distant_id' => 1234,
            ]
        );

        $this->assertResponseStatusCodeSame(400);
        $client = $em->getRepository(Client::class)->findOneBy(['nom' => 'applimobile']);
        $beneficiaire = $em->getRepository(Beneficiaire::class)->findByClientIdentifier($client->getRandomId())[0];

        /* le centre n'existe pas / le lien externe existe pour ce bénéficiaire */
        $externalLink = new ClientBeneficiaire();
        $externalLink->setClient($client);
        $externalLink->setDistantId(1235);
        $beneficiaire->addExternalLink($externalLink);
        $em->persist($beneficiaire);
        $em->flush();

        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
            [
                'distant_id' => 1235,
                'center_distant_id' => 9999999999999999,
                'membre_distant_id' => 1234,
            ]
        );

        $this->assertResponseStatusCodeSame(404);

        /* le centre n'existe pas / le lien externe existe pour un autre bénéficiaire */
//        $this->client->request(Request::METHOD_PATCH, $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
//            [
//                'distant_id' => 2,
//                'center_distant_id' => $centre4->getId(),
//                'membre_distant_id' => 1234
//            ]);
//
//        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
//        $content = $this->client->getResponse()->getContent();
//        $this->assertJson($content);
//
//        $content = json_decode($content, true);
//
//        $this->assertArrayHasKey('error', $content);
//        $this->assertArrayHasKey('message', $content['error']);
//        $this->assertArrayHasKey('status', $content['error']);
//        $this->assertArrayHasKey('code', $content['error']);
//        $this->assertArrayHasKey('details', $content['error']);
//
//        $this->assertEquals('There was a validation error', $content['error']['message']);
//        $this->assertEquals(400, $content['error']['status']);
//        $this->assertEquals('validation_error', $content['error']['code']);

        /* le centre existe / le lien externe n'existe pas */
        $centre = $em->getRepository(Centre::class)->findByClientIdentifier($client->getRandomId())[0];

        $beneficiaireCentre = new BeneficiaireCentre();
        $beneficiaireCentre
            ->setBeneficiaire($beneficiaire)
            ->setCentre($centre)
            ->setBValid(true);

        $em->persist($beneficiaireCentre);
        $em->flush();

        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
            [
                'distant_id' => 1236,
                'center_distant_id' => $centre5->getId(),
                'membre_distant_id' => 1234,
            ]
        );

        $this->assertResponseStatusCodeSame(400);

        /* le centre existe / le lien externe existe pour un autre bénéficiaire */
        $centre = $em->getRepository(Centre::class)->findAll()[0];

        $beneficiaireCentre = new BeneficiaireCentre();
        $beneficiaireCentre
            ->setBeneficiaire($beneficiaire)
            ->setCentre($centre)
            ->setBValid(true);

        $em->persist($beneficiaireCentre);
        $em->flush();

        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
            [
                'distant_id' => 2,
                'center_distant_id' => $centre6->getId(),
                'membre_distant_id' => 1234,
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
        $this->assertArrayHasKey('details', $content['error']);

        $this->assertEquals('There was a validation error', $content['error']['message']);
        $this->assertEquals(400, $content['error']['status']);
        $this->assertEquals('validation_error', $content['error']['code']);

        /* le centre existe et à un lien externe et je veux lui donner un nouveau lien externe */
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
            [
                'distant_id' => 1237,
                'center_distant_id' => $centre5->getId(),
                'membre_distant_id' => 1234,
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
        $this->assertArrayHasKey('details', $content['error']);
        $this->assertArrayHasKey('center', $content['error']['details']);

        $this->assertEquals('There was a validation error', $content['error']['message']);
        $this->assertEquals(400, $content['error']['status']);
        $this->assertEquals('validation_error', $content['error']['code']);

        /* le centre existe mais le lien externe est déjà lié à un autre centre du beneficiaire */
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
            [
                'distant_id' => 2,
                'center_distant_id' => $centre5->getId(),
                'membre_distant_id' => 1234,
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
        $this->assertArrayHasKey('details', $content['error']);
        $this->assertArrayHasKey('center', $content['error']['details']);

        $this->assertEquals('There was a validation error', $content['error']['message']);
        $this->assertEquals(400, $content['error']['status']);
        $this->assertEquals('validation_error', $content['error']['code']);

        /* le centre n'existe pas mais le lien externe est déjà lié à un autre centre du beneficiaire */
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiaire->getId().'/add-external-link'),
            [
                'distant_id' => 2,
                'center_distant_id' => $centre6->getId(),
                'membre_distant_id' => 1234,
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content);

        $content = json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
        $this->assertArrayHasKey('details', $content['error']);

        $this->assertEquals('There was a validation error', $content['error']['message']);
        $this->assertEquals(400, $content['error']['status']);
        $this->assertEquals('validation_error', $content['error']['code']);
    }

    public function testReconnectPro()
    {
        $em = $this->getEntityManager();
        $client = $this->loginAsClient('axel');
        $center = $em->getRepository(Centre::class)->findByClientIdentifier($client->getIdentifier())[0];
        $beneficiaries = $em->getRepository(Beneficiaire::class)->findByClientIdentifier($client->getIdentifier());
        $beneficiaryId = $beneficiaries[0]->getId();

        $beneficiaire = [
            'date_naissance' => '31/01/1975',
            'prenom' => 'tomas',
            'nom' => 'durand',
            'email' => 'tomas.durand@mail.com',
        ];

        /*
         * retourne une erreur sur le distant id déjà existant
         */
        $this->client->request(
            Request::METHOD_POST,
            $this->generateUrl('beneficiaries'),
            [
                'distant_id' => $beneficiaryId,
                'date_naissance' => $beneficiaire['date_naissance'],
                'prenom' => $beneficiaire['prenom'],
                'nom' => $beneficiaire['nom'],
                'email' => $beneficiaire['email'],
                'member_distant_id' => 1,
                'center_distant_id' => $center->getId(),
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);

        /*
         * retourne une erreur sur le centre
         */
        $this->client->request(
            Request::METHOD_POST,
            $this->generateUrl('beneficiaries'),
            [
                'distant_id' => 123,
                'date_naissance' => $beneficiaire['date_naissance'],
                'prenom' => $beneficiaire['prenom'],
                'nom' => $beneficiaire['nom'],
                'email' => $beneficiaire['email'],
                'member_distant_id' => 1,
                'center_distant_id' => 9999999,
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);

        /*
         * retourne une erreur sur la date de naissance
         */
        $this->client->request(
            Request::METHOD_POST,
            $this->generateUrl('beneficiaries'),
            [
                'distant_id' => 123,
                'date_naissance' => '01-30-1989',
                'prenom' => $beneficiaire['prenom'],
                'nom' => $beneficiaire['nom'],
                'email' => $beneficiaire['email'],
                'member_distant_id' => 1,
                'center_distant_id' => $center->getId(),
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);

        $center = $em->getRepository(Centre::class)->findByClientIdentifier($client->getIdentifier())[0];
        $beneficiary = $em->getRepository(Beneficiaire::class)->findByClientIdentifier($client->getIdentifier())[0];
        $member = $em->getRepository(Membre::class)->findByClientIdentifier($client->getIdentifier())[0];

        /*
         * retourne une erreur sur le mail, prenom, et nom
         */
        $this->client->request(
            Request::METHOD_POST,
            $this->generateUrl('beneficiaries'),
            [
                'distant_id' => $beneficiary->getId(),
                'date_naissance' => '01/30/1989',
                'prenom' => 'prenom1',
                'nom' => 'nom1',
                'email' => 'wrong mail',
                'telephone' => 'wrong number',
                'member_distant_id' => $member->getId(),
                'center_distant_id' => $center->getId(),
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('details', $content['error']);
        $this->assertArrayHasKey('email', $content['error']['details']);

        $this->client->request(Request::METHOD_GET, $this->generateUrl('beneficiaries/'.$beneficiary->getId()));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('id', $content);
        $this->assertArrayHasKey('user', $content);
        $this->assertArrayHasKey('date_naissance', $content);

        /*
         * Je crée un dossier
         */
        $this->client->request(Request::METHOD_POST, $this->generateUrl('beneficiaries/'.$beneficiary->getId().'/folders'));
        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $dossier = $this->assertIsEntity(self::DOSSIER_ENTITY_NAME, $response);

        // Création manuelle d'un document, on a pas géré le fait de faire une upload directement à cause de la connexion S3 pas mockée
        $document = (new Document())
            ->setBeneficiaire($beneficiary)
            ->setNom('nom')
            ->setTaille(12)
            ->setObjectKey('KEY')
            ->setThumbnailKey('ThumbKEY')
            ->setExtension('pdf');
        $em = $this->getEntityManager();
        $em->persist($document);
        $em->flush();

        // Je consulte le document
        $this->getDocument($document->getId());

        /*
         * Je renomme le document
         */
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('documents/'.$document->getId()),
            [
                'nom' => 'new name',
            ]
        );
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = json_decode($response->getContent(), 1);
        $this->assertEquals('new name.pdf', $content['nom']);

        /*
         * Je déplace le document dans un dossier non existant
         */
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('documents/'.$document->getId()),
            [
                'folder_id' => 99999,
            ]
        );
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        /*
         * Je déplace le document dans un dossier existant
         */
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('documents/'.$document->getId()),
            [
                'folder_id' => $dossier['id'],
            ]
        );
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $document = $this->assertIsEntity(self::DOCUMENT_ENTITY_NAME, $response);
        $this->assertEquals($dossier['id'], $document['folder_id']);

        /*
         * Passer le document en privé
         */
        $this->toggleAccess($document['id'], self::DOCUMENT_ENTITY_NAME);

        $note = $this->addNote($beneficiary->getId());

        /**
         * Je consulte une note.
         */
        $note = $this->getNote($note['id']);

        /*
         * Je modifie une note
         */
        $this->client->request(
            Request::METHOD_PUT,
            $this->generateUrl('notes/'.$note['id']),
            [
                'nom' => 'new name',
                'contenu' => 'new contenu',
            ]
        );
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        /*
         * Passer la note en privé
         */
        $this->toggleAccess($note['id'], self::NOTE_ENTITY_NAME);

        /**
         * Create a note and delete by api.
         */
        $note = $this->addNote($beneficiary->getId());

        $this->deleteEntity($note['id'], self::NOTE_ENTITY_NAME);

        /**
         * je crée un contact.
         */
        $contact = $this->addContact($beneficiary->getId());

        /**
         * Je consulte un contact.
         */
        $contact = $this->getContact($contact['id']);

        /*
         * je modifie un contact
         */
        $this->client->request(Request::METHOD_PUT, $this->generateUrl('contacts/'.$contact['id']), [
            'nom' => 'new name',
            'prenom' => 'new firstname',
            'telephone' => '+33611111111',
            'email' => 'newmail@mail.com',
            'commentaire' => 'new commentaire',
            'association' => 'new association',
        ]);
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = $this->assertIsEntity(self::CONTACT_ENTITY_NAME, $response);

        $this->assertEquals('new name', $content['nom']);
        $this->assertEquals('new firstname', $content['prenom']);
        $this->assertEquals('+33611111111', $content['telephone']);
        $this->assertEquals('newmail@mail.com', $content['email']);
        $this->assertEquals('new commentaire', $content['commentaire']);
        $this->assertEquals('new association', $content['association']);

        /*
         * Passer le contact en privé
         */
        $this->toggleAccess($contact['id'], self::CONTACT_ENTITY_NAME);

        /**
         * Create a contact and delete by api.
         */
        $contact = $this->addContact($beneficiary->getId());

        $this->deleteEntity($contact['id'], self::CONTACT_ENTITY_NAME);

        /**
         * je crée un evenement.
         */
        $evenement = $this->addEvenement($beneficiary->getId());

        /**
         * Je consulte un evenement.
         */
        $evenement = $this->getEvenement($evenement['id']);

        /**
         * je modifie un evenement.
         */
        $date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $date->modify('+2 day');
        $editDate = $date->format('Y-m-d H:i:s');

        $this->client->request(Request::METHOD_PUT, $this->generateUrl('events/'.$evenement['id']), [
            'nom' => 'new name',
            'date' => $editDate,
            'lieu' => 'new lieu',
            'commentaire' => 'new commentaire',
        ]);
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = json_decode($response->getContent(), 1);

        $this->assertEquals('new name', $content['nom']);
        $this->assertEquals($date->format('c'), $content['date']);
        $this->assertEquals('new lieu', $content['lieu']);
        $this->assertEquals('new commentaire', $content['commentaire']);

        /*
         * Passer l'evenement en privé
         */
        $this->toggleAccess($evenement['id'], self::EVENEMENT_ENTITY_NAME);

        /**
         * Create an event and delete by api.
         */
        $evenement = $this->addEvenement($beneficiary->getId());

        $this->deleteEntity($evenement['id'], self::EVENEMENT_ENTITY_NAME);
    }

    private function addBeneficiaire()
    {
        $em = $this->getEntityManager();
        $center = $em->getRepository(Centre::class)->findAll()[0];

        $this->client->request(
            Request::METHOD_POST,
            $this->generateUrl('beneficiaries'),
            [
                'distant_id' => 123,
                'date_naissance' => '31/01/1975',
                'prenom' => 'pierre',
                'nom' => 'dupont',
                'email' => 'pierre.dupont2@mail.com',
                'member_distant_id' => 1,
                'center_distant_id' => $center->getId(),
            ]
        );

        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('distant_id', $content);
        $this->assertArrayHasKey('prenom', $content);
        $this->assertArrayHasKey('nom', $content);
        $this->assertArrayHasKey('username', $content);
        $this->assertArrayHasKey('email', $content);
        $this->assertArrayHasKey('date_naissance', $content);
        $this->assertArrayHasKey('telephone', $content);

        return $content;
    }

    private function assertIsEntity($entityName, $response)
    {
        $content = json_decode($response->getContent(), 1);

        $this->assertArrayHasKey('id', $content);
        $this->assertArrayHasKey('b_prive', $content);
        $this->assertArrayHasKey('nom', $content);
        $this->assertArrayHasKey('created_at', $content);
        $this->assertArrayHasKey('updated_at', $content);
        $this->assertArrayHasKey('beneficiaire_id', $content);

        switch ($entityName) {
            case self::DOCUMENT_ENTITY_NAME:
                $this->assertArrayHasKey('url', $content);
                $this->assertArrayHasKey('thumb', $content);
                $this->assertArrayHasKey('delete_url', $content);
                $this->assertArrayHasKey('rename_url', $content);
                $this->assertArrayHasKey('toggle_access_url', $content);
                $this->assertArrayHasKey('is_folder', $content);
                $this->assertArrayHasKey('extension', $content);
                $this->assertArrayHasKey('folder_id', $content);
                $this->assertArrayHasKey('beneficiaire', $content);
                $this->assertArrayHasKey('depose_par_full_name', $content);
                $this->assertArrayHasKey('object_key', $content);
                $this->assertArrayHasKey('thumbnail_key', $content);
                break;
            case self::DOSSIER_ENTITY_NAME:
                $this->assertArrayHasKey('documents', $content);
                $this->assertArrayHasKey('dossier_image', $content);
                $this->assertArrayHasKey('is_folder', $content);
                $this->assertArrayHasKey('beneficiaire', $content);
                break;
            case self::NOTE_ENTITY_NAME:
                $this->assertArrayHasKey('contenu', $content);
                break;
            case self::CONTACT_ENTITY_NAME:
                $this->assertArrayHasKey('prenom', $content);
                $this->assertArrayHasKey('email', $content);
                $this->assertArrayHasKey('commentaire', $content);
                $this->assertArrayHasKey('association', $content);
                $this->assertArrayHasKey('telephone', $content);
                break;
            case self::EVENEMENT_ENTITY_NAME:
                $this->assertArrayHasKey('dateToString', $content);
                $this->assertArrayHasKey('archive', $content);
                $this->assertArrayHasKey('date', $content);
                $this->assertArrayHasKey('lieu', $content);
                $this->assertArrayHasKey('commentaire', $content);
                $this->assertArrayHasKey('rappels', $content);
                break;
        }

        return $content;
    }

    private function uploadDocument($id)
    {
        $imagePath = 'tests/test-file.pdf';
        $imageNames = explode('/', $imagePath);
        $imageName = end($imageNames);
        $file = new UploadedFile($imagePath, $imageName, 'application/pdf', 123);

        $this->client->request(Request::METHOD_POST, $this->generateUrl("beneficiaries/$id/documents"), [], ['files' => $file]);

        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $document = json_decode($response->getContent(), 1);
        $document = $document[0];

        $this->assertArrayHasKey('id', $document);
        $this->assertArrayHasKey('b_prive', $document);
        $this->assertArrayHasKey('nom', $document);
        $this->assertArrayHasKey('created_at', $document);
        $this->assertArrayHasKey('updated_at', $document);
        $this->assertArrayHasKey('url', $document);
        $this->assertArrayHasKey('thumb', $document);
        $this->assertArrayHasKey('delete_url', $document);
        $this->assertArrayHasKey('rename_url', $document);
        $this->assertArrayHasKey('toggle_access_url', $document);
        $this->assertArrayHasKey('is_folder', $document);
        $this->assertArrayHasKey('extension', $document);
        $this->assertArrayHasKey('folder_id', $document);
        $this->assertArrayHasKey('beneficiaire', $document);
        $this->assertArrayHasKey('depose_par_full_name', $document);
        $this->assertArrayHasKey('beneficiaire_id', $document);
        $this->assertArrayHasKey('object_key', $document);
        $this->assertArrayHasKey('thumbnail_key', $document);

        return $document;
    }

    private function getDocument($id)
    {
        $this->client->request(Request::METHOD_GET, $this->generateUrl('documents/'.$id));
        $response = $this->client->getResponse();

        $this->assertResponseRedirects();
    }

    private function toggleAccess($id, $entityName)
    {
        $this->client->request(Request::METHOD_PATCH, $this->generateUrl("$entityName/$id/toggle-access"));
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        if (self::DOCUMENT_ENTITY_NAME === $entityName) {
            $this->assertIsEntity($entityName, $response);
        }
    }

    private function deleteEntity($id, $entityName)
    {
        $this->client->request(Request::METHOD_DELETE, $this->generateUrl("$entityName/$id"));

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    private function addNote($beneficiaireId)
    {
        $this->client->request(Request::METHOD_POST, $this->generateUrl("beneficiaries/$beneficiaireId/notes"), [
            'nom' => 'note 1',
            'contenu' => 'contenu',
        ]);
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return $this->assertIsEntity(self::NOTE_ENTITY_NAME, $response);
    }

    private function getNote($id)
    {
        $this->client->request(Request::METHOD_GET, $this->generateUrl('notes/'.$id));
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        return $this->assertIsEntity(self::NOTE_ENTITY_NAME, $response);
    }

    private function addContact($beneficiaireId)
    {
        $this->client->request(Request::METHOD_POST, $this->generateUrl("beneficiaries/$beneficiaireId/contacts"), [
            'nom' => 'nom',
            'prenom' => 'prenom',
            'telephone' => '+33611111111',
            'email' => 'mail@mail.com',
            'commentaire' => 'commentaire',
            'association' => 'association',
        ]);
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $content = json_decode($response->getContent(), 1);

        $this->assertArrayHasKey('id', $content);
        $this->assertArrayHasKey('b_prive', $content);
        $this->assertArrayHasKey('nom', $content);
        $this->assertArrayHasKey('created_at', $content);
        $this->assertArrayHasKey('updated_at', $content);
        $this->assertArrayHasKey('prenom', $content);
        $this->assertArrayHasKey('email', $content);
        $this->assertArrayHasKey('commentaire', $content);
        $this->assertArrayHasKey('association', $content);
        $this->assertArrayHasKey('telephone', $content);
        $this->assertArrayHasKey('beneficiaire_id', $content);

        return $content;
    }

    private function getContact($id)
    {
        $this->client->request(Request::METHOD_GET, $this->generateUrl('contacts/'.$id));
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        return $this->assertIsEntity(self::CONTACT_ENTITY_NAME, $response);
    }

    private function addEvenement($beneficiaireId)
    {
        $date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $date->modify('+1 day');

        $this->client->request(Request::METHOD_POST, $this->generateUrl("beneficiaries/$beneficiaireId/events"), [
            'nom' => 'nom',
            'date' => $date->format('Y-m-d H:i:s'),
            'lieu' => 'lieu',
            'commentaire' => 'commentaire',
        ]);
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $content = $this->assertIsEntity(self::EVENEMENT_ENTITY_NAME, $response);

        $this->assertEquals('nom', $content['nom']);
        $this->assertEquals($date->format('c'), $content['date']);
        $this->assertEquals('lieu', $content['lieu']);
        $this->assertEquals('commentaire', $content['commentaire']);

        return $content;
    }

    private function getEvenement($id)
    {
        $this->client->request(Request::METHOD_GET, $this->generateUrl('events/'.$id));
        $response = $this->client->getResponse();

        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        return $this->assertIsEntity(self::EVENEMENT_ENTITY_NAME, $response);
    }

    public function testAffiliateABeneficiary()
    {
        $em = $this->getEntityManager();
        $member = $this->loginAsMember();
        $client = $em->getRepository(Client::class)->findOneBy(['nom' => 'applimobile']);
        $beneficiary = $em->getRepository(Beneficiaire::class)->findByClientIdentifier($client->getRandomId())[0];
        $center = $em->getRepository(Centre::class)->findByClientIdentifier($client->getRandomId())[0];

        foreach ($beneficiary->getExternalLinks() as $externalLink) {
            $em->remove($externalLink);
            $em->flush();
        }

        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaries/'.$beneficiary->getId().'/add-external-link'),
            [
                'distant_id' => $beneficiary->getId(),
                'centre_id' => $center->getId(),
                'center_distant_id' => $center->getId(),
                'membre_distant_id' => $member->getId(),
            ]
        );

        $this->assertResponseStatusCodeSame(200);

        /**
         * J'active le bénéficiaire pour le centre.
         */
        $beneficiaireCentre = $beneficiary->getBeneficiairesCentres()[0];
        $beneficiaireCentre->setBValid(true);
        $this->getEntityManager()->flush();

        /*
         * le beneficiaire est accessible
         */
        $this->client->request(Request::METHOD_GET, $this->generateUrl('beneficiaries/'.$beneficiary->getId()));
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

//        $document = $this->uploadDocument($beneficiary->$this->getId());
//        $document = $this->getDocument($document->getId());
//        $this->deleteEntity($document['id'], self::DOCUMENT_ENTITY_NAME);

        $note = $this->addNote($beneficiary->getId());
        $note = $this->getNote($note['id']);
        $this->deleteEntity($note['id'], self::NOTE_ENTITY_NAME);

        $contact = $this->addContact($beneficiary->getId());
        $contact = $this->getContact($contact['id']);
        $this->deleteEntity($contact['id'], self::CONTACT_ENTITY_NAME);

        $evenement = $this->addEvenement($beneficiary->getId());
        $evenement = $this->getEvenement($evenement['id']);
        $this->deleteEntity($evenement['id'], self::EVENEMENT_ENTITY_NAME);
    }

    private function forbiddenResponseTest()
    {
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $content);
    }
}
