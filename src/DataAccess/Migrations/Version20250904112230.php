<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create table for fee changes
 */
final class Version20250904112230 extends AbstractMigration {

	public function getDescription(): string {
		return 'Add Fee Changes table';
	}

	public function up( Schema $schema ): void {
		$feeChangesTable = $schema->createTable( 'membership_fee_changes' );
		$feeChangesTable->addColumn( 'id', 'integer', [] );
		$feeChangesTable->getColumn( 'id' )->setAutoincrement( true );
		$feeChangesTable->addPrimaryKeyConstraint(
			PrimaryKeyConstraint::editor()
				->setUnquotedColumnNames( 'id' )
				->create()
		);
		$feeChangesTable->addColumn( 'uuid', 'string', [ 'length' => 36, 'notnull' => true, 'unique' => true ] );
		$feeChangesTable->addColumn( 'external_member_id', 'integer', [ 'notnull' => true ] );
		$feeChangesTable->addColumn( 'current_amount_in_cents', 'integer', [ 'notnull' => true ] );
		$feeChangesTable->addColumn( 'suggested_amount_in_cents', 'integer', [ 'notnull' => true ] );
		$feeChangesTable->addColumn( 'current_interval', 'integer', [ 'notnull' => true ] );
		$feeChangesTable->addColumn( 'state', 'string', [ 'length' => 8, 'notnull' => true ] );
		$feeChangesTable->addColumn( 'export_date', 'datetime' );
		$feeChangesTable->addColumn( 'payment_id', 'integer' );

		$feeChangesTable->addIndex( [ 'uuid' ], 'm_fc_identifier' );
		$feeChangesTable->addIndex( [ 'payment_id' ], 'm_fc_payment_id' );
	}

	public function down( Schema $schema ): void {
		$schema->dropTable( 'membership_fee_changes' );
	}
}
