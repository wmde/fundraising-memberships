<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CancellationRequest {

	private int $applicationId;
	private ?string $authorizedUser;

	public function __construct( int $applicationId, ?string $authorizedUser = null ) {
		$this->applicationId = $applicationId;
		$this->authorizedUser = $authorizedUser;
	}

	public function getApplicationId(): int {
		return $this->applicationId;
	}

	public function initiatedByApplicant(): bool {
		return $this->authorizedUser !== null;
	}

	public function getUserName(): string {
		if ( $this->authorizedUser == null ) {
			throw new \LogicException( "Tried to access user name of unauthorized user. Call isAuthorizedRequest() first!" );
		}
		return $this->authorizedUser;
	}

}
