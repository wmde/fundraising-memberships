<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds new column to a table
 */
final class Version20260219132714 extends AbstractMigration {

	public function getDescription(): string {
		return 'Add "filledOn" timestamp column to "membership_fee_changes" table';
	}

	public function up( Schema $schema ): void {
		$feeChangesTable = $schema->getTable( 'membership_fee_changes' );
		$feeChangesTable->addColumn( 'filled_on', 'datetime', [ 'notnull' => false ] );
	}

	public function down( Schema $schema ): void {
		$feeChangesTable = $schema->getTable( 'membership_fee_changes' );
		$feeChangesTable->dropColumn( 'filled_on' );
	}
}
