<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StoreMembershipApplicationException extends \RuntimeException {

	public function __construct( string $message = null, \Exception $previous = null ) {
		parent::__construct(
			$message ?? 'Could not store membership application',
			0,
			$previous
		);
	}

}
