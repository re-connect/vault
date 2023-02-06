<?php

namespace App\Controller\Rest;

use App\Controller\REController;
use App\Entity\Beneficiaire;
use App\Entity\Centre;
use App\Entity\User;
use App\Exception\JsonResponseException;
use App\Form\Factory\UserFormFactory;
use App\Manager\CentreManager;
use App\Manager\SMSManager;
use App\Manager\UserManager;
use App\Provider\BeneficiaireProvider;
use App\Provider\CentreProvider;
use App\Provider\UserProvider;
use App\Security\Authorization\Voter\BeneficiaireVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/appli", name="api_beneficiary_", options={"expose"=true})
 */
class BeneficiaireRestController extends REController
{
    /**
     * @Route(
     *     "/beneficiaries/mine",
     *     name="list_for_user",
     *     methods={"GET"},
     * )
     */
    public function getForMe(CentreProvider $centreProvider): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if ($user->isMembre()) {
                $beneficiairesByCentre = $centreProvider->getBeneficiairesFromMembre($user->getSubjectMembre());
            } elseif ($user->isGestionnaire()) {
                $beneficiairesByCentre = $centreProvider->getBeneficiairesFromGestionnaire($user->getSubjectGestionnaire());
            } else {
                throw new \RuntimeException('You must be connected as membre or gestionnaire');
            }

