<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tracking;

use Exception;
use RuntimeException;

class ApplicationPiwikTrackingException extends RuntimeException {

	public function __construct( string $message, Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );
	}

}
