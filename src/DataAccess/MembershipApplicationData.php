<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

/**
 * @deprecated The access tokens are an HTTP layer detail that does not belong in the domain.
 *              You should access this information using the AuthenticationToken entity in the Application or Op Center.
 */
class MembershipApplicationData {

	private ?string $accessToken = null;
	private ?string $updateToken = null;
	private ?int $preservedStatus = null;

	public function getAccessToken(): ?string {
		return $this->accessToken;
	}

	public function setAccessToken( ?string $token ): void {
		$this->accessToken = $token;
	}

	public function getUpdateToken(): ?string {
		return $this->updateToken;
	}

	public function setUpdateToken( ?string $updateToken ): void {
		$this->updateToken = $updateToken;
	}

	public function getPreservedStatus(): ?int {
		return $this->preservedStatus;
	}

	public function setPreservedStatus( ?int $status ): void {
		$this->preservedStatus = $status;
	}

}
