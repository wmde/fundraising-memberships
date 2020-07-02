<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class MembershipApplicationTokens {

	private $accessToken;
	private $updateToken;

	public function __construct( string $accessToken, string $updateToken ) {
		if ( $accessToken === '' ) {
			throw new \InvalidArgumentException( 'Access token cannot be empty' );
		}

		if ( $updateToken === '' ) {
			throw new \InvalidArgumentException( 'Update token cannot be empty' );
		}

		$this->accessToken = $accessToken;
		$this->updateToken = $updateToken;
	}

	public function getAccessToken(): string {
		return $this->accessToken;
	}

	public function getUpdateToken(): string {
		return $this->updateToken;
	}

}
