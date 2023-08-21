<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipIdGenerator;

class DoctrineMembershipIdGenerator implements MembershipIdGenerator {

	private EntityManager $entityManager;

	public function __construct( EntityManager $entityManager ) {
		$this->entityManager = $entityManager;
	}

	public function generateNewMembershipId(): int {
		$connection = $this->entityManager->getConnection();

		$membershipId = $connection->transactional( function ( Connection $connection ): mixed {
			$this->updateMembershipId( $connection );
			$result = $this->getCurrentIdResult( $connection );
			$id = $result->fetchOne();

			if ( $id === false ) {
				throw new \RuntimeException( 'The ID generator needs a row with initial membership_id set to 0.' );
			}

			return $id;
		} );

		return ScalarTypeConverter::toInt( $membershipId );
	}

	private function updateMembershipId( Connection $connection ): void {
		$statement = $connection->prepare( "UPDATE last_generated_membership_id SET membership_id = membership_id + 1" );
		$statement->executeStatement();
	}

	private function getCurrentIdResult( Connection $connection ): Result {
		$statement = $connection->prepare( 'SELECT membership_id FROM last_generated_membership_id LIMIT 1' );
		return $statement->executeQuery();
	}

}
