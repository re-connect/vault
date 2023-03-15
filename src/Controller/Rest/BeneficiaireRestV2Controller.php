<?php

namespace App\Controller\Rest;

use App\Api\Manager\ApiClientManager;
use App\Controller\REController;
use App\Entity\Beneficiaire;
use App\Entity\BeneficiaireCentre;
use App\Entity\Centre;
use App\Entity\ClientBeneficiaire;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Form\Factory\UserFormFactory;
use App\Manager\CentreManager;
use App\Manager\RestManager;
use App\Manager\SMSManager;
use App\Manager\UserManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\CentreProvider;
use App\Provider\UserProvider;
use App\Repository\ClientRepository as OldClientRepository;
use App\Validator\Constraints\Beneficiaire\Entity as BeneficiaireValidator;
use App\Validator\Constraints\UniqueExternalLink;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route({
 *     "old": "/api/",
 *     "new": "/api/v2/"
 *   },
 *     name="re_api_beneficiaire_"
 * )
 */
final class BeneficiaireRestV2Controller extends REController
{
    private RestManager $restManager;

    public function __construct(
        RequestStack $requestStack,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        RestManager $restManager,
        ApiClientManager $apiClientManager,
    ) {
        $this->restManager = $restManager;
        parent::__construct($requestStack, $translator, $entityManager, $apiClientManager);
    }

