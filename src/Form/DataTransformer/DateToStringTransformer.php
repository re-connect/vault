<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer as BaseDateTimeTransformer;

class DateToStringTransformer extends BaseDateTimeTransformer
{
    public function __construct($inputTimezone = null, $outputTimezone = null, $format = 'd/m/Y')
    {
        parent::__construct($inputTimezone, $outputTimezone, $format);
    }

    public function transform($value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        if (!$value instanceof \DateTime) {
            throw new TransformationFailedException('Expected a \DateTime.');
        }

        $value = clone $value;
        try {
            $value->setTimezone(new \DateTimeZone($this->outputTimezone));
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $value->format('d/m/Y');
    }

    public function reverseTransform($value): ?\DateTime
    {
        if (empty($value)) {
            return null;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }
        if (!preg_match('#^\d{2}/\d{2}/\d{4}$#', $value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        try {
            $outputTz = new \DateTimeZone($this->outputTimezone);

            $dateTime = \DateTime::createFromFormat('d/m/Y', $value);

            $lastErrors = \DateTime::getLastErrors();

            if (0 < $lastErrors['warning_count'] || 0 < $lastErrors['error_count']) {
                throw new TransformationFailedException(implode(', ', array_merge(array_values($lastErrors['warnings']), array_values($lastErrors['errors']))));
            }

            if ($this->inputTimezone !== $this->outputTimezone) {
                $dateTime->setTimeZone(new \DateTimeZone($this->inputTimezone));
            }
        } catch (TransformationFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $dateTime;
    }
}
