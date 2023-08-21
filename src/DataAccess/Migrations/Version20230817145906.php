<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230817145906 extends AbstractMigration {
	public function getDescription(): string {
		return 'Generate membership IDs with table';
	}

	public function up( Schema $schema ): void {
		$membershipIdTable = $schema->createTable( 'last_generated_membership_id' );
		$membershipIdTable->addColumn( 'membership_id', 'integer' );
		$membershipIdTable->setPrimaryKey( [ 'membership_id' ] );
		$membershipTable = $schema->getTable( 'request' );
		$membershipTable->getColumn( 'id' )->setAutoincrement( false );
	}

	public function postUp( Schema $schema ): void {
		$this->addSql(
			'INSERT INTO last_generated_membership_id (membership_id) VALUES ((SELECT MAX(id) FROM request))'
		);
	}

	public function down( Schema $schema ): void {
		$schema->dropTable( 'last_generated_membership_id' );
		$this->write( 'Please add back the AUTO_INCREMENT property to spenden.id. You can find instructions in ' . __FILE__ );

		// MySQL/MariaDB will fail to add back the autoincrement by calling
		// $membershipTable->getColumn( 'id' )->setAutoincrement(true)
		// It fails because the change *could* affect a foreign key constraint (to the moderation table)
		// In reality, that change does *not* affect the constraint, so if you really wanted to undo this migration,
		// you could run the following SQL commands:
		//
		// SET FOREIGN_KEY_CHECKS=0;
		// ALTER TABLE request MODIFY id int(12) NOT NULL AUTO_INCREMENT;
		// SET FOREIGN_KEY_CHECKS=1;
	}
}
