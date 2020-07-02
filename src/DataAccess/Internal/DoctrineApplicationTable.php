<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Internal;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DoctrineApplicationTable {

	private $entityManager;
	private $logger;

	public function __construct( EntityManager $entityManager, LoggerInterface $logger ) {
		$this->entityManager = $entityManager;
		$this->logger = $logger;
	}

	public function getApplicationOrNullById( int $applicationId ): ?MembershipApplication {
		try {
			$application = $this->entityManager->find( MembershipApplication::class, $applicationId );
		}
		catch ( ORMException $ex ) {
			$this->logPersistenceError( $ex, 'Membership application could not be accessed' );
			throw new GetMembershipApplicationException( 'Membership application could not be accessed' );
		}

		return $application;
	}

	private function logPersistenceError( ORMException $previous, string $message ) {
		$this->logger->log( LogLevel::CRITICAL, $message, [ 'exception' => $previous ] );
	}

	public function getApplicationById( int $applicationId ): MembershipApplication {
		$application = $this->getApplicationOrNullById( $applicationId );

		if ( $application === null ) {
			throw new GetMembershipApplicationException( 'Membership application does not exist' );
		}

		return $application;
	}

	public function persistApplication( MembershipApplication $application ) {
		try {
			$this->entityManager->persist( $application );
			$this->entityManager->flush();
		}
		catch ( ORMException $ex ) {
			$this->logPersistenceError( $ex, 'Failed to persist membership application' );
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
	public function modifyApplication( int $applicationId, callable $modificationFunction ) {
		$application = $this->getApplicationById( $applicationId );

		$modificationFunction( $application );

		$this->persistApplication( $application );
	}

}
