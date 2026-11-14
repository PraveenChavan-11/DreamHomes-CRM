<?php

/**
 * PHPUnit bootstrap: framework bootstrap + hard database-isolation check.
 *
 * Runs before any test, migration, refresh or rollback. If any database group resolves to anything other
 * than the local `talenthub_test` database, the whole run aborts with a clear message and nothing is executed.
 */
require_once __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

try {
    $name = \Config\Database::assertSafeTestDatabase();
    fwrite(STDERR, "[test-db-guard] PHPUnit is using database '{$name}' (verified via SELECT DATABASE()).\n");
} catch (\Throwable $e) {
    fwrite(STDERR, "\n[test-db-guard] ABORTED: " . $e->getMessage() . "\n"
        . "Create it with: CREATE DATABASE talenthub_test; then load a copy of the schema. See docs/transformation/TEST_DATABASE_ISOLATION.md\n\n");
    exit(1);
}