    /**
     * @Route(
     *     "beneficiaires",
     *     name="list_for_pro",
     *     methods={"GET"},
     * )
     */
    public function getBeneficiariesForPro(CentreProvider $centreProvider): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            } elseif (!$user->isMembre()) {
                throw $this->createAccessDeniedException('You must be connected as membre');
            }

            $beneficiairesByCentre = $user->isMembre()
                ? $centreProvider->getBeneficiairesFromMembre($user->getSubjectMembre())
                : $centreProvider->getBeneficiairesFromGestionnaire($user->getSubjectGestionnaire());

            return $this->json($beneficiairesByCentre);
        } catch (AccessDeniedException $e) {
            return (new JsonResponseException($e))->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/mine",
     *     name="get_mine",
     *     methods={"GET"}
     * )
     */
    public function getMine(CentreProvider $centreProvider): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            if ($user->isMembre()) {
                $beneficiairesByCentre = $centreProvider->getBeneficiairesFromMembre($user->getSubjectMembre());

                return $this->json($beneficiairesByCentre);
            }

            throw $this->createAccessDeniedException('You must be connected as membre or gestionnaire');
        } catch (AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{id}/send-sms-activation-code",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="send_activation_code",
     *     methods={"PATCH"}
     * )
     */
    public function sendActivationCode($id, BeneficiaireProvider $provider, SMSManager $SMSManager): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);
            $SMSManager->sendSmsActivation($entity);

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{id}",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="get_beneficiaire",
     *     methods={"GET"}
     * )
     */
    public function getEntity($id, BeneficiaireProvider $provider): JsonResponse
    {
        try {
            return $this->json($provider->getEntity($id));
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            return (new JsonResponseException($e))->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{id}/phone-number",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="update_phone_number",
     *     methods={"PATCH"}
     * )
     */
    public function updatePhoneNumber($id, BeneficiaireProvider $provider): JsonResponse
    {
        try {
            if (null === $telephone = $this->request->get('telephone')) {
                throw new BadRequestHttpException('Telephone non recu');
            }

            $entity = $provider->getEntity($id);
            $entity->getUser()->setTelephone($telephone);

            $this->entityManager->flush();

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException|BadRequestHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route("beneficiaries/{id}",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="delete",
     *     methods={"DELETE"}
     * )
     */
    public function delete($id, BeneficiaireProvider $provider): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $provider->delete($entity, $user);

            return $this->json('', Response::HTTP_NO_CONTENT);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{id}/test-activation-sms-code",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="test_code_sms",
     *     methods={"PATCH"}
     * )
     */
    public function testCodeSms($id, BeneficiaireProvider $provider, CentreManager $centreManager): JsonResponse
    {
        try {
            $entity = $provider->getEntity($id);

            $user = $this->getUser();
            if (!$user instanceof User) {
                $this->createAccessDeniedException();
            }

            $subject = $user->getSubject();
            if (!$subject->isMembre()) {
                $this->createAccessDeniedException('Il faut être membre pour accéder à cette fonctionnalité');
            }

            $request = $this->request;

            if (null === $request) {
                throw new BadRequestHttpException();
            }

            $codeSms = $request->get('code');
            if (null === $codeSms) {
                throw new BadRequestHttpException('Code sms non recu');
            }

            $bResult = strtolower($entity->getActivationSmsCode()) === strtolower($codeSms);
            if ($bResult) {
                $centreManager->accepterTousCentreEnCommun($entity->getUser()->getSubject(), $subject);
            }

            return $this->json($bResult);
        } catch (NotFoundHttpException|AccessDeniedException|BadRequestHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{id}/password",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="update_password",
     *     methods={"PATCH"}
     * )
     */
    public function updatePassword(
        $id,
        BeneficiaireProvider $provider,
        UserManager $userManager
    ): JsonResponse {
        try {
            $entity = $provider->getEntity($id);

            $user = $entity->getUser();
            if (!$user instanceof User) {
                $this->createAccessDeniedException();
            }

            $request = $this->request;

            if (null === $request) {
                throw new BadRequestHttpException();
            }

            if (null === $password = $request->get('password')) {
                throw new BadRequestHttpException('Password sms non recu');
            }

            $user
                ->setPlainPassword($password)
                ->setBActif(false);
            $userManager->updatePassword($user);

            $this->entityManager->flush();

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException|BadRequestHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{id}/update-secret-question",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="update_secret_question",
     *     methods={"PATCH"}
     * )
     */
    public function updateSecretQuestion(
        $id,
        BeneficiaireProvider $provider,
        TranslatorInterface $translator
    ): JsonResponse {
        try {
            $entity = $provider->getEntity($id);

            $request = $this->request;

            if (null === $request) {
                throw new BadRequestHttpException();
            }

            $secretQuestion = $request->get('question_secrete');
            $secretQuestionOriginals = $provider->getSecretQuestions($translator);

            if (empty($secretQuestionOriginals[$secretQuestion])) {
                throw new BadRequestHttpException('Cette question secrète n\'est pas dans celles par defaut.');
            }

            $entity
                ->setQuestionSecrete($secretQuestion)
                ->setReponseSecrete($request->get('reponse_secrete'));

            if ('Autre' === $entity->getQuestionSecrete()) {
                if (empty($anotherSecretQuestion = $request->get('autre_question_secrete'))) {
                    throw new BadRequestHttpException('Vous n\'avez pas renseigné la question secrète.');
                }
                $entity->setQuestionSecrete($anotherSecretQuestion);
            }

            $entity->getUser()->setBActif(false);

            $this->entityManager->flush();

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException|BadRequestHttpException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "get-secret-questions",
     *     name="get_secret_questions",
     *     methods={"GET"}
     * )
     */
    public function getSecretQuestions(BeneficiaireProvider $provider, TranslatorInterface $translator): JsonResponse
    {
        $arQuestions = $provider->getSecretQuestionsV2($translator);

        return $this->json($arQuestions);
    }

    /**
     * @Route("beneficiaries", name="add", methods={"POST"})
     */
    public function add(Request $request, UserManager $userManager, BeneficiaireProvider $provider): Response
    {
        try {
            $connectedUser = $this->getUser();
            if ($connectedUser instanceof User && !$connectedUser->hasMemberAccess()) {
                throw $this->createAccessDeniedException('Il faut être membre ou gestionnaire pour accéder à cette fonctionnalité');
            }

            $user = (new User())->setBActif(true)->setTypeUser(User::USER_TYPE_BENEFICIAIRE);
            $beneficiaire = (new Beneficiaire())->setUser($user)->setIsCreating(false);

            $password = $request->request->get('password') ?? $userManager->randomPassword();
            $user->setPlainPassword($password);

            $errorsArray = $provider->populateBeneficiary($beneficiaire, $request->request);

            if (count($errorsArray) > 0) {
                return $this->json($errorsArray, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($beneficiaire);
            $this->entityManager->flush();

            return $this->json(array_merge(
                $beneficiaire->jsonSerializeForClientV2($this->apiClientManager->getCurrentOldClient()),
                ['password' => $password]
            ), Response::HTTP_CREATED);
        } catch (AccessDeniedException|NotFoundHttpException $e) {
            return (new JsonResponseException($e))->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiary",
     *     methods={"POST"}
     * )
     */
    public function addAction(
        Request $request,
        TranslatorInterface $translator,
        BeneficiaireProvider $beneficiaireProvider,
        CentreManager $centreManager,
        UserFormFactory $userFormFactory,
        UserProvider $userProvider,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $userConnected = $this->getUser();
            if (!$userConnected->isMembre() && !$userConnected->isAdministrateur()) {
                throw new AccessDeniedException('Il faut être membre pour accéder à cette fonctionnalité');
            }

            $form = $userFormFactory->getBeneficiaireForm($this->getUser()->getSubject()->getHandledCentres());
            $form->handleRequest($request);
            $errorsArray = [];

            if ($form->isSubmitted()) {
                /** @var Beneficiaire $entity */
                $entity = $form->getData();
                $user = $entity->getUser();

                // création d'un username pour le bénéficiaire
                $userProvider->formatUserName($user, $entity->getDateNaissance());

                /** @var ConstraintViolationList $errors */
                $errors = $validator->validate($entity, null, ['username-beneficiaire']);

                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        $propertyPathExplode = explode('.', $error->getPropertyPath());
                        $len = count($propertyPathExplode);

                        $message = $translator->trans($error->getMessage());

                        $lastKey = $propertyPathExplode[$len - 1];
                        $key = $propertyPathExplode[0];
                        if (!array_key_exists($key, $errorsArray)) {
                            $errorsArray[$key] = [];
                        }
                        if ($key === $lastKey) {
                            $errorsArray[$key][] = $message;
                        }

                        for ($i = 1; $i < $len; ++$i) {
                            $this->addKey($errorsArray, $key, $propertyPathExplode[$i], $lastKey, $message);
                            $key = $propertyPathExplode[$i];
                        }
                    }

                    return $this->json($errorsArray, Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $entity->setIsCreating(false);

                $firstCentre = null;
                if (!empty($request->request->get('re_form_beneficiaire')['centres'])) {
                    $firstCentreId = $request->request->get('re_form_beneficiaire')['centres'][0];
                    $firstCentre = $entityManager->find(Centre::class, $firstCentreId);
                }

                $beneficiaireProvider->save($entity, $this->getUser(), $firstCentre);

                $initiateur = $this->getUser()->getSubject();

                if (!empty($request->request->get('re_form_beneficiaire')['centres'])) {
                    foreach ($request->request->get('re_form_beneficiaire')['centres'] as $centreId) {
                        $centre = $entityManager->find(Centre::class, $centreId);
                        if (null !== $centre) {
                            $centreManager->associateUserWithCentres($entity, $centre, $initiateur, null, true);
                        }
                    }
                }

                $entityManager->refresh($entity);

                return $this->json($entity, Response::HTTP_CREATED);
            }

            $errorsArray = $this->getErrorMessages($form);

            return $this->json($errorsArray, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiary/enable",
     *     name="enable",
     *     methods={"PATCH"}
     * )
     */
    public function enable(
        Request $request,
        UserManager $userManager,
        ValidatorInterface $validator,
        TranslatorInterface $translator
    ): Response {
        try {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            if (!$user->isBeneficiaire()) {
                throw $this->createAccessDeniedException('Il faut être bénéficiaire pour accéder à cette fonctionnalité');
            }
            $entity = $user->getSubjectBeneficiaire();

            $errors = new ConstraintViolationList();
            if ('Autre' === $secretQuestion = $request->get('question_secrete')) {
                if (!$otherSecretQuestion = $request->get('autre_question_secrete')) {
                    $violation = new ConstraintViolation('Cette valeur ne doit pas être vide.', null, [], null, 'autreQuestionSecrete', null);
                    $errors->add($violation);
                }
                $entity->setQuestionSecrete($otherSecretQuestion);
            } else {
                $entity->setQuestionSecrete($secretQuestion);
            }

            if ($email = $request->get('email')) {
                $user->setEmail($email);
            }

            $entity->setReponseSecrete($request->get('reponse_secrete'));
            $user->setPlainPassword($request->get('password'));

            $errors->addAll($validator->validate($entity, null, ['beneficiaire', 'beneficiaireQuestionSecrete', 'password']));
            $userManager->updatePassword($user);

            if (count($errors) > 0) {
                $errorsArray = [];
                foreach ($errors as $key => $error) {
                    $propertyPathExplode = explode('.', $error->getPropertyPath());
                    $len = count($propertyPathExplode);

                    $lastKey = $propertyPathExplode[$len - 1];
                    $errorsArray[$lastKey] = $translator->trans($error->getMessage());
                }

                return $this->json($errorsArray, Response::HTTP_BAD_REQUEST);
            }

            $userManager->updateUser($user, true);

            return $this->json($entity);
        } catch (AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{username}",
     *     requirements={
     *          "username": "[a-z\-]+\.[a-z\-]+\.[0-3][0-9]\/[0-1][0-9]\/[1-2][0-9]{3}(.[0-9]{1,2})?",
     *     },
     *     name="exists",
     *     methods={"GET"}
     * )
     */
    public function exists($username, BeneficiaireProvider $beneficiaryProvider): JsonResponse
    {
        try {
            $entity = $beneficiaryProvider->getEntityByUsername($username, null);

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{id}/distant-id",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="set_distant_id",
     *     methods={"PATCH"}
     * )
     */
    public function updateDistantId($id, BeneficiaireProvider $provider): JsonResponse
    {
        try {
            $distantId = (string) $this->request->get('distant_id');
            $entity = null;
            if ($distantId) {
                $entity = $provider->getEntity($id);
                $provider->setExternalLink($entity, $distantId);
                if (null !== $data = $this->restManager->getJsonValidationError($entity->getExternalLinks(), new UniqueExternalLink())) {
                    return $this->json($data, Response::HTTP_BAD_REQUEST);
                }
                $provider->save($entity);
            }

            return $this->json($entity);
        } catch (NotFoundHttpException|AccessDeniedException|EntityNotFoundException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "beneficiaries/{id}/add-external-link",
     *     requirements={
     *          "id": "\d{1,10}"
     *     },
     *     name="add_external_link",
     *     methods={"PATCH"}
     * )
     */
    public function addExternalLink($id, BeneficiaireProvider $provider, CentreProvider $centreProvider, OldClientRepository $oldClientRepository): JsonResponse
    {
        try {
            $distantId = $this->request->get('distant_id');
            $centreDistantId = $this->request->get('center_distant_id');
            $oldClient = $this->apiClientManager->getCurrentOldClient();

            if ($distantId && $centreDistantId && $oldClient) {
                $beneficiaire = $provider->getEntityById($id);
                $centre = $centreProvider->getEntityByDistantId($centreDistantId);

                $externalLink = $beneficiaire->getExternalLinks()->filter(static function (ClientBeneficiaire $element) use ($oldClient, $distantId) {
                    return $element->getClient() === $oldClient && $element->getDistantId() == (string) $distantId && null === $element->getBeneficiaireCentre();
                })->first();

                $beneficiaireCentre = $beneficiaire->getBeneficiairesCentres()->filter(static function (BeneficiaireCentre $element) use ($centre) {
                    return $element->getCentre() === $centre && null === $element->getExternalLink();
                })->first();

                if (!$beneficiaireCentre) {
                    $beneficiaireCentre = (new BeneficiaireCentre())->setCentre($centre);
                }

                if (!$externalLink) {
                    $externalLink = new ClientBeneficiaire($oldClient, $distantId);
                    $beneficiaire->addExternalLink($externalLink);
                }

                if (null !== $membreDistantId = $this->request->get('membre_distant_id')) {
                    $externalLink->setMembreDistantId($membreDistantId);
                }

                $beneficiaireCentre->setExternalLink($externalLink);

                if (null === $beneficiaireCentre->getId()) {
                    $beneficiaire->addBeneficiairesCentre($beneficiaireCentre);
                }

                if (null !== $data = $this->restManager->getJsonValidationError($beneficiaire, new BeneficiaireValidator())) {
                    return $this->json($data, Response::HTTP_BAD_REQUEST);
                }

                if (null !== $data = $this->restManager->getJsonValidationError($beneficiaire->getExternalLinks(), new UniqueExternalLink())) {
                    return $this->json($data, Response::HTTP_BAD_REQUEST);
                }
            }

            if (!isset($beneficiaire)) {
                throw new BadRequestHttpException();
            }

            $provider->save($beneficiaire);

            return $this->json($beneficiaire);
        } catch (NotFoundHttpException|AccessDeniedException $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * $key est la clef précédente
     * $addKey est la clef que l'on veut ajouter
     * $lastKey est la dernière clef
     * $message est le message que l'on veut ajouter à la fin.
     *
     * @param $errors array
     */
    private function addKey(&$errors, $key, $addKey, $lastKey, $message)
    {
        if (array_key_exists($key, $errors)) {
            /* si la clef existe alors on vérifie si on est dans la situation de la derniere clef ou pas */
            if ($lastKey === $addKey) {
                /* Si on arrive à la dernier clef alors on verifie si elle existe déjà
                 * si elle n'existe pas alors on cré la clef
                 * dans les deux cas on ajoute seulement le message
                 */
                if (!array_key_exists($addKey, $errors[$key])) {
                    $errors[$key][$addKey] = [];
                }
                $errors[$key][$addKey][] = $message;
            } elseif (0 === count($errors[$key])) {
                /* si il ne s'agit pas de la dernière clef alors on la crée simplement */
                $errors[$key][$addKey] = [];
            }
        } else {
            /** Si la clef n'existe pas alors on rentre d'un niveau */
            $firstElement = $errors[0];
            if (is_string($firstElement)) {
                return;
            }
            $this->addKey($firstElement, $key, $addKey, $lastKey, $message);
        }
    }

    private function getErrorMessages(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if ($child->isSubmitted() && !$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }
}
