<?php

namespace Tests\Support\Libraries;

/**
 * Wraps each test in a DB transaction that is always rolled back, so tests may freely delete/insert
 * rows (e.g. to create a "clean" job table) without leaving anything behind.
 * Safe only because PHPUnit is pinned to `talenthub_test` (see Config\Database / tests/bootstrap.php).
 * Only the shared connection is used, so controllers called in-process see the same transaction.
 */
trait RollsBackDatabase
{
    protected function dbc(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect();
    }

    protected function beginRollbackTx(): void
    {
        $this->dbc()->transBegin();
    }

    protected function endRollbackTx(): void
    {
        $db = $this->dbc();
        while ($db->transDepth > 0) {
            $db->transRollback();
        }
    }
}
