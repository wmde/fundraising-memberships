<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

/**
 * This is only for checking legacy donation authorizations.
 *  New donations should use an implementation of MembershipAuthorizationChecker that uses tokens stored outside the bounded context.
 */
class DoctrineMembershipAuthorizationChecker implements MembershipAuthorizationChecker {

	private DoctrineApplicationTable $table;

	public function __construct(
		EntityManager $entityManager,
		private readonly string $updateToken = '',
		private readonly string $accessToken = ''
	) {
		$this->table = new DoctrineApplicationTable( $entityManager );
	}

	public function canModifyMembership( int $membershipId ): bool {
		try {
			$application = $this->table->getApplicationById( $membershipId );
		} catch ( GetMembershipApplicationException $ex ) {
			return false;
		}

		return $this->updateTokenMatches( $application );
	}

	private function updateTokenMatches( MembershipApplication $application ): bool {
		if ( $this->updateToken === '' ) {
			return false;
		}
		return hash_equals( (string)$application->getDataObject()->getUpdateToken(), $this->updateToken );
	}

	public function canAccessMembership( int $membershipId ): bool {
		try {
			$application = $this->table->getApplicationById( $membershipId );
		} catch ( GetMembershipApplicationException $ex ) {
			return false;
		}

		return $this->accessTokenMatches( $application );
	}

	private function accessTokenMatches( MembershipApplication $application ): bool {
		if ( $this->accessToken === '' ) {
			return false;
		}
		return hash_equals( (string)$application->getDataObject()->getAccessToken(), $this->accessToken );
	}

}
