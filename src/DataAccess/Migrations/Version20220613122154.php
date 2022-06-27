<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220613122154 extends AbstractMigration {
	public function getDescription(): string {
		return 'Add index to payment';
	}

	public function up( Schema $schema ): void {
		$membershipTable = $schema->getTable( 'request' );
		$membershipTable->changeColumn( 'payment_id', [ 'nullable' => false, 'unsigned' => true ] );
		$membershipTable->addIndex( [ 'payment_id' ], 'm_payment_id' );
	}

	public function down( Schema $schema ): void {
		$membershipTable = $schema->getTable( 'request' );
		$membershipTable->dropIndex( 'm_payment_id' );
		$membershipTable->changeColumn( 'payment_id', [ 'nullable' => true, 'unsigned' => true ] );
	}
}
