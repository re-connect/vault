<?php

namespace App\Tests\v2\EventSubscriber\Logs;

interface TestLogActivitySubscriberInterface
{
    public function testEventSubscriptions(): void;

    public function testPostPersist(): void;

    public function testPreUpdate(): void;

    public function testPreRemove(): void;
}
