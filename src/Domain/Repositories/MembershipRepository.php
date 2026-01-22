<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;

interface MembershipRepository {

	/**
	 * @throws StoreMembershipApplicationException
	 */
	public function storeApplication( MembershipApplication $application ): void;

	public function getMembershipApplicationById( int $id ): ?MembershipApplication;

	/**
	 * Get an un-exported MembershipApplication domain object.
	 *
	 * Will throw a {@see ApplicationAnonymizedException} when the membership application has been anonymized.
	 *
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 *
	 * @throws GetMembershipApplicationException
	 */
	public function getUnexportedAndUnscrubbedMembershipApplicationById( int $id ): ?MembershipApplication;

}
