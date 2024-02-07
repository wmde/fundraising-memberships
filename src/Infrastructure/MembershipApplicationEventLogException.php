<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Infrastructure;

use RuntimeException;
use Throwable;

class MembershipApplicationEventLogException extends RuntimeException {

	public function __construct( string $message, Throwable $previous = null ) {
		parent::__construct( $message, 0, $previous );
	}

}
