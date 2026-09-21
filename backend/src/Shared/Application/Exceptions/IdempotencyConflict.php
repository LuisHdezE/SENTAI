<?php

namespace Sentai\Shared\Application\Exceptions;

use RuntimeException;

final class IdempotencyConflict extends RuntimeException {}
