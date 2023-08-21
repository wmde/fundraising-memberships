<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Authorization;

/**
 * @deprecated The calling code should be able to rely on other methods of the
 *      {@see \WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizer} implementation to get the tokens
 *
 */
interface ApplicationTokenFetcher {

	/**
	 * @param int $membershipApplicationId
	 *
	 * @return MembershipApplicationTokens
	 * @throws ApplicationTokenFetchingException
	 */
	public function getTokens( int $membershipApplicationId ): MembershipApplicationTokens;

}
