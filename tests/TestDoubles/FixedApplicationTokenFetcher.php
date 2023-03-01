<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationTokenFetcher;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipApplicationTokens;

class FixedApplicationTokenFetcher implements ApplicationTokenFetcher {

	public const ACCESS_TOKEN = 'testAccessToken';
	public const UPDATE_TOKEN = 'testUpdateToken';

	public static function newWithDefaultTokens(): self {
		return new self(
			new MembershipApplicationTokens(
				self::ACCESS_TOKEN,
				self::UPDATE_TOKEN
			)
		);
	}

	public function __construct( private readonly MembershipApplicationTokens $tokens ) {
	}

	public function getTokens( int $membershipApplicationId ): MembershipApplicationTokens {
		return $this->tokens;
	}

}
