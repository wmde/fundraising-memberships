<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

use LogicException;

class CancellationRequest {

	public function __construct( private readonly int $applicationId, private readonly ?string $authorizedUser = null ) {
	}

	public function getApplicationId(): int {
		return $this->applicationId;
	}

	public function initiatedByApplicant(): bool {
		return $this->authorizedUser !== null;
	}

	public function getUserName(): string {
		if ( $this->authorizedUser == null ) {
			throw new LogicException( "Tried to access user name of unauthorized user. Call isAuthorizedRequest() first!" );
		}
		return $this->authorizedUser;
	}

}
