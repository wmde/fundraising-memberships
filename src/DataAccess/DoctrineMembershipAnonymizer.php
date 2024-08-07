<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use WMDE\Clock\Clock;
use WMDE\Fundraising\MembershipContext\Domain\AnonymizationException;
use WMDE\Fundraising\MembershipContext\Domain\MembershipAnonymizer;

class DoctrineMembershipAnonymizer implements MembershipAnonymizer {

	private const TABLE_NAME = 'request';

	private const TABLE_FIELDS = [
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
	private Clock $clock;
	private \DateInterval $gracePeriodForUnexportedData;

	public function __construct( Connection $conn, Clock $clock, \DateInterval $gracePeriodForUnexportedData ) {
		$this->conn = $conn;
		$this->clock = $clock;
		$this->gracePeriodForUnexportedData = $gracePeriodForUnexportedData;
	}

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
		$gracePeriodCutoffDate = $this->clock->now()->sub( $this->gracePeriodForUnexportedData )->format( 'Y-m-d H:i:s' );
		$countQuery = $this->conn->createQueryBuilder()->select( "COUNT(id)" )->from( self::TABLE_NAME );
		$countQuery = $this->addConditionsForIdsAndExportState( $countQuery, $gracePeriodCutoffDate, ...$membershipIds );
		$count = $countQuery->executeQuery()->fetchOne();
		if ( !is_scalar( $count ) || intval( $count ) !== count( $membershipIds ) ) {
			throw new AnonymizationException( sprintf(
				"No membership found with IDs '%s' or some memberships are not exported",
				implode( ", ", $membershipIds )
			) );
		}

		$queryBuilder = $this->newUpdateQueryBuilder();
		$queryBuilder = $this->addConditionsForIdsAndExportState( $queryBuilder, $gracePeriodCutoffDate, ...$membershipIds );

		try {
			$queryBuilder->executeStatement();
		} catch ( \Exception $e ) {
			throw new AnonymizationException( 'Could not update memberships.', 0, $e );
		}
	}

	private function addConditionsForIdsAndExportState( QueryBuilder $queryBuilder, string $gracePeriodCutoffDate, int ...$membershipIds ): QueryBuilder {
		$queryBuilder->where( 'id IN (:membershipIds)' )
			->andWhere( $queryBuilder->expr()->or(
				$queryBuilder->expr()->isNotNull( 'export' ),
				$queryBuilder->expr()->lte( 'timestamp', ':creationTime' )
			) )
			->setParameter( 'membershipIds', $membershipIds, ArrayParameterType::INTEGER )
			->setParameter( 'creationTime', $gracePeriodCutoffDate, ParameterType::STRING );
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
