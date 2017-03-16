<?php

namespace winwin\jobQueue;

class LockHandlerTest extends TestCase
{
    public function testLock()
    {
        $lock = new LockHandler('my-lock', 2);
        $this->assertTrue($lock->lock(false));
        sleep(3);
        $this->assertFalse($lock->isAlive());

        $this->assertTrue($lock->lock(false));
        foreach (range(1, 3) as $i) {
            $this->assertTrue($lock->heartbeat());
            sleep(1);
        }
        $this->assertTrue($lock->isAlive());
    }
}
