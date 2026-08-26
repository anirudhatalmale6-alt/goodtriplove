<?php

namespace App\Contracts;

interface TotpVerifier
{
    public function verify(string $secret, string $code): bool;
}
