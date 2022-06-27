<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
