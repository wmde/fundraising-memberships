<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\ShowApplicationConfirmation;

class ShowAppConfirmationRequest {

	public function __construct( private readonly int $applicationId ) {
	}

	public function getApplicationId(): int {
		return $this->applicationId;
	}

}
