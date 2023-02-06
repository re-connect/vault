<?php

namespace App\Tests\v1\Controller\Rest;

use App\Entity\Centre;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class APIRestControllerTest extends AbstractControllerTest
{
    protected string $baseUrl = '/appli/rosalie/';
    private int $distantId = 999999999;
    private array $beneficiaire;
    private Client $oauthClient;

    protected static function ensureKernelShutdown()
    {
        if (null !== static::$kernel) {
            static::$kernel->shutdown();
        }
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->oauthClient = $this->loginAsClient('rosalie');
        $this->beneficiaire = [
            'nom' => 'duchossoy-test',
            'prenom' => 'mathias-test',
            'email' => 'mathias.duchossoy-test@reconnect.fr',
            'dateNaissance' => '06/10/1989',
            'telephone' => '+33612345678',
            'distant_id' => $this->distantId,
        ];
    }

    public function testGlobalWithoutErrors(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            $this->generateUrl('beneficiaire'),
            $this->beneficiaire
        );

        $response = $this->client->getResponse();

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($response->getContent());

        /*
         * Récupération du bénéficiaire
         */
        $this->client->request(Request::METHOD_GET, $this->generateUrl('beneficiaire/'.$this->distantId));
        $response = $this->client->getResponse();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($response->getContent());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        /**
         * Vérifier si le bénéficiaire existe.
         */
        $usernameEncode = urlencode('mathiastest.duchossoytest.06/10/1989');

        $this->client->request(
            'GET',
            $this->generateUrl('beneficiaire/'.$usernameEncode)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        /*
         * Edition du bénéficiaire
         */
        $this->beneficiaire['nom'] = 'duchossoy-test-edit';

        $this->client->request(
            Request::METHOD_PUT,
            $this->generateUrl('beneficiaire/'.$this->distantId),
            $this->beneficiaire
        );

        $response = $this->client->getResponse();

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $jsonResponseContent = $response->getContent();
        $this->assertJson($jsonResponseContent);
        $content = \json_decode($jsonResponseContent, true);

        $this->assertArrayHasKey('idRosalie', $content);
        $this->assertEquals(999999999, $content['idRosalie']);
        $this->assertArrayHasKey('nom', $content);
        $this->assertEquals('duchossoy-test-edit', $content['nom']);
        $this->assertArrayHasKey('prenom', $content);
        $this->assertEquals('mathias-test', $content['prenom']);
        $this->assertArrayHasKey('username', $content);
        $this->assertEquals('mathiastest.duchossoytestedit.06/10/1989', $content['username']);
        $this->assertArrayHasKey('email', $content);
        $this->assertEquals('mathias.duchossoy-test@reconnect.fr', $content['email']);
        $this->assertArrayHasKey('dateDeNaissance', $content);
        $this->assertEquals((new \DateTime('1989-10-06T00:00:00+0000'))->format('Ymd'), (new \DateTime($content['dateDeNaissance']))->format('Ymd'));
        $this->assertArrayHasKey('telephone', $content);
        $this->assertEquals('+33612345678', $content['telephone']);
        $this->assertArrayHasKey('distant_id', $content);
        $this->assertEquals(999999999, $content['distant_id']);

        /*
         * tests avec erreurs pour l'édition des bénéficiaires
         *
         * invalid name
         */
        $this->beneficiaire['nom'] = 'duchossoy123';
        $this->beneficiaire['prenom'] = 'mathias123';
        $this->beneficiaire['email'] = 'wrongEmail';
        $this->beneficiaire['telephone'] = 'wrongTelephone';
        $this->beneficiaire['dateNaissance'] = 'wrongDateNaissance';

        $this->client->request(
            Request::METHOD_PUT,
            $this->generateUrl('beneficiaire/'.$this->distantId),
            $this->beneficiaire
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $content = $response->getContent();
        $this->assertJson($content);

        $content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
        $this->assertArrayHasKey('details', $content['error']);
        $this->assertArrayHasKey('dateNaissance', $content['error']['details']);
//        $this->assertArrayHasKey('prenom', $content['error']['details']);
//        $this->assertArrayHasKey('nom', $content['error']['details']);
//        $this->assertArrayHasKey('email', $content['error']['details']);
//        $this->assertArrayHasKey('telephone', $content['error']['details']);
//
//        /**
//         * call throw with wrong client name existant
//         */
//        $this->client->request(Request::METHOD_PUT,
//            '/appli/axel/beneficiaire/'.$this->distantId.'?access_token='.$this->accessToken,
//            $this->beneficiaire
//        );
//
//        $response = $this->client->getResponse();
//
//        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
//        $this->assertResponseHeaderSame('Content-Type', 'application/json');
//        $this->assertJson($response->getContent());
//        $content = $response->getContent();
//        $this->assertJson($content);
//
//        $content = json_decode($content, true);
//
//        $this->assertArrayHasKey('error', $content);
//        $this->assertArrayHasKey('message', $content['error']);
//        $this->assertArrayHasKey('status', $content['error']);
//        $this->assertArrayHasKey('code', $content['error']);
//
//        $this->assertEquals('Le token fourni ne correspond pas à votre compte. Veuillez contacter Reconnect', $content['error']['message']);
//        $this->assertEquals(400, $content['error']['status']);
        $firstCentre = $this->getEntityManager()->getRepository(Centre::class)->findByClientIdentifier($this->oauthClient->getIdentifier())[0];

        /*
         * Tests de liaison à un centre
         */
        // 43 	CHRS Reconnect
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaire/'.$this->distantId.'/centre/'.$firstCentre->getId().'/link'),
            $this->beneficiaire
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($response->getContent());
        $content = $response->getContent();
        $this->assertJson($content);

        $content = \json_decode($content, true);

        $this->assertArrayHasKey('idRosalie', $content);
        $this->assertArrayHasKey('nom', $content);
        $this->assertArrayHasKey('prenom', $content);
        $this->assertArrayHasKey('username', $content);
        $this->assertArrayHasKey('email', $content);
        $this->assertArrayHasKey('dateDeNaissance', $content);
        $this->assertArrayHasKey('telephone', $content);
        $this->assertArrayHasKey('distant_id', $content);

        $this->assertEquals(999999999, $content['idRosalie']);
        $this->assertEquals('duchossoy-test-edit', $content['nom']);
        $this->assertEquals('mathias-test', $content['prenom']);
        $this->assertEquals('mathiastest.duchossoytestedit.06/10/1989', $content['username']);
        $this->assertEquals('mathias.duchossoy-test@reconnect.fr', $content['email']);
        $this->assertEquals((new \DateTime('1989-10-06T00:00:00+0000'))->format('Ymd'), (new \DateTime($content['dateDeNaissance']))->format('Ymd'));
        $this->assertEquals('+33612345678', $content['telephone']);
        $this->assertEquals(999999999, $content['distant_id']);

        // twrow Already associated.
        $this->client->request(
            Request::METHOD_PATCH,
            $this->generateUrl('beneficiaire/'.$this->distantId.'/centre/'.$firstCentre->getId().'/link'),
            $this->beneficiaire
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_ACCEPTABLE);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $content = $response->getContent();
        $this->assertJson($content);

        $content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);

        /*
         * Suppression du bénéficiaire
         */
        $this->client->request(
            Request::METHOD_DELETE,
            $this->generateUrl('beneficiaire/'.$this->distantId)
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $content = $response->getContent();
        $this->assertJson($content);
        $this->assertJsonStringEqualsJsonString('{"success":"Utilisateur supprim\u00e9"}', $response->getContent());

        $content = \json_decode($content, true);

        $this->assertArrayHasKey('success', $content);
    }

    public function testCreateBeneficiaireWithErrors()
    {
        // throw error: missing distant id
        unset($this->beneficiaire['distant_id'], $this->beneficiaire['idRosalie']);

        $this->client->request(
            Request::METHOD_POST,
            $this->generateUrl('beneficiaire'),
            $this->beneficiaire
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $content = $response->getContent();
        $this->assertJson($content);
        $content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);

        $this->assertEquals('Missing distant id.', $content['error']['message']);
        $this->assertEquals(400, $content['error']['status']);
        $this->assertEquals('bad_request', $content['error']['code']);

        // validation data errors
        $this->beneficiaire['distant_id'] = '999999999';
        $this->beneficiaire['nom'] = 'duchossoy123';
        $this->beneficiaire['prenom'] = 'mathias123';
        $this->beneficiaire['email'] = 'wrongEmail';
        $this->beneficiaire['telephone'] = 'wrongTelephone';
        $this->beneficiaire['dateNaissance'] = 'wrongDateNaissance';

        $this->client->request(
            Request::METHOD_POST,
            $this->generateUrl('beneficiaire'),
            $this->beneficiaire
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $content = $response->getContent();
        $this->assertJson($content);
        $content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
        $this->assertArrayHasKey('details', $content['error']);
        $this->assertArrayHasKey('dateNaissance', $content['error']['details']);
//        $this->assertArrayHasKey('prenom', $content['error']['details']);
//        $this->assertArrayHasKey('nom', $content['error']['details']);
//        $this->assertArrayHasKey('email', $content['error']['details']);
//        $this->assertArrayHasKey('telephone', $content['error']['details']);
    }

    public function testGetBeneficiaireWithoutToken()
    {
        $this->client->request(Request::METHOD_GET, $this->baseUrl.'beneficiaire/'.$this->distantId);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUploadFile()
    {
        /** twrow error no beneficiary */
        $uploadFileUrl = $this->generateUrl('beneficiaire/9898989898/uploadFile');
        $this->client->request(Request::METHOD_POST, $uploadFileUrl);

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $response->getContent();
        $this->assertJson($content);
        $content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
    }

    public function testBeneficiaryNotExists()
    {
        // if not exists
        $username = 'notexistsmathias.duchossoy.06/10/1989';
        $usernameEncode = urlencode($username);
        $this->client->request(
            'GET',
            $this->generateUrl('beneficiaire/'.$usernameEncode)
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $response->getContent();
        $this->assertJson($content);
        $content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
    }

    public function testGetCentre()
    {
        $firstCentre = $this->getEntityManager()->getRepository(Centre::class)->findByClientIdentifier($this->oauthClient->getIdentifier())[0];

        $this->client->request(Request::METHOD_GET, $this->generateUrl('centre/'.$firstCentre->getId()));
        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($response->getContent());

        // no exist
        $this->client->request(
            Request::METHOD_GET,
            $this->generateUrl('centre/99999999')
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $response->getContent();
        $this->assertJson($content);
        $content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);
    }

    /**
     * Méthode : DELETE Paramètres GET :
     * access_token : votre token d’accès
     * idRosalie (obligatoire) : un entier représentant l’identifiant unique Rosalie
     * Retour : Un message de confirmation ou une erreur.
     */
    public function testDeleteBeneficiaireNonExistant()
    {
        $this->client->request(
            Request::METHOD_DELETE,
            $this->generateUrl('beneficiaire/9999999999')
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJson($response->getContent());

        $content = $response->getContent();
        $this->assertJson($content);
        $content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content['error']);
        $this->assertArrayHasKey('status', $content['error']);
        $this->assertArrayHasKey('code', $content['error']);

        $this->assertEquals('No beneficiary found for distant id 9999999999', $content['error']['message']);
        $this->assertEquals(404, $content['error']['status']);
        $this->assertEquals('entity_not_found', $content['error']['code']);
    }
}
