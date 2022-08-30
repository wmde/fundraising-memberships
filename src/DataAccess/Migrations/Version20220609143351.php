<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * This migration prepares the membership table for using the new payment persistence tables.
 *
 * Since the payment data is not consistent and easily accessible, this is just modifying the structure,
 * the data migration is in {@see \WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration\PaymentMigrationCommand}
 *
 * After the data migration, run {@see }
 */
final class Version20220609143351 extends AbstractMigration {
	public function getDescription(): string {
		return 'Use new payment domain for memberships';
	}

	public function up( Schema $schema ): void {
		$membershipTable = $schema->getTable( 'request' );
		$membershipTable->dropColumn( 'account_holder' );
		$membershipTable->addColumn( 'payment_id', 'integer', [ 'unsigned' => true ] );
	}

	public function down( Schema $schema ): void {
		$membershipTable = $schema->getTable( 'request' );
		$membershipTable->dropColumn( 'payment_id' );
		$membershipTable->addColumn( 'account_holder', 'string', [ 'length' => 50 ] );
	}
}
