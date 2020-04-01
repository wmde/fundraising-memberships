<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DoctrineApplicationAuthorizer implements ApplicationAuthorizer {

	private $table;
	private $updateToken;
	private $accessToken;

	public function __construct( EntityManager $entityManager, string $updateToken = null, string $accessToken = null ) {
		$this->table = new DoctrineApplicationTable( $entityManager, new NullLogger() ); // TODO: logger
		$this->updateToken = $updateToken;
		$this->accessToken = $accessToken;
	}

	public function canModifyApplication( int $applicationId ): bool {
		try {
			$application = $this->table->getApplicationById( $applicationId );
		}
		catch ( GetMembershipApplicationException $ex ) {
			return false;
		}

		return $this->updateTokenMatches( $application );
	}

	private function updateTokenMatches( MembershipApplication $application ): bool {
		return hash_equals( (string)$application->getDataObject()->getUpdateToken(), (string)$this->updateToken );
	}

	public function canAccessApplication( int $applicationId ): bool {
		try {
			$application = $this->table->getApplicationById( $applicationId );
		}
		catch ( GetMembershipApplicationException $ex ) {
			return false;
		}

		return $this->accessTokenMatches( $application );
	}

	private function accessTokenMatches( MembershipApplication $application ): bool {
		return hash_equals( (string)$application->getDataObject()->getAccessToken(), (string)$this->accessToken );
	}

}
