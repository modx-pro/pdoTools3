<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Integration;

class SnippetPdoUsersTest extends ModxTestCase
{
    public function testListsAdminUser(): void
    {
        $out = $this->runPdoSnippet('pdoUsers', [
            'limit' => 5,
            'return' => 'ids',
        ]);

        $this->assertIsString($out);
    }
}
