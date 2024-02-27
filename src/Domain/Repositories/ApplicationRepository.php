<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Domain\Repositories;

use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;

/**
 * @license GPL-2.0-or-later
 */
interface ApplicationRepository {

	/**
	 * When storing a not yet persisted MembershipApplication, a new id will be generated and assigned to it.
	 * This means the id of new applications needs to be null. The id can be accessed by calling getId on
	 * the passed in MembershipApplication.
	 *
	 * @param MembershipApplication $application
	 *
	 * @throws StoreMembershipApplicationException
	 */
	public function storeApplication( MembershipApplication $application ): void;

	/**
	 * @param int $id
	 *
	 * @return MembershipApplication|null
	 *
	 * @throws GetMembershipApplicationException
	 */
	public function getUnexportedMembershipApplicationById( int $id ): ?MembershipApplication;

}
