<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;

interface ApplicationRepository {

	/**
	 * @throws StoreMembershipApplicationException
	 */
	public function storeApplication( MembershipApplication $application ): void;

	/**
	 * Get a MembershipApplication domain object.
	 *
	 * Will throw a {@see ApplicationAnonymizedException} when the membership application has been anonymized.
	 * For most of the use cases this is desired behavior. If you ever need to read an anonymized membership application,
	 * add a new method to the interface.
	 *
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 *
	 * @throws GetMembershipApplicationException
	 */
	public function getUnexportedMembershipApplicationById( int $id ): ?MembershipApplication;

}
