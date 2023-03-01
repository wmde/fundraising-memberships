<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

class MembershipApplicationTokens {

	public function __construct( private readonly string $accessToken, private readonly string $updateToken ) {
		if ( $accessToken === '' ) {
			throw new \InvalidArgumentException( 'Access token must not be empty' );
		}

		if ( $updateToken === '' ) {
			throw new \InvalidArgumentException( 'Update token must not be empty' );
		}
	}

	public function getAccessToken(): string {
		return $this->accessToken;
	}

	public function getUpdateToken(): string {
		return $this->updateToken;
	}

}
