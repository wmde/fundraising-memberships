<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

/**
 * @since 2.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MembershipApplicationData {

	private $accessToken;
	private $updateToken;
	private $preservedStatus;

	public function getAccessToken(): ?string {
		return $this->accessToken;
	}

	public function setAccessToken( ?string $token ) {
		$this->accessToken = $token;
	}

	public function getUpdateToken(): ?string {
		return $this->updateToken;
	}

	public function setUpdateToken( ?string $updateToken ) {
		$this->updateToken = $updateToken;
	}

	public function getPreservedStatus(): ?int {
		return $this->preservedStatus;
	}

	public function setPreservedStatus( ?int $status ) {
		$this->preservedStatus = $status;
	}

}
