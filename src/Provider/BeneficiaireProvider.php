<?php

namespace App\Provider;

use App\Api\Manager\ApiClientManager;
use App\Entity\Adresse;
use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\ClientBeneficiaire;
use App\Entity\CreatorCentre;
use App\Entity\CreatorClient;
use App\Entity\User;
use App\Event\BeneficiaireEvent;
use App\Event\REEvent;
use App\Manager\UserManager;
use App\OtherClasses\ErrorCode;
use App\Repository\ClientBeneficiaireRepository;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use App\Validator\Constraints\DateNaissance;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Query;
use League\Bundle\OAuth2ServerBundle\Security\User\NullUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BeneficiaireProvider
{
    private array $voterAttributes = [BeneficiaireVoter::GESTION_BENEFICIAIRE];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
        private readonly UserManager $userManager,
        private readonly RequestStack $requestStack,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CentreProvider $centreProvider,
        private readonly Security $security,
        private readonly ClientBeneficiaireRepository $clientBeneficiaireRepository,
        private readonly ApiClientManager $apiClientManager,
    ) {
    }

    public function getBeneficiairesFromIdWithBeneficiairesWithCentres($id)
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('b', 'u', 'bc', 'c')
            ->from('App:Beneficiaire', 'b')
            ->innerJoin('b.user', 'u')
            ->innerJoin('b.beneficiairesCentres', 'bc')
            ->innerJoin('bc.centre', 'c')
            ->where('b.id = '.$id)
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        if (null === $result || 0 === count($result)) {
            return null;
        }

        return $result[0];
    }

    public function getBeneficiairesFromId($id)
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('b', 'u', 'bc', 'c')
            ->from('App:Beneficiaire', 'b')
            ->innerJoin('b.user', 'u')
            ->join('b.beneficiairesCentres', 'bc')
            ->join('bc.centre', 'c')
            ->where('b.id = '.$id)
            ->getQuery()
            ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
            ->getResult();

        if (null === $result || 0 === count($result)) {
            return null;
        }

        return $result[0];
    }

    public function getEntityByDistantId($distantId, $secured = true, $accessAttribute = null): Beneficiaire
    {
        $client = $this->apiClientManager->getCurrentOldClient();
        /** @var Beneficiaire $entity */
        if (!$entity = $this->entityManager->getRepository(Beneficiaire::class)->findByDistantId($distantId, $client->getRandomId())) {
            throw new NotFoundHttpException('No beneficiary found for distant id '.$distantId);
        }

        $this->voterAttributes[] = $accessAttribute;
        foreach ($this->voterAttributes as $attribute) {
            if ($secured && $attribute && false === $this->security->isGranted($attribute, $entity)) {
                throw new AccessDeniedException();
            }
        }

        return $entity;
    }

    public function getEntityByUsername($userName, $secured = true): Beneficiaire
    {
        /** @var Beneficiaire $entity */
        if (!$entity = $this->entityManager->getRepository(Beneficiaire::class)->findByUsername($userName)) {
            throw new NotFoundHttpException('No beneficiary found for username '.$userName);
        }

        if ($secured && false === $this->security->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $entity)) {
            throw new AccessDeniedException();
        }

        return $entity;
    }

    public function getSecretQuestions($translator): array
    {
        $arQuestions = [];

        foreach (Beneficiaire::getArQuestionsSecrete() as $key => $value) {
            $arQuestions[$translator->trans($key)] = $translator->trans($value);
        }

        return $arQuestions;
    }

    public function getSecretQuestionsV2($translator): array
    {
        $arQuestions = [];

        foreach (Beneficiaire::getArQuestionsSecrete() as $value) {
            $arQuestions[] = $translator->trans($value);
        }

        return $arQuestions;
    }

    /**
     * @throws \Exception
     */
    public function populate(Beneficiaire $entity)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException();
        }

        $entity
            ->setDateNaissance(date_create_from_format('Y-m-d', $request->get('date_naissance')))
            ->setQuestionSecrete($request->get('question_secrete'))
            ->setReponseSecrete($request->get('reponse_secrete'))
            ->getUser()
            ->setPrenom($request->get('prenom'))
            ->setNom($request->get('nom'))
            ->setTelephone($request->get('telephone'))
            ->setEmail($request->get('email'));

        $adresse = $entity->getUser()->getAdresse();
        if (null !== $adresseParams = $request->get('adresse')) {
            if (null === $adresse) {
                $adresse = new Adresse();
                $entity->getUser()->setAdresse($adresse);
            }
            $rue = empty($adresseParams['rue']) ? null : $adresseParams['rue'];
            $codePostal = empty($adresseParams['code_postal']) ? null : $adresseParams['code_postal'];
            $ville = empty($adresseParams['ville']) ? null : $adresseParams['ville'];

            $adresse
                ->setNom($rue)
                ->setCodePostal($codePostal)
                ->setVille($ville);
        } elseif (null !== $entity->getUser()->getAdresse()) {
            $entity->getUser()->setAdresse();
            $this->entityManager->remove($adresse);
        }
    }

    public function populateBeneficiary(Beneficiaire $beneficiaire, $data, bool $forceAccept = false): array
    {
        $user = $beneficiaire->getUser();
        $errors = new ConstraintViolationList();

        $distantId = (string) $data->get('idRosalie') ?: (string) $data->get('distant_id');
        $oldClient = $this->apiClientManager->getCurrentOldClient();
        if ($oldClient) {
            $user->addCreator((new CreatorClient())->setEntity($oldClient));
        }

        if (null !== $oldClient && null === $beneficiaire->getId() && '' !== $distantId) {
            $membreDistantId = $data->get('member_distant_id');
            $centreDistantId = $data->get('center_distant_id');

            $currentExternalLink = $this->clientBeneficiaireRepository->findOneBy(['client' => $oldClient, 'distantId' => $distantId]);
            if (null !== $currentExternalLink) {
                $errors->add(new ConstraintViolation('Already a beneficiary for distant id : '.$distantId, 'Already a beneficiary for distant id : '.$distantId, [], '', '', ''));
            }

            if (0 === $errors->count()) {
                $externalLink = new ClientBeneficiaire($oldClient, $distantId);
                $beneficiaire->addExternalLink($externalLink);

                if (null !== $centreDistantId) {
                    $externalLink->setMembreDistantId($membreDistantId);
                    $centre = $this->centreProvider->getEntityByDistantId($centreDistantId);

                    $creatorCentre = (new CreatorCentre())->setEntity($centre);
                    $user->addCreator($creatorCentre);

                    $this->linkBeneficiaryToCenter($beneficiaire, $centre, $externalLink, $forceAccept);
                }
            }
        } else {
            $all = $data->all();
            if (array_key_exists('centers', $all)) {
                $centers = $all['centers'];
                if ($centers && is_array($centers)) {
                    foreach ($centers as $index => $centerId) {
                        $center = $this->centreProvider->getEntity($centerId);
                        $this->linkBeneficiaryToCenter($beneficiaire, $center, null, $forceAccept);

                        if (0 === $index) {
                            $creatorCentre = (new CreatorCentre())->setEntity($center);
                            $beneficiaire->addCreator($creatorCentre);
                        }
                    }
                }
            }
        }

        $user->setNom($data->get('nom') ?? $data->get('last_name'));
        $user->setPrenom($data->get('prenom') ?? $data->get('first_name'));
        $user->setTelephone($data->get('telephone') ?? $data->get('phone'));
        $user->setEmail('' === $data->get('email') ? null : $data->get('email'));

        $secretQuestion = $data->get('secret_question');
        $secretQuestionAnswer = $data->get('secret_question_answer');
        $secretQuestionCustomText = $data->get('secret_question_custom_text');
        if ($secretQuestionCustomText && '' !== $secretQuestionCustomText) {
            $beneficiaire->setQuestionSecrete($secretQuestionCustomText);
        } elseif ($secretQuestion && '' !== $secretQuestion) {
            $beneficiaire->setQuestionSecrete($secretQuestion);
        }
        if ($secretQuestionAnswer && '' !== $secretQuestionAnswer) {
            $beneficiaire->setReponseSecrete($secretQuestionAnswer);
        }

        $birthDateString = $data->get('dateNaissance') ?? $data->get('date_naissance') ?? $data->get('birth_date');
        if ($birthDateString) {
            $errors->addAll($this->validator->validate($birthDateString, new DateNaissance()));
            if (0 === $errors->count()) {
                $birthDate = date_create_from_format('d/m/Y', $birthDateString);
                $beneficiaire->setDateNaissance($birthDate);
            }
        }

        $beneficiaire->setUser($user);
        $errors->addAll($this->validator->validateProperty($user, 'email'));
        $errors->addAll($this->validator->validateProperty($user, 'telephone'));
        $errors->addAll($this->validator->validate($beneficiaire, null, ['beneficiaire']));

        $response = [];
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $propertyPathExplode = explode('.', $error->getPropertyPath());
                $len = count($propertyPathExplode);

                $lastKey = $propertyPathExplode[$len - 1];
                $errorsArray[$lastKey] = $this->translator->trans($error->getMessage());
            }

            $response = [
                'error' => [
                    'message' => 'There was a validation error',
                    'status' => Response::HTTP_BAD_REQUEST,
                    'code' => ErrorCode::VALIDATION_ERROR,
                    'details' => $errorsArray,
                ],
            ];
        }

        return $response;
    }

    public function delete(Beneficiaire $entity, User $user)
    {
        $this->entityManager->remove($entity);
        $this->entityManager->remove($entity->getUser());
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new BeneficiaireEvent($entity, BeneficiaireEvent::BENEFICIAIRE_DELETED, $user), REEvent::RE_EVENT_BENEFICIAIRE);
    }

    /**
     * @throws \ReflectionException
     */
    public function addExternalLink($entity, $distantId): void
    {
        $externalLink = new ClientBeneficiaire($this->apiClientManager->getCurrentOldClient(), $distantId);
        $entity->addExternalLink($externalLink);
    }

    /**
     * @throws EntityNotFoundException
     */
    public function setExternalLink($entity, $distantId): void
    {
        $client = $this->apiClientManager->getCurrentOldClient();
        if (null === $client) {
            throw new \RuntimeException('No client connected.');
        }
        /** @var ClientBeneficiaire $externalLink */
        $externalLink = $entity->getExternalLinks()->filter(static function (ClientBeneficiaire $clientBeneficiaire) use ($client) {
            return $clientBeneficiaire->getClient()->getRandomId() === $client->getRandomId();
        })->first();
        if (!$externalLink) {
            throw new EntityNotFoundException('Not found external link for client '.$client->getNom());
        }
        $externalLink->setDistantId($distantId);
    }

    public function save(Beneficiaire $beneficiaire, User $user = null, $firstCentre = null): void
    {
        if ($isCreating = !$beneficiaire->getId()) {
            $beneficiaire->addCreator((new CreatorClient())->setEntity($this->apiClientManager->getCurrentOldClient()));
            if ($firstCentre) {
                $beneficiaire->addCreator((new CreatorCentre())->setEntity($firstCentre));
            }
        }

        $this->userManager->updateUser($beneficiaire->getUser(), false);
        $this->entityManager->persist($beneficiaire);
        $this->entityManager->flush();

        $event = new BeneficiaireEvent($beneficiaire, $isCreating ? BeneficiaireEvent::BENEFICIAIRE_CREATED : BeneficiaireEvent::BENEFICIAIRE_MODIFIED, $user);
        $this->eventDispatcher->dispatch($event, REEvent::RE_EVENT_BENEFICIAIRE);
    }

    public function getEntity(int|string $id, $accessAttribute = null, ?bool $secured = true): Beneficiaire
    {
        $accessAttribute = null;
        $oldClient = $this->apiClientManager->getCurrentOldClient();
        $user = $this->security->getUser();

        /* Si le grant_type est un client_credentials */
        if ($oldClient && $user instanceof NullUser) {
            $entity = $this->entityManager->getRepository(Beneficiaire::class)->findByDistantId($id, $oldClient->getRandomId());
            if (!$entity) {
                throw new NotFoundHttpException('No beneficiary found for distant id '.$id.' (client: '.$oldClient->getNom().')');
            }

            /** Vérification si le bénéficiaire a accepté au moins un ajout d'un ces centres du client */
            /** @var \Doctrine\Common\Collections\Collection<int, ClientBeneficiaire> $externalLinks */
            $externalLinks = $entity->getExternalLinks()->filter(static function (ClientBeneficiaire $element) use ($id, $oldClient) {
                return $element->getDistantId() == $id && $element->getClient() === $oldClient;
            });
            foreach ($externalLinks as $externalLink) {
                if (!$externalLink->getBeneficiaireCentre()?->getBValid()) {
                    throw new AccessDeniedException('The beneficiary has not yet accepted your connection request.');
                }
            }

            $secured = false;
        } elseif (!$entity = $this->entityManager->find(Beneficiaire::class, $id)) {
            throw new NotFoundHttpException('No beneficiary found for id '.$id);
        }

        if ($accessAttribute) {
            $this->voterAttributes[] = $accessAttribute;
        }

        foreach ($this->voterAttributes as $attribute) {
            if ($secured && $attribute && false === $this->security->isGranted($attribute, $entity)) {
                throw new AccessDeniedException();
            }
        }

        return $entity;
    }

    public function getEntityById($id, $accessAttribute = null, ?bool $secured = true): Beneficiaire
    {
        if (!$entity = $this->entityManager->find(Beneficiaire::class, $id)) {
            throw new NotFoundHttpException('No beneficiary found for id '.$id);
        }

        $this->voterAttributes[] = $accessAttribute;

        foreach ($this->voterAttributes as $attribute) {
            if ($secured && $attribute && false === $this->security->isGranted($attribute, $entity)) {
                throw new AccessDeniedException();
            }
        }

        return $entity;
    }

    private function linkBeneficiaryToCenter(Beneficiaire $beneficiaire, Centre $centre, ClientBeneficiaire $externalLink = null, bool $forceAccept = false): void
    {
        $beneficiaireCentre = (new BeneficiaireCentre())->setCentre($centre)->setBValid($forceAccept);
        if ($externalLink) {
            $beneficiaire->addExternalLink($externalLink);
            $externalLink->setBeneficiaireCentre($beneficiaireCentre);
        }
        $beneficiaire->addBeneficiairesCentre($beneficiaireCentre);
    }
}
