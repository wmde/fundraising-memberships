<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220701160624 extends AbstractMigration {
	public function getDescription(): string {
		return 'Add moderation reasons and join table';
	}

	public function up( Schema $schema ): void {
		$reasonTable = $schema->createTable( 'membership_moderation_reason' );

		$id = $reasonTable->addColumn( 'id', 'integer' );
		$id->setAutoincrement( true );
		$reasonTable->setPrimaryKey( [ 'id' ] );

		$reasonTable->addColumn( 'moderation_identifier', 'string', [ 'length' => 50, 'notnull' => true ] );
		$reasonTable->addColumn( 'source', 'string', [ 'length' => 32, 'notnull' => true ] );
		$reasonTable->addIndex( [ 'moderation_identifier', 'source' ], 'mr_identifier' );

		$reasonJoinTable = $schema->createTable( 'memberships_moderation_reasons' );
		$reasonJoinTable->addColumn( 'membership_id', 'integer', [ 'notnull' => true ] );
		$reasonJoinTable->addColumn( 'moderation_reason_id', 'integer', [ 'notnull' => true ] );
		$reasonJoinTable->setPrimaryKey( [ 'membership_id', 'moderation_reason_id' ] );
		$reasonJoinTable->addForeignKeyConstraint( 'request', [ 'membership_id' ], [ 'id' ] );
		$reasonJoinTable->addForeignKeyConstraint( 'membership_moderation_reason', [ 'moderation_reason_id' ], [ 'id' ] );
		$reasonJoinTable->addIndex( [ 'membership_id' ] );
		$reasonJoinTable->addIndex( [ 'moderation_reason_id' ] );
	}

	public function down( Schema $schema ): void {
		$schema->dropTable( 'memberships_moderation_reasons' );
		$schema->dropTable( 'membership_moderation_reason' );
	}
}
