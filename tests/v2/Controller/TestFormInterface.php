<?php

namespace App\Tests\v2\Controller;

interface TestFormInterface
{
    /**
     * @param array<string, string> $values
     *
     * @dataProvider provideTestFormIsValid
     */
    public function testFormIsValid(string $url, string $formSubmit, array $values, ?string $email, ?string $redirectUrl): void;

    public function provideTestFormIsValid(): ?\Generator;

    /**
     * @param array<string, string>         $values
     * @param array<array<string, ?string>> $errors
     *
     * @dataProvider provideTestFormIsNotValid
     */
    public function testFormIsNotValid(string $url, string $route, string $formSubmit, array $values, array $errors, ?string $email, ?string $alternateSelector = null): void;

    public function provideTestFormIsNotValid(): ?\Generator;
}
