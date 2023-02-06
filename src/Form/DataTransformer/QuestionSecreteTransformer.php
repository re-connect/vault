<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\Request;

class QuestionSecreteTransformer implements DataTransformerInterface
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function transform($value): mixed
    {
        if (null === $value) {
            return '';
        }

        return $value;
    }

    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return null;
        }

        if ('Autre' === $value->getQuestionSecrete()) {
            if (!array_key_exists('autreQuestionSecrete', $this->request->get(array_keys($this->request->request->all())[0]))) {
                throw new TransformationFailedException('Impossible de comprendre votre question secrete');
            }

            $value->setQuestionSecrete($this->request->get(array_keys($this->request->request->all())[0])['autreQuestionSecrete']);
        }

        return $value;
    }
}
