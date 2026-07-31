<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * backend/src/exceptions/QRException.php
 */
final class QRException extends RuntimeException
{
    public static function malformed(): self
    {
        return new self('QR content is not in the expected format.');
    }

    public static function notFound(): self
    {
        return new self('This QR code is not recognized.');
    }

    public static function revoked(): self
    {
        return new self('This QR code has been revoked.');
    }

    public static function expired(): self
    {
        return new self('This QR code has expired.');
    }

    public static function emptyValue(): self
    {
        return new self('No QR content was provided.');
    }
}
