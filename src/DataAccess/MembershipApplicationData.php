<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

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