            return $this->json($beneficiairesByCentre);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}/send-sms-activation-code",
     *     name="send_sms_activation_code",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function sendActivationCodeAction($id, BeneficiaireProvider $beneficiaireProvider, SMSManager $SMSManager): JsonResponse
    {
        try {
            $entity = $beneficiaireProvider->getEntity($id);

            $SMSManager->sendSmsActivation($entity);

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}",
     *     name="get",
     *     methods={"GET"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function getBeneficiaireAction($id, BeneficiaireProvider $beneficiaireProvider): JsonResponse
    {
        try {
            $entity = $beneficiaireProvider->getEntity($id);

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}/phone-number",
     *     name="update_phone_number",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function updatePhoneNumberAction(
        $id,
        Request $request,
        BeneficiaireProvider $beneficiaireProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $entity = $beneficiaireProvider->getEntity($id);

            if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $entity)) {
                throw new AccessDeniedException("Vous n'avez pas le droit de modifier le numéro de ce bénéficaire.");
            }

            $entity->getUser()->setTelephone($request->request->get('phone_number'));

            $em->flush();

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}",
     *     name="delete",
     *     methods={"DELETE"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function deleteBeneficiariesAction(
        $id,
        BeneficiaireProvider $beneficiaireProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $entity = $beneficiaireProvider->getEntity($id);

            if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $entity)) {
                throw new AccessDeniedException("Vous n'avez pas le droit de supprimer ce bénéficaire.");
            }

            $em->remove($entity);
            $em->remove($entity->getUser());
            $em->flush();

            return $this->json('', Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *     "/beneficiaries/{id}/test-activation-sms-code",
     *     name="test_sms_code",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function testCodeSmsAction(
        $id,
        Request $request,
        BeneficiaireProvider $beneficiaireProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        CentreManager $centreManager
    ): JsonResponse {
        try {
            $entity = $beneficiaireProvider->getEntity($id);

            /** @var User $user */
            $user = $this->getUser();
            if (!$user->getSubject()->isMembre()) {
                throw new AccessDeniedException('Il faut être membre pour accéder à cette fonctionnalité');
            }

            if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $entity)) {
                throw new AccessDeniedException("Vous n'avez pas le droit de gérer ce beneficiaire");
            }

            $codeSms = $request->request->get('code');
            if (null === $codeSms) {
                throw new \RuntimeException('Code sms non recu');
            }

            $bResult = strtolower($entity->getActivationSmsCode()) === strtolower($codeSms);
            if ($bResult) {
                $centreManager->accepterTousCentreEnCommun($entity->getUser()->getSubject(), $user->getSubject());
            }

            return $this->json($bResult, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *    "/beneficiaries/{id}/password",
     *     name="update_password",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function updatePasswordAction(
        $id,
        Request $request,
        BeneficiaireProvider $beneficiaireProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        UserManager $userManager,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $entity = $beneficiaireProvider->getEntity($id);

            if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $entity)) {
                throw new AccessDeniedException("Vous n'avez pas le droit de modifier le numéro de ce bénéficaiire.");
            }

            $user = $entity->getUser();

            $password = $request->request->get('password');
            if (null === $password) {
                throw new \RuntimeException('Password sms non recu');
            }

            $user
                ->setPlainPassword($password)
                ->setBActif(false);
            $userManager->updatePassword($user);

            $em->flush();

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *    "/beneficiaries/{id}/update-secret-question",
     *     name="update_secret_question",
     *     methods={"PATCH"},
     *     requirements={
     *          "id": "\d{1,10}"
     *     }
     * )
     */
    public function updateSecretQuestionAction(
        $id,
        Request $request,
        BeneficiaireProvider $beneficiaireProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $entity = $beneficiaireProvider->getEntity($id);

            if (false === $authorizationChecker->isGranted(BeneficiaireVoter::GESTION_BENEFICIAIRE, $entity)) {
                throw new AccessDeniedException("Vous n'avez pas le droit de modifier la question secrète.");
            }

            $secretQuestion = $request->request->get('secret_question');
            $secretQuestionOriginals = $beneficiaireProvider->getSecretQuestions($translator);

            if (empty($secretQuestionOriginals[$secretQuestion])) {
                throw new \RuntimeException('Cette question secrète n\'est pas dans celles par defaut.');
            }

            $entity
                ->setQuestionSecrete($secretQuestion)
                ->setReponseSecrete($request->request->get('secret_answer'));

            if ('Autre' === $entity->getQuestionSecrete()) {
                if (empty($anotherSecretQuestion = $request->request->get('another_secret_question'))) {
                    throw new \RuntimeException('Vous n\'avez pas renseigné la question secrète.');
                }
                $entity->setQuestionSecrete($anotherSecretQuestion);
            }

            $entity->getUser()->setBActif(false);

            $em->flush();

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *    "/get-secret-questions",
     *     name="get_secret_questions",
     *     methods={"GET"},
     * )
     */
    public function getSecretQuestionsAction(BeneficiaireProvider $provider, TranslatorInterface $translator): JsonResponse
    {
        try {
            $arQuestions = $provider->getSecretQuestions($translator);

            return $this->json($arQuestions, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }

    /**
     * @Route(
     *    "/beneficiaries",
     *     name="add",
     *     methods={"POST"},
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
            if (!$userConnected->isMembre() && !$userConnected->isGestionnaire() && !$userConnected->isAdministrateur()) {
                throw new AccessDeniedException('Il faut être membre pour accéder à cette fonctionnalité');
            }

            $form = $userFormFactory->getBeneficiaireForm($this->getUser()->getSubject()->getHandledCentres(), $request, $translator);

            $form->handleRequest($request);
            $errorsArray = [];

            if ($form->isSubmitted() && $form->isValid()) {
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
                if ($this->getUser()->isGestionnaire()) {
                    $initiateur = null;
                }

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
        } catch (\Exception $e) {
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

    private function getErrorMessages(Form $form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $key => $error) {
            if ($form->isRoot()) {
                $errors['#'][] = $error->getMessage();
            } else {
                $errors[] = $error->getMessage();
            }
        }

        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    /**
     * @Route(
     *    "/beneficiaries/enable",
     *     name="enable",
     *     methods={"PATCH"},
     * )
     */
    public function enableAction(
        Request $request,
        UserManager $userManager,
        ValidatorInterface $validator,
        TranslatorInterface $translator
    ): Response {
        try {
            $user = $this->getUser();
            if (!$user instanceof User || !$user->isBeneficiaire()) {
                throw new AccessDeniedException('Il faut être bénéficiaire pour accéder à cette fonctionnalité');
            }
            $entity = $user->getSubjectBeneficiaire();

            $data = $request->request;
            $secretQuestion = $data->get('secret_question');
            $otherSecretQuestion = $data->get('secret_question_custom_text');
            $secretResponse = $data->get('secret_question_answer');
            $password = $data->get('password');
            $email = $data->get('email');

            $errors = new ConstraintViolationList();
            if ('Autre' === $secretQuestion) {
                if (!$otherSecretQuestion) {
                    $violation = new ConstraintViolation('Cette valeur ne doit pas être vide.', null, [], null, 'autreQuestionSecrete', null);
                    $errors->add($violation);
                }
                $entity->setQuestionSecrete($otherSecretQuestion);
            } else {
                $entity->setQuestionSecrete($secretQuestion);
            }

            if ($email) {
                $user->setEmail($email);
            }

            $entity
                ->setReponseSecrete($secretResponse)
                ->getUser()
                ->setPlainPassword($password);
            $userManager->updatePassword($entity);

            $errors->addAll($validator->validate($entity, null, ['beneficiaire', 'beneficiaireQuestionSecrete', 'password']));

            if (count($errors) > 0) {
                $errorsArray = [];
                foreach ($errors as $key => $error) {
                    $propertyPathExplode = explode('.', $error->getPropertyPath());
                    $len = count($propertyPathExplode);

                    $lastKey = $propertyPathExplode[$len - 1];
                    $errorsArray[$lastKey] = $translator->trans($error->getMessage());
                }

                return $this->json($errorsArray, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $userManager->updateUser($user, true);

            return $this->json($entity, Response::HTTP_ACCEPTED);
        } catch (\Exception $e) {
            $jsonResponseException = new JsonResponseException($e);

            return $jsonResponseException->getResponse();
        }
    }
}
