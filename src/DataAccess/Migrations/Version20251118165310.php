<?php

declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20251118165310 extends AbstractMigration {
	public function getDescription(): string {
		return 'Add is_scrubbed column';
	}

	public function up( Schema $schema ): void {
		$memberships = $schema->getTable( 'request' );
		$memberships->addColumn( 'is_scrubbed', Types::BOOLEAN )
			->setNotnull( false )
			->setDefault( 0 );
		$memberships->addIndex( [ 'is_scrubbed' ] );
	}

	public function down( Schema $schema ): void {
		$memberships = $schema->getTable( 'request' );
		$memberships->dropColumn( 'is_scrubbed' );
	}
}
