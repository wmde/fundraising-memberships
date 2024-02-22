<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

use RuntimeException;
use Throwable;

class GetMembershipApplicationException extends RuntimeException {

	public function __construct( string $message = null, Throwable $previous = null ) {
		parent::__construct(
			$message ?? 'Could not get membership application',
			0,
			$previous
		);
	}

}
