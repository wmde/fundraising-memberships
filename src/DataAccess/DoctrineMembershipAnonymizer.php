<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\AnonymizationException;
use WMDE\Fundraising\MembershipContext\Domain\MembershipAnonymizer;

class DoctrineMembershipAnonymizer implements MembershipAnonymizer {

	private const string TABLE_NAME = 'request';

	/**
	 * @const array<string,string>
	 */
	private const array TABLE_FIELDS = [
		'anrede' => '',
		'firma' => '',
		'titel' => '',
		'name' => '',
		'vorname' => '',
		'nachname' => '',
		'strasse' => '',
		'plz' => '',
		'ort' => '',
		'email' => '',
		'phone' => '',
		'dob' => null,
		'account_number' => '',
		'bank_name' => '',
		'bank_code' => '',
		'iban' => '',
		'bic' => '',
	];

	private Connection $conn;

	public function __construct( Connection $conn ) {
		$this->conn = $conn;
	}

	public function anonymizeAll(): int {
		$this->conn->beginTransaction();
		$queryBuilder = $this->newUpdateQueryBuilder();
		$this->addConditionsForExportState( $queryBuilder );
		try {
			$rowCount = $queryBuilder->executeStatement();
			$this->conn->commit();
		} catch ( \Exception $e ) {
			throw new AnonymizationException( 'Could not update memberships.', 0, $e );
		}
		return intval( $rowCount );
	}

	/**
	 * @deprecated Use {@see self::anonymizeAll()} instead
	 */
	public function anonymizeAt( \DateTimeImmutable $timestamp ): int {
		$this->conn->beginTransaction();
		$queryBuilder = $this->newUpdateQueryBuilder();
		$queryBuilder->where( 'backup = :backupDate' )
			->setParameter( 'backupDate', \DateTime::createFromImmutable( $timestamp ), 'datetime' );
		try {
			$rowCount = $queryBuilder->executeStatement();
			$this->conn->commit();
		} catch ( \Exception $e ) {
			throw new AnonymizationException( 'Could not update memberships.', 0, $e );
		}
		return intval( $rowCount );
	}

	public function anonymizeWithIds( int ...$membershipIds ): void {
		$countQuery = $this->conn->createQueryBuilder()->select( "COUNT(id)" )->from( self::TABLE_NAME );
		$countQuery = $this->addConditionsForIdsAndExportState( $countQuery, ...$membershipIds );
		$count = $countQuery->executeQuery()->fetchOne();
		if ( !is_scalar( $count ) || intval( $count ) !== count( $membershipIds ) ) {
			throw new AnonymizationException( sprintf(
				"No membership found with IDs '%s' or some memberships are not exported",
				implode( ", ", $membershipIds )
			) );
		}

		$queryBuilder = $this->newUpdateQueryBuilder();
		$queryBuilder = $this->addConditionsForIdsAndExportState( $queryBuilder, ...$membershipIds );

		try {
			$queryBuilder->executeStatement();
		} catch ( \Exception $e ) {
			throw new AnonymizationException( 'Could not update memberships.', 0, $e );
		}
	}

	private function addConditionsForIdsAndExportState( QueryBuilder $queryBuilder, int ...$membershipIds ): QueryBuilder {
		$queryBuilder->andWhere( 'id IN (:membershipIds)' )
			->setParameter( 'membershipIds', $membershipIds, ArrayParameterType::INTEGER );
		return $this->addConditionsForExportState( $queryBuilder );
	}

	/**
	 * Add conditions to select the memberships that *can* be scrubbed and protect the memberships that should not be scrubbed.
	 *
	 * The "rules" for selecting are
	 * - Exported Memberships
	 * - Deleted Memberships
	 */
	private function addConditionsForExportState( QueryBuilder $queryBuilder ): QueryBuilder {
		$queryBuilder->andWhere( $queryBuilder->expr()->or(
				$queryBuilder->expr()->isNotNull( 'export' ),
				$queryBuilder->expr()->in( 'status', [
					strval( MembershipApplication::STATUS_CANCELED ),
					strval( MembershipApplication::STATUS_CANCELLED_MODERATION )
				] )
			) );
		return $queryBuilder;
	}

	private function newUpdateQueryBuilder(): QueryBuilder {
		$queryBuilder = $this->conn->createQueryBuilder();
		$queryBuilder->update( self::TABLE_NAME );
		foreach ( self::TABLE_FIELDS as $field => $value ) {
			$queryBuilder->set( $field, $queryBuilder->createNamedParameter( $value ) );
		}
		return $queryBuilder;
	}

}
