<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Internal;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;

/**
 * This class acts as a "poor man's repository". We us it in the DoctrineMembershipApplicationRepository, but
 * its main purpose is for persisting values that are stored in the "requests" (i.e. "membership applications") table,
 * but that are not part of the MembershipApplication domain entity.
 *
 * The more we move values out of the MembershipApplication Doctrine entity, the less we need this class.
 */
class DoctrineApplicationTable {

	public function __construct( private readonly EntityManager $entityManager ) {
	}

	public function getApplicationOrNullById( int $applicationId ): ?MembershipApplication {
		try {
			$application = $this->entityManager->find( MembershipApplication::class, $applicationId );
		} catch ( ORMException $ex ) {
			throw new GetMembershipApplicationException( 'Membership application could not be accessed' );
		}

		return $application;
	}

	public function getApplicationById( int $applicationId ): MembershipApplication {
		$application = $this->getApplicationOrNullById( $applicationId );

		if ( $application === null ) {
			throw new GetMembershipApplicationException( 'Membership application does not exist' );
		}

		return $application;
	}

	public function persistApplication( MembershipApplication $application ): void {
		try {
			$this->entityManager->persist( $application );
			$this->entityManager->flush();
		} catch ( ORMException $ex ) {
			throw new StoreMembershipApplicationException( 'Failed to persist membership application', $ex );
		}
	}

	/**
	 * @param int $applicationId
	 * @param callable $modificationFunction Gets called with the MembershipApplication
	 *
	 * @throws GetMembershipApplicationException
	 * @throws StoreMembershipApplicationException
	 */
	public function modifyApplication( int $applicationId, callable $modificationFunction ): void {
		$application = $this->getApplicationById( $applicationId );

		$modificationFunction( $application );

		$this->persistApplication( $application );
	}

}
