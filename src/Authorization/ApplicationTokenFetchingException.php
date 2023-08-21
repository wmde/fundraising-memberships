<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

/**
 * @deprecated The calling code should be able to rely on other methods of the
 *      {@see \WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizer} implementation to get the tokens
 *
 */
class ApplicationTokenFetchingException extends \RuntimeException {

	public function __construct( string $message, \Exception $previous = null ) {
		parent::__construct( $message, 0, $previous );
	}

}
