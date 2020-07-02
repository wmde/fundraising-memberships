<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class ShowAppConfirmationRequest {

	private $applicationId;

	public function __construct( int $applicationId ) {
		$this->applicationId = $applicationId;
	}

	public function getApplicationId(): int {
		return $this->applicationId;
	}

}
