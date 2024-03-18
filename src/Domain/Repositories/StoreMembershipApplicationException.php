<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

use RuntimeException;
use Throwable;

class StoreMembershipApplicationException extends RuntimeException {

	public function __construct( string $message = null, Throwable $previous = null ) {
		parent::__construct(
			$message ?? 'Could not store membership application',
			0,
			$previous
		);
	}

}
