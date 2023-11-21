<?php

namespace App\Tests\v2\Listener\Logs;

interface TestLogActivityListenerInterface
{
    public function testPostPersist(): void;

    public function testPreUpdate(): void;

    public function testPreRemove(): void;
}
