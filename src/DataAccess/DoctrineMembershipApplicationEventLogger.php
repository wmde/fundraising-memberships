<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogException;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogger;

class DoctrineMembershipApplicationEventLogger implements MembershipApplicationEventLogger {

	public function __construct( private readonly EntityManager $entityManager ) {
	}

	public function log( int $membershipApplicationId, string $message ): void {
		try {
			/** @var ?MembershipApplication $membershipApplication */
			$membershipApplication = $this->entityManager->find(
				MembershipApplication::class,
				$membershipApplicationId
			);
		} catch ( ORMException $e ) {
			throw new MembershipApplicationEventLogException( 'Could not get application', $e );
		}

		if ( $membershipApplication === null ) {
			throw new MembershipApplicationEventLogException(
				'Could not find application with id ' . $membershipApplicationId
			);
		}

		$data = $membershipApplication->getDecodedData();
		if ( empty( $data['log'] ) || !is_array( $data['log'] ) ) {
			$data['log'] = [];
		}

		$data['log'][$this->currentDateTime()] = $message;
		$membershipApplication->encodeAndSetData( $data );

		try {
			$this->entityManager->persist( $membershipApplication );
			$this->entityManager->flush();
		} catch ( ORMException $e ) {
			throw new MembershipApplicationEventLogException( 'Could not store application', $e );
		}
	}

	private function currentDateTime(): string {
		return date( 'Y-m-d H:i:s' );
	}
}
