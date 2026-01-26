<?php

declare(strict_types=1);

namespace Done\PayTR\Exceptions;

/**
 * İmza/hash doğrulaması başarısız olduğunda fırlatılır (callback vb.).
 */
class SignatureException extends PayTRException
{
}
