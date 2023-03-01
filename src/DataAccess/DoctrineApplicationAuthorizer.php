<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

/**
 * @license GPL-2.0-or-later
 */
class DoctrineApplicationAuthorizer implements ApplicationAuthorizer {

	private DoctrineApplicationTable $table;
	private string $updateToken;
	private string $accessToken;

	public function __construct( EntityManager $entityManager, string $updateToken = '', string $accessToken = '' ) {
		// TODO: Add non-null logger
		$this->table = new DoctrineApplicationTable( $entityManager, new NullLogger() );
		$this->updateToken = $updateToken;
		$this->accessToken = $accessToken;
	}

	public function canModifyApplication( int $applicationId ): bool {
		try {
			$application = $this->table->getApplicationById( $applicationId );
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

	public function canAccessApplication( int $applicationId ): bool {
		try {
			$application = $this->table->getApplicationById( $applicationId );
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
