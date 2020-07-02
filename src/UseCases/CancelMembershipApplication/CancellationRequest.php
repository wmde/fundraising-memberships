<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CancellationRequest {

	private $applicationId;

	public function __construct( int $applicationId ) {
		$this->applicationId = $applicationId;
	}

	public function getApplicationId(): int {
		return $this->applicationId;
	}

}
