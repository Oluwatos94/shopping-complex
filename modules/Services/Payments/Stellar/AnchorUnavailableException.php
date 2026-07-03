<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments\Stellar;

/**
 * Thrown when a call to the anchor fails at the transport level (auth handshake, status
 * lookup, deposit init) — i.e. a transient/retryable condition, not a terminal outcome for
 * the deposit itself. Callers polling a deposit should treat this as "keep waiting", not
 * "the deposit failed": the on-chain deposit is unaffected by our inability to reach the anchor.
 */
final class AnchorUnavailableException extends \RuntimeException {}
